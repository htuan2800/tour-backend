<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model
{
    use SoftDeletes;
    protected $primaryKey = 'tour_id';
    public $timestamps = false; // Nếu bảng DB không có created_at/updated_at

    protected $fillable = [
        'name', 
        'image_url', 
        'description', 
        'duration_days', 
        'duration_nights',
        'transportation',
        'depart_id',
        'is_active'
    ];

    // Relationships
    public function destinations()
    {
        return $this->belongsToMany(
            Location::class, 
            'tour_destinations',
            'tour_id',           
            'destination_id'  
        );
    }

    public function depart() {
        return $this->belongsTo(Location::class, 'depart_id');
    }


    public function itineraries() {
        return $this->hasMany(TourItinerary::class, 'tour_id');
    }

    public function schedules() {
        return $this->hasMany(TourSchedule::class, 'tour_id');
    }
}
