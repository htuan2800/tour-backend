<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    private $cloudinaryService;
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }
    public function getPagenatedCustomer(int $limit, ?string $search, $roleFilter = null)
    {
        $query = User::query()
            ->with(['customer', 'role'])
            ->whereHas('role', function ($q) {
                $q->where('name', 'CUSTOMER');
            });

        // Logic tìm kiếm (Search)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhereHas('customer', function ($qCustomer) use ($search) {
                    $qCustomer->where('phone', 'LIKE', "%{$search}%")
                                ->orWhere('full_name', 'LIKE', "%{$search}%");
                });
            });
        }

        return $query->orderBy('user_id', 'DESC')->paginate($limit);
    }


    public function findCustomerById(string $id)
    {
        return User::where('user_id', $id)
            ->whereHas('customer')
            ->with('customer')
            ->first();
    }

    public function createCustomer(array $data)
    {
        return DB::transaction(function () use ($data) {
            $customerRole = Role::where('name', 'CUSTOMER')->firstOrFail();
            $user = User::create([
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role_id'      => $customerRole->role_id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $user->customer()->create([
                'user_id' => $user->id,
                'full_name' => $data['full_name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'address' => $data['address'] ?? '',
                'date_of_birth' => $data['date_of_birth'] ?? null
            ]);
            return $user;
        });
    }

    public function updateCustomer(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $user = User::where('user_id', $id)->firstOrFail();
            $user->update(
                [
                    'email' => $data['email'],
                    'password' => isset($data['password']) ? $data['password'] : $user->password,
                ]
            );
            $user->customer()->update([
                'full_name' => $data['full_name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'address' => $data['address'] ?? '',
                'date_of_birth' => $data['date_of_birth'] ?? null,
            ]);
            return $user;
        });
    }

    public function toggleStatusCustomer(User $user): void
    {
        $user->is_active = !$user->is_active;
        $user->save();
    }

    public function deleteCustomer(User $user): void
    {
        $user->delete();
    }
}
