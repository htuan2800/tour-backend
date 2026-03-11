<?php
namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Http\Resources\LocationResource;
use App\Services\LocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function getAllLocation () {
        $location = $this->locationService->getAllLocation();
        return $this->success(LocationResource::collection($location), 'Danh sach diem den', 200);
    }

    public function getPopularLocations(Request $request)
    {
        $region = $request->query('region');
        $locations = $this->locationService->getPopularLocations($region);
        return $this->success(LocationResource::collection($locations)->resolve(), 'Danh sách điểm đến phổ biến', 200);
    }

    public function index (Request $request)
    {
        $limit  = $request->input('limit', 10);
        $search = $request->input('search');
        $paginator = $this->locationService->getPagenatedLocation($limit, $search);

        return $this->success(
            [
                'data' => LocationResource::collection($paginator),
                'meta' => [
                    'totalItems'   => $paginator->total(),       
                    'itemCount'    => count($paginator->items()),      
                    'itemsPerPage' => $paginator->perPage(),     
                    'totalPages'   => $paginator->lastPage(),   
                    'currentPage'  => $paginator->currentPage(), 
                ],
            ],
            'Danh sách điểm đến',
            200
        ) ;
    }

    public function getLocationById (string $id) {
        $location = $this->locationService->findLocationById($id);
        return $this->success(new LocationResource($location), 'Thong tin diem den', 200);
    }

    public function createLocation (LocationRequest $request)
    {
        $data = $request->validated();

        $location = $this->locationService->createLocation($data);

        return $this->success(new LocationResource($location), 'Tạo điểm đến thành công', 201);
    }

    public function updateLocation(LocationUpdateRequest $request, string $id) {
        $data = $request->validated();
        $data['location_id'] = $id;
        $location = $this->locationService->updateLocation($id, $data);
        return $this->success(new LocationResource($location), 'Cap nhat diem den thanh cong', 200);
    }

    public function changeStatusLocation(string $id) {
        $location = $this->locationService->findLocationById($id);
        $this->locationService->toggleStatusLocation($location);
        return $this->success(new LocationResource($location), 'Doi trang thai diem den thanh cong', 200);
    }

    public function deleteLocation(string $id) {
        $location = $this->locationService->findLocationById($id);
        $this->locationService->deleteLocation($location);
        return $this->success(new LocationResource($location), 'Xoa diem den thanh cong', 200);
    }
}