<?php

namespace Database\Seeders;

use App\Models\PlatformCommissionSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlatformCommissionSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PlatformCommissionSetting::updateOrCreate([
            'commission_amount' => 0,
            'commission_percent' => 0,
            'active_commission' => 'commission_amount',
            'currency' => 'CHF'
        ]);
    
    }
}
