<?php

namespace App\Services;

use App\Mail\BookingStatusMail;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\TourSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingService
{
    /**
     * Lấy danh sách booking của user kèm bộ lọc
     */
    public function getUserBookings($userId, array $filters)
    {
        DB::enableQueryLog();

        $query = Booking::with('schedule.tour')
            ->where('user_id', $userId)
            ->orderBy('booking_date', 'desc');

        // Lọc theo Status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Lọc theo Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('booking_id', 'LIKE', "%{$search}%")
                    ->orWhereHas('schedule.tour', function ($qTour) use ($search) {
                        $qTour->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // TÍNH TOÁN PHÂN TRANG Ở ĐÂY
        // Lấy limit từ request, nếu không có mặc định là 10 record / trang
        $perPage = isset($filters['limit']) ? (int) $filters['limit'] : 10;

        // Laravel sẽ TỰ ĐỘNG đọc tham số ?page=... trên URL, bạn không cần truyền 'page' vào hàm paginate()
        $bookings = $query->paginate($perPage);

        Log::info('Query Log:', DB::getQueryLog());

        return $bookings;
    }

    public function getBookingDetailForCustomer($bookingId, $userId)
    {
        DB::enableQueryLog();
        $booking = Booking::with(['schedule.tour', 'passengers', 'payment', 'coupon'])
            ->where('user_id', $userId)
            ->where('booking_id', $bookingId) // Đừng quên thêm điều kiện tìm ID
            ->first();
        Log::info('Query Log:', DB::getQueryLog());
        return $booking;
    }

    public function getBookingDetailForGuest($bookingId)
    {

        $booking = Booking::with(['schedule.tour', 'passengers', 'payment', 'coupon'])
            ->where('booking_id', $bookingId)
            ->first();

        return $booking;
    }

    public function getPagenatedBooking(int $limit, ?string $search, string $status = 'ALL')
    {
        $query = Booking::with('user');

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_id', 'LIKE', "%{$search}%")
                    ->orWhere('contact_fullName', 'LIKE', "%{$search}%")
                    ->orWhere('contact_phone', 'LIKE', "%{$search}%")
                    ->orWhere('contact_email', 'LIKE', "%{$search}%")
                    ->orWhere('contact_address', 'LIKE', "%{$search}%")
                    ->orWhereHas('user.customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('full_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('booking_date', 'DESC')->paginate($limit);
    }

    public function findBookingById(string $id)
    {
        DB::enableQueryLog();
        $booking = Booking::with(['schedule.tour', 'passengers', 'payment', 'coupon'])->findOrFail($id);
        Log::info('Query Log:', DB::getQueryLog());
        return $booking;
    }


    public function createBooking(array $data)
    {
        return DB::transaction(function () use ($data) {
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
                ->has('customer')
                ->first();
            $booking = Booking::create([
                'user_id'       => $user?->user_id,
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
                'booking_date'  => $booking_date
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

            Payment::create([
                'booking_id'     => $booking->booking_id, // Lấy ID nội bộ
                'amount'         => $finalAmount,
                'payment_method' => $data['paymentMethod'],
                'transaction_id' => null,
                'transaction_code' => $uniquePaymentCode,
                'payment_status' => 'PENDING',
            ]);

            Mail::to($booking->contact_email)->send(new BookingStatusMail($booking));
            return [
                'message' => 'Đặt tour thành công, vui lòng thanh toán tại quầy!',
            ];
        });
    }

    public function updateBooking(string $id, array $data)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status === 'VERIFYING') {
            $newTotalPassengers = count($data['adults']) + (isset($data['children']) ? count($data['children']) : 0);
            $oldTotalPassengers = $booking->number_of_adults + $booking->number_of_children;

            if ($newTotalPassengers !== $oldTotalPassengers) {
                throw new Exception("Đơn hàng đang chờ đối soát, không được phép thay đổi số lượng vé.");
            }
        }

        return DB::transaction(function () use ($booking, $data) {
            $schedule = TourSchedule::findOrFail($data['schedule_id']);

            $newAdultCount = count($data['adults']);
            $newChildCount = isset($data['children']) ? count($data['children']) : 0;
            $newTotalPassengers = $newAdultCount + $newChildCount;

            $oldTotalPassengers = $booking->number_of_adults + $booking->number_of_children;
            $passengerDiff = $newTotalPassengers - $oldTotalPassengers;

            // Nếu số người thay đổi, cập nhật lại số ghế trong Lịch trình
            if ($passengerDiff !== 0) {
                $availableSeats = $schedule->max_capacity - $schedule->current_booked;
                if ($passengerDiff > 0 && $passengerDiff > $availableSeats) {
                    throw new Exception("Lịch trình này không còn đủ chỗ trống.");
                }
                $schedule->increment('current_booked', $passengerDiff);
            }

            $originalAmount = ($newAdultCount * $booking->applied_price_adult) + ($newChildCount * $booking->applied_price_children);

            $discountAmount = 0;
            $newCouponId = null;

            if (!empty($data['voucherCode'])) {
                $coupon = Coupon::where('code', $data['voucherCode'])->first();
                if (!$coupon) throw new Exception("Mã giảm giá không hợp lệ.");

                if ($coupon->discount_type === 'PERCENT') {
                    $discountAmount = ($originalAmount * $coupon->discount_value) / 100;
                } else {
                    $discountAmount = $coupon->discount_value;
                }
                $newCouponId = $coupon->coupon_id;
            }

            $oldCouponId = $booking->coupon_id;

            // Nếu mã giảm giá bị thay đổi hoặc bị gỡ bỏ
            if ($oldCouponId !== $newCouponId) {
                if ($oldCouponId) {
                    Coupon::where('coupon_id', $oldCouponId)->decrement('usage_count');
                }
                if ($newCouponId) {
                    Coupon::where('coupon_id', $newCouponId)->increment('usage_count');
                }
            }

            $finalAmount = max(0, $originalAmount - $discountAmount);

            $booking->update([
                'schedule_id'            => $schedule->schedule_id,
                'coupon_id'              => $newCouponId,
                'contact_fullName'       => $data['contact']['fullName'],
                'contact_phone'          => $data['contact']['phone'],
                'contact_email'          => $data['contact']['email'],
                'contact_address'        => $data['contact']['address'],


                'number_of_adults'       => $newAdultCount,
                'number_of_children'     => $newChildCount,
                'original_price'         => $originalAmount,
                'discount_amount'        => $discountAmount,
                'total_price'            => $finalAmount,
                'note'                   => $data['note'] ?? $booking->note,
            ]);

            $booking->passengers()->delete(); // Xóa sạch hành khách cũ

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

            $payment = $booking->payment;

            // Nếu đơn chưa thanh toán, chỉ cập nhật lại số tiền mới
            if ($payment && $payment->payment_status === 'PENDING') {
                $payment->update([
                    'amount'         => $finalAmount,
                    'payment_method' => $data['paymentMethod']
                ]);
            }

            Mail::to($booking->contact_email)->send(new BookingStatusMail($booking));

            return [
                'message' => 'Cập nhật đơn hàng thành công!',
                'booking' => $booking->fresh()
            ];
        });
    }

    public function updateStatus(string $id, string $newStatus)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status === 'CANCELLED') {
            throw new Exception("Đơn hàng đã bị hủy, không thể thay đổi trạng thái được nữa!");
        }

        if ($booking->status === 'PAID' && $newStatus === 'PENDING') {
            throw new Exception("Đơn hàng đã thanh toán, không thể chuyển về trạng thái chờ thanh toán!");
        }

        switch ($newStatus) {
            case 'PENDING':
                if ($booking->status === 'PAID') {
                    throw new Exception("Đơn hàng đã thanh toán, không thể chuyển về trạng thái chờ thanh toán!");
                }
                $booking->payment()->update(['payment_status' => 'PENDING']);
                $booking->status = 'PENDING';
                break;
            case 'VERIFYING':
                if ($booking->status !== 'PENDING') {
                    throw new Exception("Chỉ có đơn hàng đang chờ thanh toán mới có thể chuyển sang chờ xác nhận!");
                }
                $booking->payment()->update(['payment_status' => 'COMPLETED']);
                $booking->status = 'VERIFYING';
                break;
            case 'PAID':
                if (!in_array($booking->status, ['PENDING', 'VERIFYING'])) {
                    throw new Exception("Chỉ có đơn hàng đang chờ thanh toán hoặc chờ xác nhận mới có thể chuyển sang đã thanh toán!");
                }
                $booking->payment()->update(['payment_status' => 'COMPLETED']);
                $booking->status = 'PAID';
                break;
            case 'CANCELLED':
                if ($booking->status === 'PAID') {
                    throw new Exception("Đơn hàng đã thanh toán, không thể hủy!");
                }
                $schedule = $booking->schedule;
                $totalPassengers = $booking->number_of_adults + $booking->number_of_children;
                $schedule->decrement('current_booked', $totalPassengers);
                $booking->payment()->update(['payment_status' => 'REFUNDED']);
                if ($booking->coupon_id) {
                    $booking->coupon()->decrement('usage_count');
                }
                $booking->status = 'CANCELLED';
                break;
            case 'EXPIRED':
                if ($booking->status === 'PAID') {
                    throw new Exception("Đơn hàng đã thanh toán, không thể hủy!");
                }
                $schedule = $booking->schedule;
                $totalPassengers = $booking->number_of_adults + $booking->number_of_children;
                $schedule->decrement('current_booked', $totalPassengers);
                $booking->payment()->update(['payment_status' => 'EXPIRED']);
                if ($booking->coupon_id) {
                    $booking->coupon()->decrement('usage_count');
                }
                $booking->status = 'EXPIRED';
                break;
            default:
                throw new Exception("Trạng thái không hợp lệ!");
        }

        $booking->status = $newStatus;
        $booking->save();

        Mail::to($booking->contact_email)->send(new BookingStatusMail($booking));

        return $booking;
    }
}
