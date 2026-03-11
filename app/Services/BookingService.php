<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\TourSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Str;

class BookingService
{
    /**
     * Lấy danh sách booking của user kèm bộ lọc
     */
    public function getUserBookings($userId, array $filters, $perPage = 10)
    {
        $query = Booking::with('schedule.tour')
            ->where('user_id', $userId)
            ->orderBy('booking_date', 'desc');

        // Lọc theo Status (bỏ qua nếu status là 'all')
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Lọc theo Search (mã đơn hoặc tên tour)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'LIKE', "%{$search}%")
                    ->orWhereHas('schedule.tour', function ($qTour) use ($search) {
                        $qTour->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage);
    }

    public function getPagenatedBooking(int $limit, ?string $search)
    {
        $query = Booking::with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'LIKE', "%{$search}%")
                    ->orWhere('contact_fullName', 'LIKE', "%{$search}%")
                    ->orWhere('contact_phone', 'LIKE', "%{$search}%")
                    ->orWhere('contact_email', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($qUser) use ($search) {
                        $qUser->where('fullName', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('booking_id', 'DESC')->paginate($limit);
    }

    public function findBookingById(string $id)
    {
        return Booking::with(['schedule.tour', 'passengers', 'payments', 'coupon'])->findOrFail($id);
    }


    public function createBooking(array $data)
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
            $user = User::where('email', $data['contact']['email'])
                ->whereHas('customer', function ($query) use ($data) {
                    // Tìm tên và SĐT ở bảng customers
                    $query->where('full_name', $data['contact']['fullName'])
                        ->where('phone', $data['contact']['phone']);
                })
                ->first();
            $booking = Booking::create([
                'booking_code' => $uniqueOrderCode,
                'user_id'       => $user?->user_id,
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

            Payment::create([
                'booking_id'     => $booking->booking_id, // Lấy ID nội bộ
                'amount'         => $finalAmount,
                'payment_method' => $data['paymentMethod'],
                'transaction_id' => null,
                'payment_status' => 'PENDING',
            ]);
            return [
                'message' => 'Đặt tour thành công, vui lòng thanh toán tại quầy!',
                'payUrl'  => url("/booking-success?code=" . $booking->booking_code)
            ];
        });
    }

    public function updateBooking(string $id, array $data)
    {
        $booking = Booking::findOrFail($id);
        return DB::transaction(function () use ($booking, $data) {
            $schedule = TourSchedule::findOrFail($data['schedule_id']);

            $newAdultCount = count($data['adults']);
            $newChildCount = isset($data['children']) ? count($data['children']) : 0;
            $newTotalPassengers = $newAdultCount + $newChildCount;

            $oldTotalPassengers = $booking->number_of_adults + $booking->number_of_children;
            $passengerDiff = $newTotalPassengers - $oldTotalPassengers; // Độ chênh lệch

            // Nếu số người thay đổi, cập nhật lại số ghế trong Lịch trình
            if ($passengerDiff !== 0) {
                $schedule->increment('current_booked', $passengerDiff);
            }

            $originalAmount = ($newAdultCount * $schedule->price_adult) + ($newChildCount * $schedule->price_child);
            $discountAmount = 0;
            $newCouponId = null;

            if (!empty($data['voucherCode'])) {
                $coupon = Coupon::where('code', $data['voucherCode'])
                    ->where('is_active', true)
                    ->first();

                if (!$coupon) throw new Exception("Mã giảm giá không hợp lệ.");

                // Logic tính tiền giảm giá (Giữ nguyên của bạn)
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
                // Hoàn lại 1 lượt cho mã CŨ (Nếu trước đó có dùng)
                if ($oldCouponId) {
                    Coupon::where('coupon_id', $oldCouponId)->decrement('usage_count');
                }
                // Trừ đi 1 lượt của mã MỚI (Nếu có nhập mã mới)
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
                'applied_price_adult'    => $schedule->price_adult,
                'applied_price_children' => $schedule->price_child,
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
            $booking->passengers()->createMany($passengers); // Insert lại danh sách mới

            $payment = $booking->payments()->first();

            // Nếu đơn chưa thanh toán, chỉ cập nhật lại số tiền mới
            if ($payment && $payment->payment_status === 'PENDING') {
                $payment->update([
                    'amount'         => $finalAmount,
                    'payment_method' => $data['paymentMethod']
                ]);
            }

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
            case 'VERIFYING':
                if ($booking->status !== 'PENDING') {
                    throw new Exception("Chỉ có đơn hàng đang chờ thanh toán mới có thể chuyển sang chờ xác nhận!");
                }
                $booking->payments()->update(['payment_status' => 'COMPLETED']);
                $booking->status = 'VERIFYING';
                break;
            case 'PAID':
                if (!in_array($booking->status, ['PENDING', 'VERIFYING'])) {
                    throw new Exception("Chỉ có đơn hàng đang chờ thanh toán hoặc chờ xác nhận mới có thể chuyển sang đã thanh toán!");
                }
                $booking->payments()->update(['payment_status' => 'COMPLETED']);
                $booking->status = 'PAID';
                break;
            case 'CANCELLED':
                if ($booking->status === 'PAID') {
                    throw new Exception("Đơn hàng đã thanh toán, không thể hủy!");
                }
                $schedule = $booking->schedule;
                $totalPassengers = $booking->number_of_adults + $booking->number_of_children;
                $schedule->decrement('current_booked', $totalPassengers);
                $booking->payments()->update(['payment_status' => 'REFUNDED']);
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
                $booking->payments()->update(['payment_status' => 'FAILED']);
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
        return $booking;
    }
}
