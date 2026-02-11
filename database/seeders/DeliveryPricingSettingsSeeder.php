<?php

namespace Database\Seeders;

use App\Models\DeliveryPricingSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliveryPricingSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DeliveryPricingSetting::create([
            'currency' => 'CHF',
            'base_fare' => 10,
            'price_per_km' => 2,
            'small_package_price' => 5,
            'medium_package_price' => 8,
            'large_package_price' => 12,
        ]);
    }
}
