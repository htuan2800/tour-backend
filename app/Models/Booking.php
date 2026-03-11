<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';
    
    // Laravel mặc định tìm cột created_at và updated_at. 
    // DB bạn chỉ có booking_date, nên cần tắt timestamps chuẩn
    public $timestamps = false; 

    protected $fillable = [
        'booking_code',
        'user_id',
        'schedule_id',
        'coupon_id',
        'contact_fullName',
        'contact_phone',
        'contact_email',
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

    public function payments() {
        return $this->hasMany(Payment::class, 'booking_id');
    }

    public function coupon() {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
