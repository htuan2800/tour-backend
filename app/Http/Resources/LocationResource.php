<?php

namespace App\Http\Resources;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'location_id' => $this->location_id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_url ? Cloudinary::image($this->image_url)->toUrl() : null,
            'region' => $this->region,
            'is_active' => $this->is_active,
        ];
    }
}
