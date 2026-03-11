<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class BookingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $adults = $this->passengers->filter(function ($passenger) {
            return $passenger->type === 'ADULT'; // Cập nhật điều kiện này theo DB thực tế
        })->map(function ($passenger) {
            return [
                'fullName' => $passenger->full_name, // Chú ý đổi thành tên cột thật trong bảng passengers
                'gender'   => $passenger->gender,
                'dob'      => $passenger->dob ? Carbon::parse($passenger->dob)->format('Y-m-d') : '',
            ];
        })->values(); // Dùng ->values() để reset index của mảng (tránh trả về object trong JSON)

        $children = $this->passengers->filter(function ($passenger) {
            return $passenger->type === 'CHILD';
        })->map(function ($passenger) {
            return [
                'fullName' => $passenger->full_name,
                'gender'   => $passenger->gender,
                'dob'      => $passenger->dob ? Carbon::parse($passenger->dob)->format('Y-m-d') : '',
            ];
        })->values();

        // 2. Lấy phương thức thanh toán
        // Booking hasMany Payments, nên ta lấy method của giao dịch đầu tiên/mới nhất
        $paymentMethod = $this->payments->first() ? $this->payments->first()->payment_method : 'CASH';

        // 3. Trả về cấu trúc GIỐNG HỆT Zod Schema
        return [
            'booking_id'    => $this->booking_id,
            'schedule_id'   => (string) $this->schedule_id,
            'contact' => [
                'fullName' => $this->contact_fullName ?? '',
                'phone'    => $this->contact_phone ?? '',
                'email'    => $this->contact_email ?? '',
                'address'  => $this->contact_address ?? '', // Thêm vào DB nếu có, không thì để chuỗi rỗng
            ],

            'tour_schedule' => $this->schedule ? new TourScheduleResource($this->schedule) : null, // Thêm thông tin lịch trình tour nếu có
            // Nhóm Hành khách (Đã được chia mảng ở trên)
            'adults'   => $adults,
            'children' => $children,

            // Vận hành
            'paymentMethod' => $paymentMethod,
            'coupon'   => $this->coupon ? new CouponResource($this->coupon) : null, // Thêm thông tin mã giảm giá nếu có 
            'note'          => $this->note ?? '',
        ];
    }
}
