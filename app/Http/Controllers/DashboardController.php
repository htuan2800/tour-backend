<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected $dashboardService;

    // Dependency Injection: Bơm Service vào Controller
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getStats(Request $request)
    {
        // 1. Lấy ngày từ URL (?start_date=2026-03-01&end_date=2026-03-09)
        // Lỡ Frontend quên gửi, mình fallback về mặc định là 30 ngày qua
        $startDate = $request->query('from', now()->subDays(30)->toDateString());
        $endDate   = $request->query('to', now()->toDateString());

        // 2. Gọi Service lấy trọn bộ Data
        $data = $this->dashboardService->getDashboardStats($startDate, $endDate);

        // 3. Trả về JSON cho React
        return response()->json([
            'status' => 'success',
            'data'   => $data
        ], 200);
    }
}