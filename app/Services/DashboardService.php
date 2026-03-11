<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        // Lấy các đơn hàng TRONG KỲ để tính Doanh thu, Số đơn, Số khách
        $bookingsInRange = Booking::whereBetween('booking_date', [$start, $end]);

        return [
            'total_revenue' => (clone $bookingsInRange)
                ->whereIn('status', ['PAID']) // Chỉ tính tiền đơn thành công
                ->sum('total_price'),

            'new_bookings'  => (clone $bookingsInRange)->count(),

            'total_passengers' => (clone $bookingsInRange)
                ->sum(DB::raw('number_of_adults + number_of_children')),

            // TIỀN CHỜ THU: Luôn lấy Real-time (Thời gian thực), bỏ qua bộ lọc ngày như đã thống nhất!
            'pending_revenue' => Booking::whereIn('status', ['PENDING', 'VERIFYING'])
                ->sum('total_price')
        ];
    }

    private function getRevenueChart($start, $end, $isGroupByMonth)
    {
        // Tùy biến format Group By (Theo tháng 'Y-m' hoặc theo ngày 'Y-m-d') 
        // LƯU Ý: Cú pháp DATE_FORMAT dành cho MySQL. Nếu dùng PostgreSQL thì phải đổi hàm khác.
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

        // Map lại data để đổi tên key cho khớp với Recharts ở Frontend
        return $chartData->map(function ($item) {
            return [
                'month' => $item->time_label, // React đang dùng chữ 'month', nếu thích bạn có thể đổi thành 'date'
                'revenue' => (int) $item->revenue
            ];
        });
    }

    private function getTopTours($start, $end)
    {
        // Nối bảng Bookings -> TourSchedules -> Tours để đếm số đơn theo từng Tour
        return DB::table('bookings')
            ->join('tour_schedules', 'bookings.schedule_id', '=', 'tour_schedules.schedule_id')
            ->join('tours', 'tour_schedules.tour_id', '=', 'tours.tour_id')
            ->whereBetween('bookings.booking_date', [$start, $end])
            ->select('tours.name', DB::raw('COUNT(bookings.booking_id) as bookings'))
            ->groupBy('tours.tour_id', 'tours.name')
            ->orderByDesc('bookings')
            ->limit(5)
            ->get();
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
        // Gọi kèm relationship `schedule.tour` để Frontend có tên tour in ra bảng
        return Booking::with(['schedule.tour'])
            ->orderByDesc('booking_date')
            ->limit(5)
            ->get();
    }
}
