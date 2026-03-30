<?php

namespace App\Http\Resources;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
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
            'total_amount' => $this->total_price,
            'status'       => $this->status,
            'created_at'   => $this->booking_date ? $this->booking_date->format('d/m/Y H:i') : null,
            
            // Xử lý gộp tên Tour từ các bảng liên kết để Frontend dễ đọc nhất
            'tour' => $this->whenLoaded('schedule', function () {
                return [
                    'name' => $this->schedule->tour->name ?? 'Tour không xác định',
                    'image_url' => $this->schedule->tour->image_url ? Cloudinary::image($this->schedule->tour->image_url)->toUrl() : null,
                ];
            }),
        ];
    }
}
