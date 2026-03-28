<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CourierProfile extends Model
{
    protected $guarded = ['id'];

    // The wallet will automatically be created when a courier profile is created
    protected static function booted()
    {
        static::created(function ($courierProfile) {
            $user = $courierProfile->user;

            if (!$user->wallet) {
                $user->wallet()->create([
                    'balance' => 0.00,
                    'currency' => 'CHF',
                    'status' => 'active',
                ]);
            }
        });
    }

    
    public function user() {
        return $this->belongsTo(User::class);
    }

    // Show full link
    public function getIdDocumentAttribute($value)
    {
        return Storage::disk('public')->url($value);
    }
}
