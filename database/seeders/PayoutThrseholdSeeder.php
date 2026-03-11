<?php

namespace Database\Seeders;

use App\Models\PayoutThrsehold;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PayoutThrseholdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PayoutThrsehold::updateOrCreate([
            'minimum_amount' => 10,
            'maximum_amount' => 9999,
            'currency' => 'CHF'
        ]);
    }
}
