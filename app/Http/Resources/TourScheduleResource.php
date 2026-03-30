<?php

namespace App\Http\Resources;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return
            [
                'schedule_id' => $this->schedule_id,
                'tour_id' => $this->tour_id,
                'departure_date' => $this->departure_date,
                'return_date' => $this->return_date,
                'price_adult' => $this->price_adult,
                'price_child' => $this->price_child,
                'max_capacity' => $this->max_capacity,
                'current_booked' => $this->current_booked,
                'status' => $this->status,
                'tour' => $this->whenLoaded('tour', function () {
                    return [
                        'id'   => $this->tour->id, // Hoặc tour_id tùy bạn đặt
                        'name' => $this->tour->name,
                        // Trỏ qua $this->tour để lấy image_url
                        'image_url' => $this->tour->image_url ? Cloudinary::image($this->tour->image_url)->toUrl() : null,
                    ];
                }),
                'bookings' => BookingResource::collection($this->whenLoaded('bookings'))
            ];
    }
}
