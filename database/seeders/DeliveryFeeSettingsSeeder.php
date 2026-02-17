<?php

namespace Database\Seeders;

use App\Models\DeliveryFeeSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliveryFeeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DeliveryFeeSetting::updateOrCreate([
            'currency' => 'CHF',
            'base_fare' => 10,
            'per_km_fee' => 2,
            'small_package_fee' => 5,
            'medium_package_fee' => 8,
            'large_package_fee' => 12,
        ]);
    }
}
