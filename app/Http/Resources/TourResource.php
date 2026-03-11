<?php

namespace App\Http\Resources;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return
            [
                'tour_id' => $this->tour_id,
                'name' => $this->name,
                'description' => $this->description,
                'image_url' => $this->image_url ? Cloudinary::image($this->image_url)->toUrl() : null,
                'duration_days' => $this->duration_days,
                'duration_nights' => $this->duration_nights,
                'transportation' => $this->transportation,
                'destinations' => $this->whenLoaded('destinations', function () {
                    return $this->destinations->map(function ($location) {
                        return [
                            'location_id' => $location->location_id,
                            'name'        => $location->name,
                            'image_url'   => $location->image_url,
                            'type'        => $location->type,
                        ];
                    });
                }),

                'depart' => $this->whenLoaded('depart', function () {
                    return [
                        'location_id' => $this->depart->location_id,
                        'name'        => $this->depart->name,
                    ];
                }),
                'itineraries'      => TourItineraryResource::collection(
                    $this->whenLoaded('itineraries', function () {
                        return $this->itineraries->sortBy('day_number');
                    })
                ),
                'schedules'        => TourScheduleResource::collection($this->whenLoaded('schedules')),
                'is_active'        => (bool) $this->is_active,
            ];
    }
}
