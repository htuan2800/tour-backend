<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Booking extends Model
{
    protected $primaryKey = 'booking_id';
    
    public $incrementing = false; 
    
    protected $keyType = 'string'; 
    
    public $timestamps = false; 

    protected $fillable = [
        'booking_id',
        'user_id',
        'schedule_id',
        'coupon_id',
        'contact_fullName',
        'contact_phone',
        'contact_email',
        'contact_address',
        'booking_date',
        'applied_price_adult',
        'number_of_adults', 
        'applied_price_children',
        'number_of_children', 
        'original_price',
        'discount_amount',
        'total_price',
        'status', 
        'note'
    ];

    protected $casts = [
        'booking_date' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schedule() {
        return $this->belongsTo(TourSchedule::class, 'schedule_id');
    }

    public function passengers() {
        return $this->hasMany(Passenger::class, 'booking_id');
    }

    public function payment() {
        return $this->hasOne(Payment::class, 'booking_id');
    }

    public function coupon() {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->booking_id)) {
                $model->booking_id = 'TOUR_' . time() . '_' . Str::random(5);
            }
        });
    }
}
