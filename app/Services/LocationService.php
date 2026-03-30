<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationService
{
    private $cloudinaryService;
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function getAllLocation()
    {
        return Location::all();
    }

    public function getPopularLocations($region = null, $limit = 6)
    {
        $query = Location::where('is_active', true);
        $query->where('region', $region);
        return $query->latest('location_id')->take($limit)->get();
    }

    public function getPagenatedLocation(int $limit, ?string $search)
    {
        $query = Location::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('location_id', 'DESC')->paginate($limit);
    }

    public function getGroupedLocations()
    {
        $locations = Location::where('is_active', true)
                             ->get();

        $groupedByRegion = $locations->groupBy('region')->map(function ($group) {
            return $group->take(5)->map(function ($item) {
                // Tạo ra một object mới chứa cả id và name
                return [
                    'slug' => $item->slug,
                    'name'        => $item->name,
                ];
            })->values()->toArray(); 
        });

        return [
            'mien-bac'  => $groupedByRegion->get('Northern', []),
            'mien-trung'  => $groupedByRegion->get('Central', []),
            'mien-dong-nam-bo' => $groupedByRegion->get('Southeast', []),
            'mien-tay-nam-bo' => $groupedByRegion->get('Southwest', []),
        ];
    }


    public function findLocationById(string $id)
    {
        return Location::where('location_id', $id)->first();
    }


    public function createLocation(array $data)
    {
        return DB::transaction(function () use ($data) {
            $rawSlug = !empty($data['slug']) ? $data['slug'] : $data['name'];
            $cleanSlug = Str::slug($rawSlug);
            $location = Location::create([
                'name'     => $data['name'],
                'slug'     => $cleanSlug,
                'description' => $data['description'],
                'image_url' => $data['image_url'],
                'region' => $data['region'],
                'is_active' => true,
            ]);

            return $location;
        });
    }


    public function updateLocation(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $location = Location::where('location_id', $id)->firstOrFail();
            if (!empty($data['image_url']) && $location->image_url) {
                try {
                    $this->cloudinaryService->deleteFile($location->image_url);
                } catch (\Exception $e) {
                    throw new \Exception("Lỗi: " . $e->getMessage());
                }
            }

            $rawSlug = !empty($data['slug']) ? $data['slug'] : $data['name'];
            $cleanSlug = Str::slug($rawSlug);
            $location->update(
                [
                    'name' => $data['name'],
                    'slug' => $cleanSlug,
                    'description' => $data['description'],
                    'region' => $data['region'],
                    'image_url' => isset($data['image_url']) ? $data['image_url'] : $location->image_url
                ]
            );

            return $location;
        });
    }

    public function toggleStatusLocation(Location $location): void
    {
        $location->is_active = !$location->is_active;
        $location->save();
    }

    public function deleteLocation(Location $location): void
    {
        $location->delete();
    }

}