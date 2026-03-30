<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Http\Requests\BookingUpdateRequest;
use App\Http\Resources\BookingDetailResource;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
class BookingController extends Controller
{
   protected $bookingService;

    // Inject Service vào Controller
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request) {
        $limit = $request->query('limit', 10);
        $search = $request->query('search', '');
        $status=$request->query('status', 'ALL');
        $paginator = $this->bookingService->getPagenatedBooking($limit, $search, $status);
        return $this->success(
            [
                'data' => BookingResource::collection($paginator),
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

    public function getUserBookings(Request $request)
    {
        // 1. Thu thập dữ liệu cần thiết từ Request
        $userId = Auth::user()->user_id; 
        $filters = $request->only(['status', 'search']);

        // 2. Gọi Service xử lý logic
        $bookings = $this->bookingService->getUserBookings($userId, $filters);

        // 3. Trả về thông qua Resource (tự động kèm theo meta phân trang của Laravel)
        return $this->success(
            [
                'data' => BookingResource::collection($bookings)->resolve(),
                'meta' => [
                    'totalItems'   => $bookings->total(),       
                    'itemCount'    => count($bookings->items()),      
                    'itemsPerPage' => $bookings->perPage(),     
                    'totalPages'   => $bookings->lastPage(),   
                    'currentPage'  => $bookings->currentPage(), 
                ],
            ],
            'Danh sách tour',
            200
        ) ;
    }

    public function getBookingDetail(string $id) {
        $booking = $this->bookingService->findBookingById($id);
        return $this->success(new BookingDetailResource($booking), 'Thông tin booking', 200);
    }

    public function getBookingDetailForCustomer(string $id) {
        $userId = Auth::user()?->user_id; 
        if ($userId == null) {
            return $this->error('Vui lớng đăng nhập', 401);
        }
        $booking = $this->bookingService->getBookingDetailForCustomer($id, $userId);
        if ($booking == null) {
            return $this->error('Đơn hàng không tồn tại hoặc không có quyền truy cập', 404);
        }
        return $this->success(new BookingDetailResource($booking), 'Thông tin booking', 200);
    }

    public function create(BookingRequest $request)
    {
        try {
            $data = $request->validated();
            $result = $this->bookingService->createBooking($data);

            // Trả về cho Frontend cái link payUrl để React dùng window.location.href chuyển trang
            return $this->success($result, 'Thanh toán thành công', 200);
        } catch (Exception $e) {
            return $this->error('Thanh toán thất bại: ' . $e->getMessage(), 500);
        }
    }

    public function update(BookingUpdateRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $result = $this->bookingService->updateBooking($id, $data);

            return $this->success($result, 'Cập nhật booking thành công', 200);
        } catch (Exception $e) {
            return $this->error('Cập nhật booking thất bại: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:VERIFYING,PAID,CANCELLED,EXPIRED'
        ]);

        $newStatus = $request->input('status');
        $booking = $this->bookingService->updateStatus($id, $newStatus);

        return $this->success(new BookingResource($booking), 'Cập nhật trạng thái thành công', 200);
    }

    public function CancelBooking(string $id)
    {
        $booking = $this->bookingService->updateStatus($id, 'CANCELLED');
        return $this->success(new BookingResource($booking), 'Cập nhật trạng thái thành công', 200);
    }
}
