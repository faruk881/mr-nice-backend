<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            [
                'email' => env('ADMIN_EMAIL'),
            ],
            [
                'name' => env('ADMIN_NAME'),
                'phone' => env('ADMIN_PHONE'),
                'email' => env('ADMIN_EMAIL'),
                'password' => env('ADMIN_PASSWORD'),
                'email_verified_at' => now()
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if($adminRole) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        if (!$user->wallet) {
            $user->wallet()->create([
                'balance' => 0.00,
                'currency' => 'CHF',
                'status' => 'active',
            ]);
        }
    }
}
