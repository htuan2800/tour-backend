<?php

namespace App\Http\Controllers;

use App\Http\Requests\TourRequest;
use App\Http\Requests\TourUpdateRequest;
use App\Http\Resources\TourResource;
use App\Services\TourService;
use Illuminate\Http\Request;

class TourController extends Controller
{
    private $tourService;

    public function __construct(TourService $tourService)
    {
        $this->tourService = $tourService;
    }

    public function indexForCustomer(Request $request)
    {
        // Thu thập các bộ lọc từ Query Params của URL
        $filters = $request->only([
            'destination_id', 'departure_id', 'departure_date', 
            'price_range', 'category', 'transportation'
        ]);
        
        $sortBy = $request->input('sort_by', 'nearest_date');

        $tours = $this->tourService->searchTours($filters, $sortBy);

        return $this->success(
            [
                'data' => TourResource::collection($tours)->resolve(),
                'meta' => [
                    'totalItems'   => $tours->total(),
                    'currentPage'  => $tours->currentPage(),
                    'totalPages'   => $tours->lastPage(),
                ]
            ],
            'Lấy danh sách tour thành công',
            200
        );
    }

    public function index(Request $request) {
        $limit = $request->query('limit', 10);
        $search = $request->query('search', '');
        $paginator = $this->tourService->getPagenatedTour($limit, $search);
        return $this->success(
            [
                'data' => TourResource::collection($paginator),
                'meta' => [
                    'totalItems'   => $paginator->total(),       
                    'itemCount'    => count($paginator->items()),      
                    'itemsPerPage' => $paginator->perPage(),     
                    'totalPages'   => $paginator->lastPage(),   
                    'currentPage'  => $paginator->currentPage(), 
                ],
            ],
            'Danh sách tour',
            200
        ) ;
    }

    public function createTour(TourRequest $request) {
        $data = $request->validated();
        $tour= $this->tourService->createTour($data);
        return $this->success(new TourResource($tour), 'Tạo tour thành công', 201);
    }

    public function getTourById (string $id) {
        $tour = $this->tourService->findTourById($id);
        return $this->success(new TourResource($tour), 'Thong tin tour', 200);
    }

    public function getTourForCustomerById (string $id) {
        $tour = $this->tourService->findTourForCustomerById($id);
        return $this->success(new TourResource($tour), 'Thong tin tour', 200);
    }

    public function updateTour(TourRequest $request, string $id) {
        $data = $request->validated();
        $data['tour_id'] = $id;
        $tour = $this->tourService->updateTour($id, $data);
        return $this->success(new TourResource($tour), 'Cap nhat tour thanh cong', 200);
    }

    public function changeStatusTour(string $id) {
        $tour = $this->tourService->findTourById($id);
        $this->tourService->toggleStatusTour($tour);
        return $this->success(new TourResource($tour), 'Doi trang thai tour thanh cong', 200);
    }

    public function deleteTour(string $id) {
        $tour = $this->tourService->findTourById($id);
        $this->tourService->deleteTour($tour);
        return $this->success(new TourResource($tour), 'Xoa tour thanh cong', 200);
    }

}