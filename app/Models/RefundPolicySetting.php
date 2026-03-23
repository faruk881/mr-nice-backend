<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundPolicySetting extends Model
{
    protected $casts = [ 
        'custom_refund_deduction_amount' => 'decimal:2',
    ];
}
