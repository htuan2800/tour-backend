<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId = DB::table('roles')->insertGetId([
            'name' => 'ADMIN', 
            'display_name' => 'Quản trị viên cấp cao'
        ]);

        $customerId = DB::table('roles')->insertGetId([
            'name' => 'CUSTOMER', 
            'display_name' => 'Khách hàng'
        ]);

        $managerId = DB::table('roles')->insertGetId([
            'name' => 'MANAGER', 
            'display_name' => 'Quản lý'
        ]);
        
        // ADMIN: Lấy tất cả quyền gắn vào
        $allPermissions = DB::table('permissions')->pluck('permission_id');
        foreach ($allPermissions as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $adminId,
                'permission_id' => $permId
            ]);
        }
    }
}
