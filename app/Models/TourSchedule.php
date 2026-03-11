<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourSchedule extends Model
{
    protected $primaryKey = 'schedule_id';
    public $timestamps = false;

    protected $fillable = [
        'tour_id',
        'departure_date', 
        'return_date', 
        'price_adult', 
        'price_child',
        'max_capacity', 
        'current_booked', 
        'status'
    ];
    
    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    public function bookings() {
        return $this->hasMany(Booking::class, 'schedule_id');
    }

    public function tour() {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

}
