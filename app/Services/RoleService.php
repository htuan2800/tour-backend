<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function getAllFunction()
    {
        return Feature::with('permissions')->get();
    }

    public function getPagenatedRole(int $limit, ?string $search)
    {
        $query = Role::query();
        $query->whereNotIn('name', ['ADMIN', 'CUSTOMER']);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('role_id', 'DESC')->paginate($limit);
    }

    public function getRoleForStaff()
    {
        return Role::whereNotIn('name', ['CUSTOMER', 'ADMIN'])->get();
    }

    public function findRoleById(string $id)
    {
        $role = Role::where('role_id', $id)->firstOrFail();

        $role->load(['permissions' => function ($query) {
            $query->select('permissions.permission_id', 'permissions.name');
        }]);

        return $role;
    }


    public function createRole(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Tạo Role
            $role = Role::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
            ]);


            if (!empty($data['permission_ids'])) {
                $role->permissions()->attach($data['permission_ids']);
            }
            return $role;
        });
    }

    public function updateRole(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // 1. Cập nhật Role
            $role = Role::where('role_id', $id)->firstOrFail();
            $role->update([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
            ]);

            // 2. Cập nhật Permissions
            if (isset($data['permission_ids'])) {
                $role->permissions()->sync($data['permission_ids']);
            } else {
                // Nếu không có permission_ids trong request, xóa tất cả permissions
                $role->permissions()->detach();
            }

            return $role;
        });
    }

    public function deleteRole(Role $role): void
    {
        $role->delete();
    }
}
