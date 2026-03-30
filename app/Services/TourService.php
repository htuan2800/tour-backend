<?php

namespace App\Services;

use App\Models\Tour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TourService
{
    private $cloudinaryService;
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function searchToursByAdmin(array $filters, $sortBy = 'nearest_date', $perPage = 10)
    {

        $query = Tour::with(['destinations', 'depart', 'schedules' => function ($q) {
            $q->where('departure_date', '>=', now())->orderBy('departure_date', 'asc')
                ->where('status', 'OPEN');
        }])->where('is_active', true);


        // 2. LỌC ĐIỂM ĐẾN (Dùng whereHas vì là quan hệ Many-to-Many)
        if (!empty($filters['destination_id'])) {
            $query->whereHas('destinations', function ($q) use ($filters) {
                // Lọc các tour có chứa destination_id này trong bảng trung gian
                $q->where('tour_destinations.destination_id', $filters['destination_id']);
            });
        }

        // 3. LỌC NƠI KHỞI HÀNH (Sửa lại tên cột thành depart_id)
        if (!empty($filters['departure_id']) && $filters['departure_id'] !== 'all') {
            $query->where('depart_id', $filters['departure_id']);
        }

        // Lọc qua bảng Schedules (Ngày đi & Giá)
        if (!empty($filters['departure_date']) || !empty($filters['price_range'])) {
            $query->whereHas('schedules', function ($q) use ($filters) {

                // Lọc theo ngày khởi hành
                if (!empty($filters['departure_date'])) {
                    $date = Carbon::parse($filters['departure_date'])->format('Y-m-d');
                    $q->whereDate('departure_date', $date);
                }

                // Lọc theo khoảng giá (price_range)
                if (!empty($filters['price_range'])) {
                    switch ($filters['price_range']) {
                        case 'under_5':
                            $q->where('price_adult', '<', 5000000);
                            break;
                        case '5_to_10':
                            $q->whereBetween('price_adult', [5000000, 10000000]);
                            break;
                        case '10_to_20':
                            $q->whereBetween('price_adult', [10000000, 20000000]);
                            break;
                        case 'over_20':
                            $q->where('price_adult', '>', 20000000);
                            break;
                    }
                }
            });
        }

        // Sắp xếp
        switch ($sortBy) {
            case 'price_asc':
                $query->withMin('schedules', 'price_adult')->orderBy('schedules_min_price_adult', 'asc');
                break;
            case 'price_desc':
                $query->withMin('schedules', 'price_adult')->orderBy('schedules_min_price_adult', 'desc');
                break;
            case 'nearest_date':
            default:
                $query->withMin('schedules', 'departure_date')->orderBy('schedules_min_departure_date', 'asc');
                break;
        }

        return $query->paginate($perPage);
    }

    public function searchTours(array $filters, $sortBy = 'nearest_date', $perPage = 10)
    {
        $regionMapping = [
            'mien-bac' => 'Northern',
            'mien-trung' => 'Central',
            'mien-dong-nam-bo' => 'Southeast',
            'mien-tay-nam-bo' => 'Southwest',
        ];

        $query = Tour::with(['destinations', 'depart', 'schedules' => function ($q) {
            $q->where('departure_date', '>=', now())->orderBy('departure_date', 'asc')
                ->where('status', 'OPEN');
        }])->where('is_active', true);

        if (!empty($filters['slug'])) {
            $slug = $filters['slug'];

            // Kiểm tra xem slug truyền vào có phải là Vùng Miền không?
            if (array_key_exists($slug, $regionMapping)) {
                
                // NẾU LÀ VÙNG MIỀN: Tìm các tour có Điểm đến thuộc vùng này
                $regionValue = $regionMapping[$slug]; 
                
                $query->whereHas('destinations', function ($q) use ($regionValue) {
                    // $q ở đây đại diện cho bảng locations
                    $q->where('region', $regionValue); 
                });

            } else {
                
                // NẾU LÀ ĐỊA ĐIỂM: Tìm các tour có Điểm đến khớp chính xác với slug này
                $query->whereHas('destinations', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                });

            }
        }

        // 2. LỌC ĐIỂM ĐẾN (Dùng whereHas vì là quan hệ Many-to-Many)
        if (!empty($filters['destination_id'])) {
            $query->whereHas('destinations', function ($q) use ($filters) {
                // Lọc các tour có chứa destination_id này trong bảng trung gian
                $q->where('tour_destinations.destination_id', $filters['destination_id']);
            });
        }

        // 3. LỌC NƠI KHỞI HÀNH (Sửa lại tên cột thành depart_id)
        if (!empty($filters['departure_id']) && $filters['departure_id'] !== 'all') {
            $query->where('depart_id', $filters['departure_id']);
        }

        // Lọc qua bảng Schedules (Ngày đi & Giá)
        if (!empty($filters['departure_date']) || !empty($filters['price_range'])) {
            $query->whereHas('schedules', function ($q) use ($filters) {

                // Lọc theo ngày khởi hành
                if (!empty($filters['departure_date'])) {
                    $date = Carbon::parse($filters['departure_date'])->format('Y-m-d');
                    $q->whereDate('departure_date', $date);
                }

                // Lọc theo khoảng giá (price_range)
                if (!empty($filters['price_range'])) {
                    switch ($filters['price_range']) {
                        case 'under_5':
                            $q->where('price_adult', '<', 5000000);
                            break;
                        case '5_to_10':
                            $q->whereBetween('price_adult', [5000000, 10000000]);
                            break;
                        case '10_to_20':
                            $q->whereBetween('price_adult', [10000000, 20000000]);
                            break;
                        case 'over_20':
                            $q->where('price_adult', '>', 20000000);
                            break;
                    }
                }
            });
        }

        // Sắp xếp
        switch ($sortBy) {
            case 'price_asc':
                $query->withMin('schedules', 'price_adult')->orderBy('schedules_min_price_adult', 'asc');
                break;
            case 'price_desc':
                $query->withMin('schedules', 'price_adult')->orderBy('schedules_min_price_adult', 'desc');
                break;
            case 'nearest_date':
            default:
                $query->withMin('schedules', 'departure_date')->orderBy('schedules_min_departure_date', 'asc');
                break;
        }

        return $query->paginate($perPage);
    }

    public function getPagenatedTour(int $limit, ?string $search)
    {
        $query = Tour::query()->with(['destinations', 'depart', 'itineraries', 'schedules']);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('tour_id', 'DESC')->paginate($limit);
    }

    public function findTourById(string $id)
    {
        return Tour::with(['destinations', 'depart', 'itineraries', 'schedules'])
            ->findOrFail($id);
    }

    public function findTourForCustomerById(string $id)
    {
        return Tour::query()
            ->where('is_active', true)
            ->with([
                // 1. SỬA: Lấy danh sách các điểm đến (Array)
                'destinations',

                // 2. Điểm khởi hành (Object - vẫn giữ nguyên vì chỉ có 1 điểm đi)
                'depart',

                // 3. Lịch trình chi tiết (Sắp xếp theo ngày)
                'itineraries' => function ($query) {
                    $query->orderBy('day_number', 'asc');
                },

                // 4. Lịch khởi hành (Chỉ lấy cái sắp tới & đang mở bán)
                'schedules' => function ($query) {
                    $query->where('departure_date', '>=', now())
                        ->where('status', 'OPEN')
                        ->orderBy('departure_date', 'asc');
                },

            ])
            ->findOrFail($id);
    }

    public function createTour(array $data)
    {
        return DB::transaction(function () use ($data) {
            $tour = Tour::create([
                'name'        => $data['name'],
                'image_url'   => $data['image_url'],
                'description' => $data['description'],
                'duration_days'   => $data['duration_days'],
                'duration_nights' => $data['duration_nights'],
                'transportation' => $data['transportation'],
                'depart_id'      => $data['depart_id'],
                'is_active'   => true,
            ]);
            $tour->itineraries()->createMany($data['itineraries']);
            $tour->destinations()->sync($data['destination_ids']);
            return $tour->load(['destinations', 'depart', 'itineraries']);
        });
    }


    public function updateTour(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $tour = Tour::where('tour_id', $id)->firstOrFail();
            if (!empty($data['image_url']) && $tour->image_url) {
                try {
                    $this->cloudinaryService->deleteFile($tour->image_url);
                } catch (\Exception $e) {
                    throw new \Exception("Lỗi: " . $e->getMessage());
                }
            }
            $hasBooking = $tour->schedules()->whereHas('bookings')->exists();
            if ($hasBooking) {
                $tour->update(
                    [
                        // 'name' => $data['name'],
                        'description' => $data['description'],
                        'image_url' => isset($data['image_url']) ? $data['image_url'] : $tour->image_url,
                    ]
                );
            } else {
                $tour->update(
                    [
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'image_url' => isset($data['image_url']) ? $data['image_url'] : $tour->image_url,
                        'duration_days'   => $data['duration_days'],
                        'duration_nights' => $data['duration_nights'],
                        'transportation' => $data['transportation'],
                        'depart_id'      => $data['depart_id'],
                    ]
                );
            }

            if (isset($data['destination_ids'])) {
                $tour->destinations()->sync($data['destination_ids']);
            }

            if (isset($data['itineraries'])) {
                $tour->itineraries()->delete();

                $tour->itineraries()->createMany($data['itineraries']);
            }

            return $tour;
        });
    }

    public function toggleStatusTour(Tour $tour): void
    {
        $tour->is_active = !$tour->is_active;
        $tour->save();
    }

    public function deleteTour(Tour $tour): void
    {
        $tour->delete();
    }
}
