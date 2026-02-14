<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryFeeSetting extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [ 
        'base_fare' => 'decimal:2',
        'per_km_fee' => 'decimal:2',
        'small_package_fee' => 'decimal:2',
        'medium_package_fee' => 'decimal:2',
        'large_package_fee' => 'decimal:2',
        'service_fee' => 'decimal:2'
    ];
}
