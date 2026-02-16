<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        // Coordinates
        'pickup_lat'      => 'decimal:7',
        'pickup_long'     => 'decimal:7',
        'delivery_lat'    => 'decimal:7',
        'delivery_long'   => 'decimal:7',

        // Fee related
        'distance'        => 'decimal:2',
        'base_fare'       => 'decimal:2',
        'per_km_fee'      => 'decimal:2',
        'service_fee'     => 'decimal:2',
        'total_fee'       => 'decimal:2',

        // Boolean
        'is_paid'         => 'boolean',

        // Date
        'booking_date'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    // --- Relationship: an order can have multiple payments ---
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Optional: get latest payment
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
}
