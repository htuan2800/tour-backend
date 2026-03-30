<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCurrentRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index (Request $request)
    {
        $limit  = $request->input('limit', 10);
        $search = $request->input('search');

        // 2. Gọi Service để lấy cục dữ liệu phân trang (Paginator)
        $paginator = $this->userService->getPaginatedUsers($limit, $search);

        return response()->json([
            'data' => UserResource::collection($paginator), // Mảng dữ liệu chính
            'meta' => [
                'totalItems'   => $paginator->total(),       // Tổng số bản ghi trong DB
                'itemCount'    => count($paginator->items()),       // Số bản ghi của trang hiện tại
                'itemsPerPage' => $paginator->perPage(),     // Limit
                'totalPages'   => $paginator->lastPage(),    // Tổng số trang
                'currentPage'  => $paginator->currentPage(), // Trang hiện tại
            ],
        ]);
    }

    public function getUserById (string $id)
    {
        $user = $this->userService->findUserById($id);
        return $this->success(new UserResource($user), 'Lay nguoi dung thanh cong', 200);
    }
    

    public function createUser (UserRequest $request)
    {
        $data = $request->validated();

        $user = $this->userService->createUser($data);

        return $this->success($user, 'Tạo người dùng thành công', 201);
    }

    public function updateCurrtentUser (UserCurrentRequest $request)
    {
        $data = $request->validated();

        $user = $this->userService->updateUser($data);

        return $this->success($user, 'Cap nhat nguoi dung thanh cong', 200);
    }
}