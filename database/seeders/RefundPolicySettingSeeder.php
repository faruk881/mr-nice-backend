<?php

namespace Database\Seeders;

use App\Models\RefundPolicySetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RefundPolicySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RefundPolicySetting::updateOrCreate(
            ['id' => 1], // condition
            [
                'refund_type' => 'partial_refund',
                'custom_refund_deduction_amount' => 0.00,
            ]
        );
    }
}
