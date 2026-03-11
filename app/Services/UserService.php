<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function getPaginatedUsers(int $limit, ?string $search, $roleFilter = null): LengthAwarePaginator
    {
        $query = User::query();

        // Eager Load để lát nữa lấy ra hiển thị cho nhanh (tránh N+1 query)
        $query->with(['staff', 'customer']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                // 1. Tìm trong bảng USERS (chỉ có email)
                $q->where('email', 'LIKE', "%{$search}%")

                    // 2. Tìm trong quan hệ STAFF (nếu user là nhân viên)
                    ->orWhereHas('staff', function ($qStaff) use ($search) {
                        $qStaff->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%");
                    })

                    // 3. Tìm trong quan hệ CUSTOMER (nếu user là khách)
                    ->orWhereHas('customer', function ($qCustomer) use ($search) {
                        $qCustomer->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Filter Role (nếu có)
        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        return $query->orderBy('user_id', 'DESC')->paginate($limit);
    }
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findUserById(string $id): ?User
    {
        return User::where('user_id', $id)->first();
    }


    public function toggleStatusUser(User $user): void
    {
        $user->is_active = true;
        $user->save();
    }

    public function createUser(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Tạo User chính (Account)
            $user = User::create([
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role'      => $data['role'],
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            if($user->role === 'CUSTOMER') {
                $user->customer()->create([
                    'user_id' => $user->id,
                    'full_name' => $data['full_name'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'address' => $data['address'] ?? '',
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                ]);
            } else {
                $user->staff()->create([
                    'user_id' => $user->id,
                    'full_name' => $data['full_name'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'address' => $data['address'] ?? '',
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                ]);
            }

            return $user;
        });
    }

    public function activeUser(User $user): void
    {
        $user->is_active = true;
        $user->save();
    }
}
