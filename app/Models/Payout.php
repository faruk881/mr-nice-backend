<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'amount' => 'decimal:2',
    ];


    public function courier() {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
