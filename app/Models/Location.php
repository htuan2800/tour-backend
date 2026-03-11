<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;
    protected $primaryKey = 'location_id';
    public $timestamps = false;

    protected $fillable = [
        'name', 
        'description', 
        'region',
        'image_url',
        'is_active',
    ];

    public function toursAsDestination()
    {
        return $this->belongsToMany(
            Tour::class, 
            'tour_destinations', // Tên bảng trung gian
            'destination_id',    // Khóa ngoại của Location trong bảng trung gian
            'tour_id'            // Khóa ngoại của Tour trong bảng trung gian
        );
    }

    public function toursAsDepart()
    {
        return $this->hasMany(Tour::class, 'depart_id', 'location_id');
    }
}
