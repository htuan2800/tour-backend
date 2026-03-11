<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\TourSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private $momoService;

    // Inject MomoService vào đây
    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    public function processBooking(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Lấy thông tin lịch trình & Tính tổng tiền BẢO MẬT TỪ BACKEND
            $schedule = TourSchedule::findOrFail($data['schedule_id']);
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

            // Tùy chọn: Xử lý trừ tiền nếu có voucherCode ở đây...
            $uniqueOrderCode = 'TOUR_' . time() . '_' . Str::random(5);
            $booking = Booking::create([
                'booking_code' => $uniqueOrderCode,
                'user_id'       => Auth::user()?->user_id, // Lấy user_id từ token đã xác thực
                'schedule_id'   => $schedule->schedule_id,
                'coupon_id'       => $appliedCouponId,
                'contact_fullName' => $data['contact']['fullName'],
                'contact_phone' => $data['contact']['phone'],
                'contact_email' => $data['contact']['email'],
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

            // 4. Xử lý phương thức thanh toán
            if ($data['paymentMethod'] === 'MOMO') {
                // Gọi sang MomoService tạo link thanh toán, truyền tổng tiền và mã Booking
                $payUrl = $this->momoService->createPayment($finalAmount, $booking->booking_code);

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
                    'payment_status' => 'PENDING',
                ]);
                return [
                    'message' => 'Đặt tour thành công, vui lòng thanh toán tại quầy!',
                    'payUrl'  => url("/booking-success?code=" . $booking->booking_code)
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
            $booking = Booking::where('booking_code', $data['orderId'])->first();

            if (!$booking) {
                Log::error('MoMo IPN: Không tìm thấy mã đơn hàng ' . $data['orderId']);
                return false;
            }

            if ($booking->status === 'PAID') {
                return true;
            }

            if ($data['resultCode'] == 0) {
                $booking->update(['status' => 'VERIFYING']);

                Payment::create([
                    'booking_id'     => $booking->booking_id, // Lấy ID nội bộ
                    'amount'         => $data['amount'],
                    'payment_method' => 'MOMO',
                    'transaction_id' => $data['transId'], // Mã giao dịch của MoMo (Dùng đối soát sau này)
                    'payment_status'         => 'COMPLETED',
                ]);

                Log::info('MoMo IPN: Thanh toán thành công đơn ' . $booking->booking_code);
            } else {
                // Khách hủy thanh toán hoặc quẹt thẻ lỗi
                $booking->update(['status' => 'FAILED']); // Hoặc CANCELLED
                Log::info('MoMo IPN: Khách hủy hoặc lỗi đơn ' . $booking->booking_code);
            }

            return true;
        });
    }

    public function checkPaymentStatus($orderId)
    {
        $booking = Booking::where('booking_code', $orderId)->first();

        if (!$booking) {
            throw new Exception('Không tìm thấy đơn hàng');
        }

        return $booking->status; // PENDING, PAID, hoặc FAILED
    }
}
