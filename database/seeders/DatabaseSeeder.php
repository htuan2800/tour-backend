<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FeatureSeeder::class,      // 1. Tạo nhóm chức năng trước
            PermissionSeeder::class,    // 2. Tạo quyền và gắn vào chức năng
            RoleSeeder::class,          // 3. Tạo vai trò (Admin/Staff)
            UserSeeder::class,          // 4. Tạo User admin mặc định
        ]);
    }
}
