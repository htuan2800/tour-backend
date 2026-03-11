<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tourFunc = DB::table('features')->where('code', 'tour')->first()->feature_id;
        $couponFunc = DB::table('features')->where('code', 'coupon')->first()->feature_id;
        $customerFunc = DB::table('features')->where('code', 'customer')->first()->feature_id;
        $staffFunc = DB::table('features')->where('code', 'staff')->first()->feature_id;
        $dashboardFunc = DB::table('features')->where('code', 'dashboard')->first()->feature_id;
        $bookingFunc = DB::table('features')->where('code', 'booking')->first()->feature_id;
        $tourScheduleFunc = DB::table('features')->where('code', 'tour_schedule')->first()->feature_id;
        $locationFunc = DB::table('features')->where('code', 'location')->first()->feature_id;
        $roleFunc = DB::table('features')->where('code', 'role')->first()->feature_id;

        $permissions = [
             // Quyền Khách hàng
            ['name' => 'customer.view', 'display_name' => 'Xem Khách hàng', 'feature_id' => $customerFunc],
            ['name' => 'customer.create', 'display_name' => 'Tạo Khách hàng', 'feature_id' => $customerFunc],
            ['name' => 'customer.update', 'display_name' => 'Chình sửa Khách hàng', 'feature_id' => $customerFunc],
            ['name' => 'customer.delete', 'display_name' => 'Xóa Khách hàng', 'feature_id' => $customerFunc],
            ['name' => 'customer.status', 'display_name' => 'Thay đổi trạng thái Khách hàng', 'feature_id' => $customerFunc],

            // Quyền Nhân viên
            ['name' => 'staff.view', 'display_name' => 'Xem Nhân viên', 'feature_id' => $staffFunc],
            ['name' => 'staff.create', 'display_name' => 'Tạo Nhân viên', 'feature_id' => $staffFunc],
            ['name' => 'staff.update', 'display_name' => 'Chình sửa Nhân viên', 'feature_id' => $staffFunc],
            ['name' => 'staff.delete', 'display_name' => 'Xóa Nhân viên', 'feature_id' => $staffFunc],
            ['name' => 'staff.status', 'display_name' => 'Thay đổi trạng thái Nhân viên', 'feature_id' => $staffFunc],
            
            //Quyền đặt địa điểm
            ['name' => 'location.view', 'display_name' => 'Xem địa điểm', 'feature_id' => $locationFunc],
            ['name' => 'location.create', 'display_name' => 'Tạo địa điểm', 'feature_id' => $locationFunc],
            ['name' => 'location.update', 'display_name' => 'Chình sửa địa điểm', 'feature_id' => $locationFunc],
            ['name' => 'location.delete', 'display_name' => 'Xóa địa điểm', 'feature_id' => $locationFunc],
            ['name' => 'location.status', 'display_name' => 'Thay đổi trạng thái địa điểm', 'feature_id' => $locationFunc],

            // Quyền Tour
            ['name' => 'tour.view', 'display_name' => 'Xem Tour', 'feature_id' => $tourFunc],
            ['name' => 'tour.create', 'display_name' => 'Tạo Tour', 'feature_id' => $tourFunc],
            ['name' => 'tour.update', 'display_name' => 'Chỉnh sửa Tour', 'feature_id' => $tourFunc],
            ['name' => 'tour.delete', 'display_name' => 'Xóa Tour', 'feature_id' => $tourFunc],
            ['name' => 'tour.status', 'display_name' => 'Thay đổi trạng thái Tour', 'feature_id' => $tourFunc],

            // Quyền Tour lịch trình
            ['name' => 'tour_schedule.view', 'display_name' => 'Xem Tour lịch trình', 'feature_id' => $tourScheduleFunc],
            ['name' => 'tour_schedule.create', 'display_name' => 'Tạo Tour lịch trình', 'feature_id' => $tourScheduleFunc],
            ['name' => 'tour_schedule.update', 'display_name' => 'Chình sửa Tour lịch trình', 'feature_id' => $tourScheduleFunc],
            ['name' => 'tour_schedule.status', 'display_name' => 'Thay đổi trạng thái Tour lịch trình', 'feature_id' => $tourScheduleFunc],
            
            // Quyền Coupon
            ['name' => 'coupon.view', 'display_name' => 'Xem Coupon', 'feature_id' => $couponFunc],
            ['name' => 'coupon.create', 'display_name' => 'Tạo Coupon', 'feature_id' => $couponFunc],
            ['name' => 'coupon.update', 'display_name' => 'Chình sửa Coupon', 'feature_id' => $couponFunc],
            ['name' => 'coupon.delete', 'display_name' => 'Xóa Coupon', 'feature_id' => $couponFunc],
            ['name' => 'coupon.status', 'display_name' => 'Thay đổi trạng thái Coupon', 'feature_id' => $couponFunc],

            // Quyền Thống kê
            ['name' => 'dashboard.view', 'display_name' => 'Xem Thống kê', 'feature_id' => $dashboardFunc],

            // Quyền Đơn hàng
            ['name' => 'booking.view', 'display_name' => 'Xem Đơn hàng', 'feature_id' => $bookingFunc],
            ['name' => 'booking.create', 'display_name' => 'Tạo Đơn hàng', 'feature_id' => $bookingFunc],
            ['name' => 'booking.update', 'display_name' => 'Chỉnh sửa Đơn hàng', 'feature_id' => $bookingFunc],
            ['name' => 'booking.status', 'display_name' => 'Thay đổi trạng thái Đơn hàng', 'feature_id' => $bookingFunc],

            //Phân quyền
            ['name' => 'role.view', 'display_name' => 'Xem Phân quyền', 'feature_id' => $roleFunc],
            ['name' => 'role.create', 'display_name' => 'Tạo Phân quyền', 'feature_id' => $roleFunc],
            ['name' => 'role.update', 'display_name' => 'Chỉnh sửa Phân quyền', 'feature_id' => $roleFunc],
            ['name' => 'role.delete', 'display_name' => 'Xóa Phân quyền', 'feature_id' => $roleFunc],
        ];

        DB::table('permissions')->insert($permissions);
    }
}
