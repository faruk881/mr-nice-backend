<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
            'otp_verified_at' => 'datetime',
        ];
    }

    public function roles() {
        return $this->belongsToMany(Role::class);
    }

    public function orders()
    {
        // Orders created by this user (customer)
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function assignedOrders()
    {
        // Orders assigned to this user as courier
        return $this->hasMany(Order::class, 'courier_id');
    }

    public function courierProfile()
    {
        // One-to-one courier profile
        return $this->hasOne(CourierProfile::class);
    }

    // Ratings **given by this user** (if customer)
    public function ratingsGiven()
    {
        return $this->hasMany(CourierRating::class, 'customer_id');
    }

    // Ratings **received by this user** (if courier)
    public function ratingsReceived()
    {
        return $this->hasMany(CourierRating::class, 'courier_id');
    }

        public function wallet() {
        return $this->hasOne(Wallet::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }


}
