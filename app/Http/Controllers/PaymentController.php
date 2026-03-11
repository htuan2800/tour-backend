<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function checkout(PaymentRequest $request)
    {
        try {
            $data = $request->validated();
            $result = $this->paymentService->processBooking($data);

            // Trả về cho Frontend cái link payUrl để React dùng window.location.href chuyển trang
            return $this->success($result, 'Thanh toán thành công', 200);
        } catch (Exception $e) {
            return $this->error('Thanh toán thất bại: ' . $e->getMessage(), 500);
        }
    }

    public function momoIpn(Request $request)
    {
        try {
            $data = $request->all();

            $isSuccess = $this->paymentService->processIpn($data);

            if ($isSuccess) {
                // Trả về 204 No Content (MoMo yêu cầu trả về HTTP Status 200 hoặc 204 để xác nhận)
                return response()->noContent();
            }

            return response()->json(['message' => 'Lỗi xử lý IPN'], 400);
        } catch (\Exception $e) {
            Log::error('Lỗi Exception Momo IPN: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Error'], 500);
        }
    }

    public function checkStatus($orderId)
    {
        try {
            $status = $this->paymentService->checkPaymentStatus($orderId);
            return $this->success(['status' => $status], 'Trạng thái thanh toán', 200);
        } catch (Exception $e) {
            return $this->error('Lỗi khi kiểm tra trạng thái: ' . $e->getMessage(), 500);
        }
    }
}
