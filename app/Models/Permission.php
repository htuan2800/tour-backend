<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $primaryKey = 'permission_id';
    protected $fillable = [
        'name',
        'display_name',
        'feature_id',
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }
}
