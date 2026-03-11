<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $primaryKey = 'review_id';
    
    // Vì bảng có created_at nhưng không có updated_at, ta cần config nhẹ
    public $timestamps = true; 
    const UPDATED_AT = null; // Tắt cột updated_at

    protected $fillable = ['user_id', 'tour_id', 'rating', 'comment'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tour() {
        return $this->belongsTo(Tour::class, 'tour_id');
    }
}
