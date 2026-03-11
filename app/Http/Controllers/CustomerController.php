<?php
namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index (Request $request)
    {
        $limit  = $request->input('limit', 10);
        $search = $request->input('search');

        // 2. Gọi Service để lấy cục dữ liệu phân trang (Paginator)
        $paginator = $this->customerService->getPagenatedCustomer($limit, $search);

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
            'Danh sách khach hang',
            200
        ) ;
    }

    public function createCustomer (UserRequest $request)
    {
        $data = $request->validated();

        $user = $this->customerService->createCustomer($data);

        return $this->success($user, 'Tạo người dùng thành công', 201);
    }
    
    public function updateCustomer(UserUpdateRequest $request, string $id) {
        $data = $request->validated();
        $data['user_id'] = $id;
        $customer = $this->customerService->updateCustomer($id, $data);
        return $this->success(new UserResource($customer), 'Cap nhat khach hang thanh cong', 200);
    }

    public function changeStatusCustomer(string $id) {
        $customer = $this->customerService->findCustomerById($id);
        $this->customerService->toggleStatusCustomer($customer);
        return $this->success(new UserResource($customer), 'Doi trang thai khach hang thanh cong', 200);
    }

    public function deleteCustomer(string $id) {
        $customer = $this->customerService->findCustomerById($id);
        $this->customerService->deleteCustomer($customer);
        return $this->success(new UserResource($customer), 'Xoa khach hang thanh cong', 200);
    }
}