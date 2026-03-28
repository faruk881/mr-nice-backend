<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PayoutThrsehold;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(RoleSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(DeliveryFeeSettingsSeeder::class);
        $this->call(PlatformCommissionSettingSeeder::class);
        $this->call(PayoutThrseholdSeeder::class);
        $this->call(RefundPolicySettingSeeder::class);
        
        // Create users
        // User::factory()->customer()->count(5)->create();
        // User::factory()->courier()->count(5)->create();
        Order::factory()->count(500)->create()->each(function($order) {
            $order->order_number = 'LX-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            $order->saveQuietly(); // avoid triggering events again
        });

        
        
    }
}
