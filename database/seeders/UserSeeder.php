<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'admin@gmail.com',
            'password' => Hash::make('28102004'),
            'role_id' => 1,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'email' => 'huynhngoctuan48@gmail.com',
            'password' => Hash::make('28102004'),
            'role_id' => 2,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user2->customer()->create([
            'full_name' => 'Huỳnh Ngọc Tuấn', 
            'phone' => '0938124402',
            'address' => 'Biên Hòa, Đồng Nai',
            'date_of_birth' => '2004-10-28',
        ]);
    }
}
