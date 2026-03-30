<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';
    public $timestamps = false; // Bảng này dùng payment_date thay vì created_at chuẩn

    protected $fillable = [
        'booking_id', 
        'amount', 
        'payment_method', 
        'payment_date', 
        'transaction_id',
        'transaction_code',
        'payment_status', 
        'bank_code',
        'response_code',
        'payment_details'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function booking() {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
