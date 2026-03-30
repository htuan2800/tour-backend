<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class StaffService
{
    private $cloudinaryService;
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function getAllStaffs(?string $roleFilter = null)
    {
        $staffRoles = ['OFFICE_STAFF', 'GUIDE_STAFF'];

        $query = User::query()
            ->with('staff')
            ->where('is_active', true);

        if ($roleFilter) {
            if (in_array($roleFilter, $staffRoles)) {
                $query->where('role', $roleFilter);
            }
        } else {
            $query->whereIn('role', $staffRoles);
        }

        return $query->orderBy('created_at', 'DESC')->get();
    }

    public function getPagenatedStaff(int $limit, ?string $search, $roleFilter = null)
    {
        $query = User::query()
            ->with(['staff', 'role']); // Load thêm 'role' để hiển thị tên chức vụ

        $query->whereHas('role', function (Builder $q) {
            $q->whereNotIn('name', ['CUSTOMER', 'ADMIN']);
        });


        // 3. Xử lý Search (Tìm kiếm)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('staff', function ($qStaff) use ($search) {
                        $qStaff->where('phone', 'LIKE', "%{$search}%")
                            ->orWhere('full_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Ví dụ: Frontend gửi lên ?role=GUIDE_STAFF
        if ($roleFilter) {
            $query->whereHas('role', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }
        return $query->orderBy('user_id', 'DESC')->paginate($limit);
    }


    public function findStaffById(string $id)
    {
        return User::where('user_id', $id) // Đảm bảo PK là user_id
            ->whereHas('staff')            // Chỉ lấy user nào là staff
            ->with([
                'staff',                   // Lấy profile staff
                'role.permissions.feature' // Lấy luôn Role và Quyền (Eager Load)
            ])
            ->first();
    }


    public function createStaff(array $data)
    {
        return DB::transaction(function () use ($data) {
            $roleName = $data['role']; // Mặc định nếu thiếu
            $role = Role::where('name', $roleName)->firstOrFail();
            $user = User::create([
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role_id'      => $role->role_id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $user->staff()->create([
                'user_id' => $user->id,
                'full_name' => $data['full_name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'address' => $data['address'] ?? '',
                'date_of_birth' => $data['date_of_birth'] ?? null
            ]);
            return $user;
        });
    }

    public function updateStaff(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $roleName = $data['role'];
            $role = Role::where('name', $roleName)->firstOrFail();
            $user = User::where('user_id', $id)->firstOrFail();
            $user->update(
                [
                    'email' => $data['email'],
                    'password' => isset($data['password']) ? $data['password'] : $user->password,
                    'role_id' => $role->role_id,
                ]
            );
            $user->staff()->update([
                'full_name' => $data['full_name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'address' => $data['address'] ?? '',
                'date_of_birth' => $data['date_of_birth'] ?? null
            ]);
            return $user;
        });
    }

    public function toggleStatusStaff(User $user): void
    {
        $user->is_active = !$user->is_active;
        $user->save();
    }

    public function deleteStaff(User $user): void
    {
        $user->delete();
    }
}
