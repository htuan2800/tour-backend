<?php

namespace App\Services;

use App\Http\Resources\BookingResource;
use App\Mail\BookingStatusMail;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\TourSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentService
{
    private $momoService;
    private $bookingService;

    // Inject MomoService vào đây
    public function __construct(MomoService $momoService, BookingService $bookingService)
    {
        $this->momoService = $momoService;
        $this->bookingService = $bookingService;
    }

    public function processBooking(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Lấy thông tin lịch trình & Tính tổng tiền BẢO MẬT TỪ BACKEND
            $booking_date = now();
            $schedule = TourSchedule::findOrFail($data['schedule_id']);
            if ($schedule->departure_date < $booking_date) {
                throw new Exception("Qúa hạn đặt tour!");
            }
            $adultCount = count($data['adults']);
            $childCount = isset($data['children']) ? count($data['children']) : 0;
            $totalAmount = ($adultCount * $schedule->price_adult) + ($childCount * $schedule->price_child);

            $originalAmount = ($adultCount * $schedule->price_adult) + ($childCount * $schedule->price_child);
            $discountAmount = 0;
            $appliedCouponId = null;
            if (!empty($data['voucherCode'])) {
                $coupon = Coupon::where('code', $data['voucherCode'])
                    ->where('is_active', true) // Trạng thái mã đang bật
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->whereColumn('usage_count', '<', 'usage_limit')
                    ->first();

                if (!$coupon) {
                    throw new Exception("Mã giảm giá không hợp lệ hoặc đã hết lượt sử dụng.");
                }

                // Tính tiền giảm 
                if ($coupon->discount_type === 'PERCENT') {
                    $discountAmount = ($originalAmount * $coupon->discount_value) / 100;
                    if ($coupon->max_discount && $discountAmount > $coupon->max_discount) {
                        $discountAmount = $coupon->max_discount;
                    }
                } else {
                    // Giảm tiền mặt
                    $discountAmount = $coupon->discount_value;
                }

                $appliedCouponId = $coupon->coupon_id; // Lấy ID để lưu vào đơn hàng

                // Tăng số lượt đã sử dụng của mã này lên 1
                $coupon->increment('usage_count');
            }
            $finalAmount = max(0, $originalAmount - $discountAmount);

            $schedule->increment('current_booked', $adultCount + $childCount);
            $user = User::where('email', $data['contact']['email'])
                // ->has('customer')
                ->first();
            $userId = $user?->user_id;

            $booking = Booking::create([
                'user_id'       => $userId, // Lấy user_id từ token đã xác thực
                'schedule_id'   => $schedule->schedule_id,
                'coupon_id'       => $appliedCouponId,
                'contact_fullName' => $data['contact']['fullName'],
                'contact_phone' => $data['contact']['phone'],
                'contact_email' => $data['contact']['email'],
                'contact_address' => $data['contact']['address'],
                'applied_price_adult' => $schedule->price_adult,
                'applied_price_children' => $schedule->price_child,
                'number_of_adults' => $adultCount,
                'number_of_children' => $childCount,
                'original_price' => $totalAmount,
                'discount_amount' => $discountAmount,
                'total_price'  => $finalAmount,
                'payment_method' => $data['paymentMethod'],
                'status'        => 'PENDING',
                'note'          => $data['note'] ?? null,
                'booking_date'  => $booking_date,
            ]);

            // 3. Lưu danh sách hành khách vào bảng phụ (passengers)
            $passengers = [];
            foreach (array_merge($data['adults'], $data['children'] ?? []) as $p) {
                $passengers[] = [
                    'full_name' => $p['fullName'],
                    'gender'    => $p['gender'],
                    'dob'       => $p['dob'],
                    'type'      => in_array($p, $data['adults']) ? 'ADULT' : 'CHILD'
                ];
            }
            $booking->passengers()->createMany($passengers);

            $uniquePaymentCode = 'PAYMENT_' . time() . '_' . Str::random(5);
            // 4. Xử lý phương thức thanh toán
            if ($data['paymentMethod'] === 'MOMO') {
                // Gọi sang MomoService tạo link thanh toán
                $payUrl = $this->momoService->createPayment($finalAmount, $uniquePaymentCode);
                Payment::create([
                    'booking_id'     => $booking->booking_id, // Lấy ID nội bộ
                    'amount'         => $finalAmount,
                    'payment_method' => 'MOMO',
                    'transaction_id' => null,
                    'transaction_code' => $uniquePaymentCode,
                    'payment_status' => 'PENDING',
                ]);
                Mail::to($booking->contact_email)->send(new BookingStatusMail($booking));
                return [
                    'message' => 'Vui lòng thanh toán để hoàn tất',
                    'payUrl'  => $payUrl
                ];
            } else if ($data['paymentMethod'] === 'CASH') {
                // Nếu thanh toán tiền mặt/tại quầy thì trả về trang thành công luôn
                Payment::create([
                    'booking_id'     => $booking->booking_id, // Lấy ID nội bộ
                    'amount'         => $finalAmount,
                    'payment_method' => 'CASH',
                    'transaction_id' => null,
                    'transaction_code' => $uniquePaymentCode,
                    'payment_status' => 'PENDING',
                ]);
                Mail::to($booking->contact_email)->send(new BookingStatusMail($booking));
                return [
                    'message' => 'Đặt tour thành công, vui lòng thanh toán tại quầy!',
                ];
            }
        });
    }

    public function processIpn(array $data)
    {
        Log::info('MoMo IPN Data: ', $data);

        $accessKey   = config('momo.access_key');
        $secretKey   = config('momo.secret_key');
        $partnerCode = config('momo.partner_code');

        // 1. Tái tạo lại chuỗi dữ liệu (Raw Data) theo đúng thứ tự MoMo yêu cầu
        $rawHash = "accessKey=" . $accessKey .
            "&amount=" . $data['amount'] .
            "&extraData=" . $data['extraData'] .
            "&message=" . $data['message'] .
            "&orderId=" . $data['orderId'] .
            "&orderInfo=" . $data['orderInfo'] .
            "&orderType=" . $data['orderType'] .
            "&partnerCode=" . $data['partnerCode'] .
            "&payType=" . $data['payType'] .
            "&requestId=" . $data['requestId'] .
            "&responseTime=" . $data['responseTime'] .
            "&resultCode=" . $data['resultCode'] .
            "&transId=" . $data['transId'];

        // 2. Ký lại chữ ký bằng Secret Key của mình
        $mySignature = hash_hmac('sha256', $rawHash, $secretKey);

        // 3. So sánh chữ ký của mình với chữ ký MoMo gửi sang
        if ($mySignature !== $data['signature']) {
            Log::error('MoMo IPN: Sai chữ ký (Signature Mismatch)!');
            return false;
        }

        return DB::transaction(function () use ($data) {
            $payment = Payment::where('transaction_code', $data['orderId'])->first();
            $booking = Booking::where('booking_id', $payment->booking_id)->first();
            if (!$booking) {
                Log::error('MoMo IPN: Không tìm thấy mã đơn hàng ' . $data['orderId']);
                return false;
            }

            if ($booking->status === 'PAID') {
                return true;
            }

            if ($payment && $payment->payment_status === 'COMPLETED') {
                return true;
            }

            if ($data['resultCode'] == 0) {
                $this->bookingService->updateStatus($booking->booking_id, 'VERIFYING');

                $payment->update([
                    'transaction_id' => $data['transId'], // Cập nhật mã GD thật của MoMo
                ]);

                Log::info('MoMo IPN: Thanh toán thành công đơn ' . $booking->booking_code);
            } else {
                $this->bookingService->updateStatus($booking->booking_id, 'CANCELLED');
                $payment->update([
                    'payment_status' => 'FAILED',
                ]);
                Log::info('MoMo IPN: Khách hủy hoặc lỗi đơn ' . $booking->booking_code);
            }

            return true;
        });
    }

    public function checkPaymentStatus($orderId)
    {
        $payment = Payment::where('transaction_code', $orderId)->first();

        if (!$payment) {
            throw new Exception('Không tìm thấy đơn hàng');
        }

        return $payment->payment_status;
    }


    public function retryPayment($order_id)
    {
        $payment = Payment::where('transaction_code', $order_id)->first();
        $amount = (int) round($payment->booking->total_price);
        $uniquePaymentCode = 'PAYMENT_' . time() . '_' . Str::random(5);
        if ($payment) {
            $payment->update([
                'transaction_code' => $uniquePaymentCode, // Lưu lại để tí nữa IPN về còn biết đường tìm
                'payment_status' => 'PENDING'
            ]);
        }
        $payUrl = $this->momoService->createPayment($amount, $uniquePaymentCode);

        return [
            'message' => 'Vui lòng thanh toán để hoàn tất',
            'payUrl'  => $payUrl
        ];
    }
}
