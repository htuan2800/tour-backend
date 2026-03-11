<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $primaryKey = 'feature_id';

    protected $fillable = [
        'name',
        'code',
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'feature_id');
    }
}
