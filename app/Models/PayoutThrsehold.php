<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutThrsehold extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
    ];
}
