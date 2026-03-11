<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class TourScheduleService
{
    public function getPaginatedSchedulesByTour($tourId, $limit, $filterType)
    {
        $tour = Tour::findOrFail($tourId);

        $query = $tour->schedules();

        if ($filterType === 'upcoming') {
            $query->where('departure_date', '>=', now())
                ->orderBy('departure_date', 'asc');
        } elseif ($filterType === 'history') {
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
            ->get();
    }


    public function findTourScheduleById(string $id)
    {
        return TourSchedule::with(['tour'])
            ->findOrFail($id);
    }

    // private function checkGuideAvailability($guideId, $start, $end, $ignoreId = null)
    // {
    //     $isBusy = TourSchedule::where('guide_id', $guideId)
    //         // Nếu đang update (có ignoreId), thì loại bỏ record đó ra khỏi query
    //         ->when($ignoreId, function ($q) use ($ignoreId) {
    //             $q->where('schedule_id', '!=', $ignoreId);
    //         })
    //         // Kiểm tra chồng lấn thời gian (Overlap Logic chuẩn)
    //         ->where(function ($query) use ($start, $end) {
    //             // Logic chuẩn để check 2 khoảng thời gian có chạm nhau không:
    //             // (Ngày đi cũ < Ngày về mới) VÀ (Ngày về cũ > Ngày đi mới)
    //             $query->where('departure_date', '<', $end)
    //                 ->where('return_date', '>', $start);
    //         })
    //         // Loại bỏ các tour đã bị Hủy (quan trọng)
    //         ->where('status', '!=', 'CANCELLED')
    //         ->exists();

    //     if ($isBusy) {
    //         throw new Exception("Nhân viên này đã có lịch đi tour khác trong khoảng thời gian này!");
    //     }
    // }

    // private function checkGuideAvailabilityForUpdate($guideId, $start, $end)
    // {
    //     $isBusy = TourSchedule::where('guide_id', $guideId)
    //         ->where(function ($query) use ($start, $end) {
    //             // Kiểm tra xem khoảng thời gian mới có chồng lấn với lịch cũ không
    //             $query->whereBetween('departure_date', [$start, $end])
    //                 ->orWhereBetween('return_date', [$start, $end]);
    //         })
    //         ->exists();

    //     if ($isBusy) {
    //         throw new Exception("Nhân viên này đã có lịch đi tour khác trong khoảng thời gian này!");
    //     }
    // }

    public function createTourSchedule(array $data)
    {
        $tour = Tour::findOrFail($data['tour_id']);

        $departureDate = Carbon::parse($data['departure_date']);

        $returnDate = $departureDate->copy()->addDays($tour->duration_days - 1);

        return DB::transaction(function () use ($data, $departureDate, $returnDate) {

            // $this->checkGuideAvailability($data['guide_id'], $departureDate, $returnDate);

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

        $schedule->status = $newStatus;
        $schedule->save();
    }

    public function deleteTour(Tour $tour): void
    {
        $tour->delete();
    }
}
