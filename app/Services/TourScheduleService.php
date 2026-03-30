<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class TourScheduleService
{
    protected $bookingService;

    // Nhúng BookingService thông qua Dependency Injection
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }
    public function getPaginatedSchedulesByTour($tourId, $limit, $status = 'ALL', $timeline = null)
    {
        // Tìm Tour để đảm bảo Tour tồn tại (nếu không có sẽ văng lỗi 404 ngay)
        $tour = Tour::findOrFail($tourId);

        // Bắt đầu build query từ relation schedules()
        $query = $tour->schedules();

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($timeline === 'UPCOMING') {
            $query->where('departure_date', '>=', now())
                ->orderBy('departure_date', 'asc');
        } elseif ($timeline === 'HISTORY') {
            $query->where('departure_date', '<', now())
                ->orderBy('departure_date', 'desc');
        } else {
            $query->orderBy('departure_date', 'desc');
        }

        return $query->paginate($limit);
    }

    public function findTourScheduleByTourId(string $tourId)
    {
        return TourSchedule::with(['tour'])
            ->where('tour_id', $tourId)
            ->where('departure_date', '>', now())
            ->orderBy('departure_date', 'asc')
            ->get();
    }


    public function findTourScheduleById(string $id)
    {
        return TourSchedule::with(['tour', 'bookings'])
            ->findOrFail($id);
    }

    public function createTourSchedule(array $data)
    {
        $tour = Tour::findOrFail($data['tour_id']);

        $departureDate = Carbon::parse($data['departure_date']);

        $isExist = TourSchedule::where('tour_id', $data['tour_id'])
            ->whereDate('departure_date', $departureDate->format('Y-m-d'))
            ->exists();

        if ($isExist) {
            throw new Exception('Tour này đã có lịch trình khởi hành vào ngày ' . $departureDate->format('d/m/Y') . '!');
        }

        $returnDate = $departureDate->copy()->addDays($tour->duration_days - 1);

        return DB::transaction(function () use ($data, $departureDate, $returnDate) {

            return TourSchedule::create([
                'tour_id'        => $data['tour_id'],
                // 'guide_id'       => $data['guide_id'],

                // Thời gian
                'departure_date' => $departureDate,
                'return_date'    => $returnDate,

                'price_adult'    => $data['price_adult'],
                'price_child'    => $data['price_child'],
                'max_capacity'   => $data['max_capacity'],

                'current_booked' => 0,
                'status'         => 'OPEN',
            ]);
        });
    }



    public function updateTourSchedule(string $id, array $data)
    {
        $schedule = TourSchedule::findOrFail($id);
        $tour = $schedule->tour;

        $departureDate = Carbon::parse($data['departure_date']);
        $returnDate = $departureDate->copy()->addDays($tour->duration_days - 1);

        return DB::transaction(function () use ($schedule, $data, $departureDate, $returnDate) {

            // $this->checkGuideAvailability($data['guide_id'], $departureDate, $returnDate, $schedule->schedule_id);

            $schedule->update([
                'departure_date' => $departureDate,
                'return_date'    => $returnDate,
                'price_adult'    => $data['price_adult'],
                'price_child'    => $data['price_child'],
                'max_capacity'   => $data['max_capacity'],
            ]);

            return $schedule;
        });
    }

    public function updateStatus(TourSchedule $schedule, string $newStatus): void
    {
        if ($schedule->status === 'CANCELLED') {
            throw new \Exception("Tour này đã bị Hủy, không thể thay đổi trạng thái được nữa!");
        }

        if ($schedule->status === 'COMPLETED') {
            throw new \Exception("Tour đã hoàn thành, không thể sửa!");
        }

        DB::transaction(function () use ($schedule, $newStatus) {
            if ($newStatus === 'CANCELLED') {

                $bookings = $schedule->bookings()
                    ->whereNotIn('status', ['CANCELLED'])
                    ->get();
                

                foreach ($bookings as $booking) {
                    $this->bookingService->updateStatus($booking->booking_id, 'CANCELLED');
                }
            }

            $schedule->status = $newStatus;
            $schedule->save();
        });
    }

    public function deleteTour(Tour $tour): void
    {
        $tour->delete();
    }
}
