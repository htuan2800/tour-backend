<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\StaffService;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    protected $staffService;

    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }

    public function getAllStaff(Request $request)
    {
        $roleFilter = $request->input('role');
        $staffs = $this->staffService->getAllStaffs($roleFilter);
        return $this->success(UserResource::collection($staffs), 'Lấy danh sách nhân viên thành công');
    }

    public function index(Request $request)
    {
        $limit  = $request->input('limit', 10);
        $search = $request->input('search');

        // 2. Gọi Service để lấy cục dữ liệu phân trang (Paginator)
        $paginator = $this->staffService->getPagenatedStaff($limit, $search);

        return $this->success(
            [
                'data' => UserResource::collection($paginator), // Mảng dữ liệu chính
                'meta' => [
                    'totalItems'   => $paginator->total(),       // Tổng số bản ghi trong DB
                    'itemCount'    => count($paginator->items()),       // Số bản ghi của trang hiện tại
                    'itemsPerPage' => $paginator->perPage(),     // Limit
                    'totalPages'   => $paginator->lastPage(),    // Tổng số trang
                    'currentPage'  => $paginator->currentPage(), // Trang hiện tại
                ],
            ],
            'Danh sách nhan vien',
            200
        );
    }

    public function createStaff(UserRequest $request)
    {
        $data = $request->validated();

        $user = $this->staffService->createStaff($data);

        return $this->success($user, 'Tạo người dùng thành công', 201);
    }

    public function updateStaff(UserUpdateRequest $request, string $id)
    {
        $data = $request->validated();
        $data['user_id'] = $id;
        $staff = $this->staffService->updateStaff($id, $data);
        return $this->success(new UserResource($staff), 'Cap nhat nhan vien thanh cong', 200);
    }

    public function changeStatusStaff(string $id)
    {
        $staff = $this->staffService->findStaffById($id);
        $this->staffService->toggleStatusStaff($staff);
        return $this->success(new UserResource($staff), 'Doi trang thai nhan vien thanh cong', 200);
    }

    public function deleteStaff(string $id)
    {
        $staff = $this->staffService->findStaffById($id);
        $this->staffService->deleteStaff($staff);
        return $this->success(new UserResource($staff), 'Xoa nhan vien thanh cong', 200);
    }
}
