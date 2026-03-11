<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'feature_name' => $this->name,
            
            // Map Permissions con bên trong
            'permissions' => $this->permissions->map(function ($perm) {
                return [
                    'id' => $perm->permission_id, 
                    'name' => $perm->display_name,
                    'code' => $perm->name, 
                ];
            }),
        ];
    }
}
