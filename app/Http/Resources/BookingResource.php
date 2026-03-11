<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Dùng khóa chính của bạn (id hoặc booking_id)
            'id'           => $this->booking_id ?? $this->id, 
            'booking_code' => $this->booking_code,
            'total_amount' => $this->total_amount,
            'status'       => $this->status,
            'created_at'   => $this->created_at ? $this->created_at->format('d/m/Y H:i') : null,
            
            // Xử lý gộp tên Tour từ các bảng liên kết để Frontend dễ đọc nhất
            'tour' => $this->whenLoaded('schedule', function () {
                return [
                    'name' => $this->schedule->tour->name ?? 'Tour không xác định',
                ];
            }),
        ];
    }
}
