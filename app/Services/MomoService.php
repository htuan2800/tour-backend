<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

class MomoService
{
    public function createPayment($amount, $orderId)
    {
        // 1. Lấy cấu hình từ file .env hoặc config
        $endpoint    = config('momo.endpoint');
        $partnerCode = config('momo.partner_code');
        $accessKey   = config('momo.access_key');
        $secretKey   = config('momo.secret_key');
        $ipnUrl      = config('momo.ipn_url');
        $redirectUrl = config('momo.redirect_url');
        $requestType = config('momo.request_type'); // Hoặc payWithATM, tùy cấu hình của bạn
        
        $extraData = "";
        $orderInfo = "Thanh toán giao dịch: " . $orderId;
        $amountStr = (string) $amount; // MoMo yêu cầu amount là chuỗi số nguyên
        $requestId = (string) Str::uuid(); // Tự động tạo UUID

        // 2. Tạo chuỗi rawData theo đúng thứ tự alphabet
        $rawData = "accessKey=$accessKey"
            . "&amount=$amountStr"
            . "&extraData=$extraData"
            . "&ipnUrl=$ipnUrl"
            . "&orderId=$orderId"
            . "&orderInfo=$orderInfo"
            . "&partnerCode=$partnerCode"
            . "&redirectUrl=$redirectUrl"
            . "&requestId=$requestId"
            . "&requestType=$requestType";

        // 3. Tạo chữ ký (Signature) bằng HMAC SHA256
        $signature = hash_hmac('sha256', $rawData, $secretKey);

        // 4. Chuẩn bị payload
        $requestBody = [
            'partnerCode' => $partnerCode,
            'requestId'   => $requestId,
            'amount'      => $amountStr,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl'      => $ipnUrl,
            'lang'        => 'vi',
            'extraData'   => $extraData,
            'requestType' => $requestType,
            'signature'   => $signature
        ];

        // 5. Gửi request bằng Laravel HTTP Client cực kỳ gọn nhẹ
        $response = Http::post($endpoint, $requestBody);

        $result = $response->json();

        // 6. Xử lý kết quả trả về
        if ($response->successful() && isset($result['payUrl'])) {
            return $result['payUrl'];
        }

        // Báo lỗi nếu MoMo từ chối (VD: Sai chữ ký, sai số tiền...)
        Log::error('MoMo Payment Error: ', $result);
        throw new Exception("Lỗi khởi tạo thanh toán MoMo: " . ($result['message'] ?? 'Unknown Error'));
    }
}