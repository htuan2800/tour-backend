<?php

namespace App\Http\Controllers;

use App\Http\Requests\TourRequest;
use App\Http\Requests\TourScheduleRequest;
use App\Http\Requests\TourUpdateRequest;
use App\Http\Resources\TourResource;
use App\Http\Resources\TourScheduleResource;
use App\Services\TourScheduleService;
use Illuminate\Http\Request;

class TourScheduleController extends Controller
{
    private $tourScheduleService;

    public function __construct(TourScheduleService $tourScheduleService)
    {
        $this->tourScheduleService = $tourScheduleService;
    }

    public function index(Request $request, $tourId) // Nhận $tourId từ URL
    {
        $limit = $request->query('limit', 10);
        $status=$request->query('status', 'ALL');
        $timeline = $request->query('timeline', 'ALL');
        $paginator = $this->tourScheduleService->getPaginatedSchedulesByTour($tourId, $limit, $status, $timeline);

        return $this->success(
            [
                // SỬA LỖI: item() -> items()
                'data' => $paginator->items(),
                'meta' => [
                    'totalItems'   => $paginator->total(),
                    'itemCount'    => $paginator->count(), // Dùng count() của Collection trang hiện tại      
                    'itemsPerPage' => $paginator->perPage(),
                    'totalPages'   => $paginator->lastPage(),
                    'currentPage'  => $paginator->currentPage(),
                ],
            ],
            'Lấy danh sách lịch trình thành công',
            200
        );
    }

    public function createTourSchedule(TourScheduleRequest $request)
    {
        $data = $request->validated();
        $tour = $this->tourScheduleService->createTourSchedule($data);
        return $this->success(new TourResource($tour), 'Tạo tour thành công', 201);
    }

    public function getTourScheduleByTourId(string $tourId)
    {
        $schedules = $this->tourScheduleService->findTourScheduleByTourId($tourId);
        return $this->success(TourScheduleResource::collection($schedules), 'Danh sách lịch trình của tour', 200);
    }

    public function getTourScheduleById(string $id)
    {
        $tour = $this->tourScheduleService->findTourScheduleById($id);
        return $this->success(new TourScheduleResource($tour), 'Thong tin tour', 200);
    }

    public function updateTourSchedule(TourScheduleRequest $request, string $id)
    {
        $data = $request->validated();
        $data['tour_id'] = $id;
        $tour = $this->tourScheduleService->updateTourSchedule($id, $data);
        return $this->success(new TourResource($tour), 'Cap nhat tour thanh cong', 200);
    }

    public function changeStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:OPEN,CLOSED,CANCELLED,COMPLETED'
        ]);
        $schedule = $this->tourScheduleService->findTourScheduleById($id);

        $newStatus = $request->input('status');
        $this->tourScheduleService->updateStatus($schedule, $newStatus);

        return $this->success(new TourScheduleResource($schedule), 'Cập nhật trạng thái thành công', 200);
    }
}
