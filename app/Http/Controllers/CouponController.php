<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponRequest;
use App\Http\Resources\CouponResource;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

     public function index(Request $request) {
        $limit = $request->query('limit', 10);
        $search = $request->query('search', '');
        $paginator = $this->couponService->getPagenatedCoupon($limit, $search);
        return $this->success(
            [
                'data' => CouponResource::collection($paginator),
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

    public function getCouponById (string $id) {
        $coupon = $this->couponService->findCouponById($id);
        return $this->success(new CouponResource($coupon), 'Thong tin ma giam gia', 200);
    }

    public function getCouponForPayment()
    {
        $coupons = $this->couponService->getCouponForPayment();
        return $this->success(CouponResource::collection($coupons), 'Danh sách mã giảm giá áp dụng cho thanh toán', 200);
    }

    public function createCoupon(CouponRequest $request)
    {
        $data = $request->validated();

        $user = $this->couponService->createCoupon($data);

        return $this->success($user, 'Tạo người dùng thành công', 201);
    }

    public function updateCoupon(CouponRequest $request, string $id)
    {
        $data = $request->validated();
        $data['coupon_id'] = $id;
        $coupon = $this->couponService->updateCoupon($id, $data);
        return $this->success(new CouponResource($coupon), 'Cap nhat ma giam gia thanh cong', 200);
    }

    public function changeStatusCoupon(string $id)
    {
        $coupon = $this->couponService->findCouponById($id);
        $this->couponService->toggleStatusCoupon($coupon);
        return $this->success(new CouponResource($coupon), 'Doi trang thai nhan vien thanh cong', 200);
    }

    public function deleteCoupon(string $id)
    {
        $coupon = $this->couponService->findCouponById($id);
        $this->couponService->deleteCoupon($coupon);
        return $this->success(new CouponResource($coupon), 'Xoa nhan vien thanh cong', 200);
    }
}
