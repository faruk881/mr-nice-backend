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
                'email' => 'farismahdipro@gmail.com',
            ],
            [
                'name' => 'Admin',
                'phone' => '01000000000',
                'email' => 'farismahdipro@gmail.com',
                'password' => 'rev88943',
                'email_verified_at' => now()
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
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
