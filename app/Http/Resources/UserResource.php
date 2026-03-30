<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Carbon;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profileData = null;

        if ($this->staff) {
            $profileData = [
                'phone' => $this->staff->phone,
                'address' => $this->staff->address,
                'date_of_birth' => $this->staff->date_of_birth 
                    ? Carbon::parse($this->staff->date_of_birth)->format('Y-m-d') 
                    : null
            ];
        } elseif ($this->customer) {
            $profileData = [
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
                'date_of_birth' => $this->customer->date_of_birth 
                    ? Carbon::parse($this->customer->date_of_birth)->format('Y-m-d') 
                    : null
            ];
        }

        $features = [];

        if ($this->role) {
            //Role -> Permissions -> Feature
            $this->role->loadMissing('permissions.feature');
            // Gom nhóm permission theo feature_id
            $features = $this->role->permissions
                ->groupBy('feature_id')
                ->map(function ($permissions) {
                    // Lấy thông tin feature từ permission đầu tiên trong nhóm
                    $feature = $permissions->first()->feature;

                    // Nếu permission rác (không có feature cha) thì bỏ qua
                    if (!$feature) return null;

                    return [
                        'feature_name' => $feature->name,
                        'code' => $feature->code,
                        'permissions' => $permissions->map(function ($perm) {
                            return [
                                'id' => $perm->permission_id,
                                'name' => $perm->display_name,
                                'code' => $perm->name,
                            ];
                        })->values()->all()
                    ];
                })
                ->filter() // Loại bỏ các giá trị null
                ->values() // Reset key mảng về 0, 1, 2...
                ->all();
        }

        return [
            'id' => $this->user_id,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'role' => $this->role ? [
                'role_id' => $this->role->role_id,
                'name' => $this->role->name,
                'display_name' => $this->role->display_name,
            ] : null,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'features' => $features,
            // Trả về dynamic profile
            'profile' => $profileData,
        ];
    }
}
