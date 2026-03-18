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
        'package_fee'     => 'decimal:2',
        'total_fee'       => 'decimal:2',
        'stripe_processing_fee' => 'decimal:2',
        'net_amount'      => 'decimal:2',

        // Boolean
        'is_paid'         => 'boolean',

        // Date
        'booking_date'    => 'datetime',
    ];

    public function customer() {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function courier() {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function walletTransaction() {
        return $this->hasOne(WalletTransaction::class);
    }

    protected $appends = [
        'courier_commission',
        'admin_commission'
    ];

    public function getCourierCommissionAttribute() {
        
        $settings = PlatformCommissionSetting::first();

        if (!$settings) {
            return null;
        }

        if ($settings->active_commission === 'commission_percent') {
            return round($this->net_amount - ($this->net_amount * $settings->commission_percent) / 100, 2);
        }

        if ($settings->active_commission === 'commission_amount' && $this->net_amount < $settings->commission_amount) {
            return null;
        }
        if ($settings->active_commission === 'commission_amount') {
            return $this->net_amount - $settings->commission_amount;
        }

        return null;

    }



    public function getAdminCommissionAttribute() {
        $settings = PlatformCommissionSetting::first();

        if (!$settings) {
            return null;
        }

        if ($settings->active_commission === 'commission_percent') {
            return round(($this->net_amount * $settings->commission_percent) / 100, 2);
        }
        if ($settings->active_commission === 'commission_amount' && $this->net_amount < $settings->commission_amount) {
            return null;
        }
        if ($settings->active_commission === 'commission_amount') {
            return $settings->commission_amount;
        }

        return null;
    }

    // --- Relationship: an order can have multiple payments ---
    public function payments() {
        return $this->hasMany(Payment::class);
    }

    // Optional: get latest payment
    public function latestPayment() {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPaid() {
        return $this->payments()
            ->where('status', 'succeeded')
            ->exists();
    }

    public function paymentMethod() {
        return $this->payment_method;
    }
    public function refund()
    {
        return $this->hasOneThrough(Refund::class, Payment::class);
    }


}
