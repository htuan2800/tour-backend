<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            ['name' => 'Quản lý Khách hàng', 'code' => 'customer'],
            ['name' => 'Quản lý Nhân viên', 'code' => 'staff'],
            ['name' => 'Quản lý địa điểm', 'code' => 'location'],
            ['name' => 'Quản lý Tour', 'code' => 'tour'],
            ['name' => 'Quản lý Tour lịch trình', 'code' => 'tour_schedule'],
            ['name' => 'Quản lý mã giảm giá', 'code' => 'coupon'],
            ['name' => 'Quản lý Đơn hàng', 'code' => 'booking'],
            ['name' => 'Thống kê', 'code' => 'dashboard'],
            ['name' => 'Phân quyền', 'code' => 'role'],
        ];

        DB::table('features')->insert($features);
    }
}
