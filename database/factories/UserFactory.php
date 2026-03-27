<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->unique()->phoneNumber(),
            'profile_photo' => 'images/profile/fake.jpg',
            'status' => 'active',
            'password' => Hash::make('12345678'),
        ];
    }

    /**
     * Customer state
     */
    public function customer()
    {
        return $this->afterCreating(function ($user) {
            $role = Role::where('name', 'customer')->first();
            if ($role) $user->roles()->attach($role->id);
        });
    }

    /**
     * Courier state (creates profile + wallet)
     */
    public function courier()
    {
        return $this->afterCreating(function ($user) {
            $role = Role::where('name', 'courier')->first();
            if ($role) $user->roles()->attach($role->id);

            // Create courier profile
            $courierProfile = $user->courierProfile()->create([
                'city' => fake()->city(),
                'vehicle_type' => fake()->randomElement(['bicycle','car','motorbike','cargo-van']),
                'package_size' => fake()->randomElement(['small','medium','large']),
                'id_document' => 'courier/id_documents/fake.jpg',
                'document_status' => fake()->randomElement(['pending','verified','rejected']),
            ]);

            // Create wallet automatically
            if (!$user->wallet) {
                $user->wallet()->create([
                    'balance' => 0.00,
                    'currency' => 'CHF',
                    'status' => 'active',
                ]);
            }
        });
    }

    /**
     * Admin state
     */
    public function admin()
    {
        return $this->afterCreating(function ($user) {
            $role = Role::where('name', 'admin')->first();
            if ($role) $user->roles()->attach($role->id);
        });
    }
}