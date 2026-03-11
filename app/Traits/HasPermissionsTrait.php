<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Support\Facades\Cache;

trait HasPermissionsTrait
{
    public function hasPermission($permissionCode)
    {
        if (!$this->role) {
            return false; // Nếu người dùng không có vai trò, trả về false
        }
        // Admin tối cao (Backdoor)
        if ($this->role->name === 'ADMIN') return true;

        $cacheKey = 'role_permissions_' . $this->role_id;

        // Lấy từ Cache, nếu không có thì Query DB và lưu Cache 24h
        $permissions = Cache::remember($cacheKey, 60 * 60 * 24, function () {
            return $this->role->permissions()->pluck('code')->toArray();
        });

        return in_array($permissionCode, $permissions);
    }

    // 3. Hàm helper check role
    public function hasRole($roleName)
    {
        return $this->role->name === $roleName;
    }
}
