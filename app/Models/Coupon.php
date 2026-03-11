<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;
    protected $primaryKey = 'coupon_id';
    public $timestamps = false; // Chỉ có created_at, không có updated_at

    protected $fillable = [
        'code', 'description', 'discount_type', 'discount_value',
        'start_date', 'end_date', 'usage_limit', 'usage_count', 'is_active'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    // Logic kiểm tra coupon có hợp lệ không
    public function checkValidity() 
    {
        $now = now();

        if (!$this->is_active) {
            throw new \Exception("Mã giảm giá này đang bị khóa.");
        }

        if ($this->start_date && $now->lt($this->start_date)) {
            throw new \Exception("Mã giảm giá chưa đến đợt áp dụng.");
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            throw new \Exception("Mã giảm giá đã hết hạn.");
        }

        if ($this->usage_limit > 0 && $this->usage_count >= $this->usage_limit) {
            throw new \Exception("Mã giảm giá đã hết lượt sử dụng.");
        }

        return true;
    }

    public function bookings() {
        return $this->hasMany(Booking::class, 'coupon_id');
    }
}
