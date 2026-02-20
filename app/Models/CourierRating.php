<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierRating extends Model
{
    protected $guarded = ['id'];
    
    protected function casts(): array
    {
        return [
            'rating' => 'integer'
        ];
    }

    // The customer who gave the rating
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // The courier who is being rated
    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    // Optional: the order this rating is for
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
