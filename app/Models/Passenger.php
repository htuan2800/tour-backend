<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    protected $primaryKey = 'passenger_id';
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'full_name', 'dob', 'gender', 'type'
    ];

    protected $casts = [
        'dob' => 'date', // Cast về dạng Date để dễ xử lý tuổi
    ];

    public function booking() {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
