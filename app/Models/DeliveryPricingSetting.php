<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPricingSetting extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [ 
        'base_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'small_package_price' => 'decimal:2',
        'medium_package_price' => 'decimal:2',
        'large_package_price' => 'decimal:2'
    ];
}
