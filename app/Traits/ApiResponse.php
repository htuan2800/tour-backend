<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Phản hồi thành công (Success)
     *
     * @param mixed $data Dữ liệu trả về
     * @param string $message Thông báo kèm theo
     * @param int $statusCode Mã HTTP (mặc định 200)
     */
    public function success($data, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'statusCode' => $statusCode,
            'message'    => $message,
            'data'       => $data,
        ], $statusCode);
    }

    /**
     * Phản hồi lỗi (Error)
     *
     * @param string $message Thông báo lỗi
     * @param int $statusCode Mã HTTP (mặc định 400 hoặc 500)
     * @param mixed $data Dữ liệu lỗi chi tiết (nếu có)
     */
    public function error(string $message, int $statusCode = 400, $data = null): JsonResponse
    {
        return response()->json([
            'statusCode' => $statusCode,
            'message'    => $message,
            'data'       => $data,
        ], $statusCode);
    }
}