<?php

namespace App\Http\Resources;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'coupon_id' => $this->coupon_id,
            'code' => $this->code,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'is_active' => $this->is_active,
            'bookings' => BookingResource::collection($this->whenLoaded('bookings')),
        ];
    }
}
