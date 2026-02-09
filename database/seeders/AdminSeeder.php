<?php

namespace Database\Seeders;

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
         User::updateOrCreate(
            [
                'email' => env('ADMIN_EMAIL'),
            ],
            [
                'name' => env('ADMIN_NAME'),
                'phone' => env('ADMIN_PHONE'),
                'email' => env('ADMIN_EMAIL'),
                'password' => env('ADMIN_PASSWORD'),
                'account_type' => 'admin',
                'email_verified_at' => now()
            ]
        );
    }
}
