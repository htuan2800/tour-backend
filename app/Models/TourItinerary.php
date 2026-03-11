<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourItinerary extends Model
{
    protected $primaryKey = 'itinerary_id';
    public $timestamps = false;

    protected $fillable = ['tour_id', 'day_number', 'title', 'description'];

    public function tour() {
        return $this->belongsTo(Tour::class, 'tour_id');
    }
}
