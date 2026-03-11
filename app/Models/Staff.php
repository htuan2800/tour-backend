<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false; // Vì ID lấy từ User, không tự tăng
    public $timestamps = false;

    protected $fillable = [
        'user_id', 
        'full_name', 
        'phone', 
        'address', 
        'date_of_birth'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
