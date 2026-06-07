<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function getDashboardStats($startDate, $endDate)
    {
        // 1. Chuẩn hóa thời gian (Bao trọn từ 00:00:00 ngày bắt đầu đến 23:59:59 ngày kết thúc)
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Kiểm tra xem khoảng thời gian lọc lớn hơn 60 ngày không (Để quyết định vẽ chart theo Ngày hay theo Tháng)
        $isGroupByMonth = $start->diffInDays($end) > 60;

        return [
            'overview'        => $this->getOverviewStats($start, $end),
            'revenue_chart'   => $this->getRevenueChart($start, $end, $isGroupByMonth),
            'top_tours'       => $this->getTopTours($start, $end),
            'payment_methods' => $this->getPaymentMethodStats($start, $end),
            'recent_bookings' => $this->getRecentBookings() // Không truyền ngày vì luôn lấy mới nhất
        ];
    }

    // ========================================================
    // CÁC HÀM NGHIỆP VỤ CHI TIẾT
    // ========================================================

    private function getOverviewStats($start, $end)
    {
        $bookingsInRange = Booking::whereBetween('booking_date', [$start, $end]);
        $OverviewStats = [
            // 1. NHÓM ĐÃ THANH TOÁN
            'paid_revenue' => (clone $bookingsInRange)->where('status', 'PAID')->sum('total_price'),
            'paid_count'   => (clone $bookingsInRange)->where('status', 'PAID')->count(),

            // 2. NHÓM CHỜ DUYỆT / CHỜ THANH TOÁN
            'pending_revenue' => (clone $bookingsInRange)->whereIn('status', ['PENDING', 'VERIFYING'])->sum('total_price'),
            'pending_count'   => (clone $bookingsInRange)->whereIn('status', ['PENDING', 'VERIFYING'])->count(),
        ];

        return $OverviewStats;

    }

    private function getRevenueChart($start, $end, $isGroupByMonth)
    {
        $dateFormat = $isGroupByMonth ? '%Y-%m' : '%Y-%m-%d';

        $chartData = Booking::whereBetween('booking_date', [$start, $end])
            ->whereIn('status', ['PAID'])
            ->select(
                DB::raw("DATE_FORMAT(booking_date, '{$dateFormat}') as time_label"),
                DB::raw('SUM(total_price) as revenue')
            )
            ->groupBy('time_label')
            ->orderBy('time_label', 'asc')
            ->get();

        return $chartData->map(function ($item) {
            return [
                'month' => $item->time_label, 
                'revenue' => (int) $item->revenue
            ];
        });
    }

    private function getTopTours($start, $end)
    {
        // Nối bảng Bookings -> TourSchedules -> Tours để đếm số đơn theo từng Tour
        DB::enableQueryLog();
        $topTours= DB::table('bookings')
            ->join('tour_schedules', 'bookings.schedule_id', '=', 'tour_schedules.schedule_id')
            ->join('tours', 'tour_schedules.tour_id', '=', 'tours.tour_id')
            ->whereBetween('bookings.booking_date', [$start, $end])
            ->where('bookings.status', 'PAID')
            ->select('tours.name', DB::raw('COUNT(bookings.booking_id) as bookings'))
            ->groupBy('tours.tour_id', 'tours.name')
            ->orderByDesc('bookings')
            ->limit(5)
            ->get();
        Log::info('Query Log:', DB::getQueryLog());
        return $topTours;
    }

    private function getPaymentMethodStats($start, $end)
    {
        return Payment::join('bookings', 'payments.booking_id', '=', 'bookings.booking_id')
            ->whereBetween('bookings.booking_date', [$start, $end])
            ->where('payments.payment_status', 'COMPLETED')

            ->whereNotNull('payments.payment_method')
            ->select('payments.payment_method as name', DB::raw('COUNT(*) as value'))
            ->groupBy('payments.payment_method')
            ->get();
    }

    private function getRecentBookings()
    {
        // Luôn lấy 5 giao dịch mới nhất bất chấp bộ lọc ngày
        return Booking::with(['schedule.tour'])
            ->orderByDesc('booking_date')
            ->limit(5)
            ->get();
    }
}
