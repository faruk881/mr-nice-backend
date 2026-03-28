<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        // Pick a random existing customer
        $customer = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->inRandomOrder()->first();

        $packageSize = $this->faker->randomElement(['small', 'medium', 'large']);
        $pickupLat = $this->faker->latitude();
        $pickupLon = $this->faker->longitude();
        $deliveryLat = $this->faker->latitude();
        $deliveryLon = $this->faker->longitude();
        // Booking date: from last 7 months to next 5 days (you can adjust future range)
        $bookingDate = Carbon::now()
        ->subMonths(7)
        ->addDays(rand(0, (26 * 30) + 28)) // total range: ~7 months + 5 days
        ->startOfDay();

        // Created date: must be before or equal to booking date
        $createdAt = (clone $bookingDate)
        ->subDays(rand(0, 10)) // created up to 10 days before booking
        ->subHours(rand(0, 23))
        ->subMinutes(rand(0, 59));

        $distance = $this->faker->randomFloat(2, 1, 50);
        $baseFare = 50;
        $perKmFee = 10;
        $packagePrices = [
            'small' => 20,
            'medium' => 30,
            'large' => 50,
        ];
        $packageFee = $packagePrices[$packageSize];
        $totalFee = max($distance * $perKmFee + $packageFee, $baseFare);

        $status = $this->faker->randomElement([
            'pending_payment',
            'pending',
            'accepted',
            'pickedup',
            'pending_delivery',
            'cancelled'
        ]);

        return [
            'customer_id'      => $customer->id,
            'order_number'     => strtoupper(Str::random(10)),
            'pickup_address'   => $this->faker->address(),
            'pickup_lat'       => $pickupLat,
            'pickup_long'      => $pickupLon,
            'pickup_notes'     => $this->faker->optional()->sentence(),
            'delivery_address' => $this->faker->address(),
            'delivery_lat'     => $deliveryLat,
            'delivery_long'    => $deliveryLon,
            'delivery_notes'   => $this->faker->optional()->sentence(),
            'package_items'    => $this->faker->words(rand(1,5), true),
            'package_size'     => $packageSize,
            'additional_notes' => $this->faker->optional()->sentence(),
            'distance'         => $distance,
            'base_fare'        => $baseFare,
            'per_km_fee'       => $perKmFee,
            'package_fee'      => $packageFee,
            'total_fee'        => $totalFee,
            'status'           => $status,
            'booking_date'     => $bookingDate,
            'is_paid'          => false, // default
            'created_at'       => $createdAt,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            // Automatically create payment if status is pending
            if ($order->status === 'pending'){
                $stripeFeePercent = 0.029;
                $stripeFixedFee  = 0.30;

                $stripeProcessingFee = round($order->total_fee * $stripeFeePercent + $stripeFixedFee, 2);
                $netAmount = round($order->total_fee - $stripeProcessingFee, 2);

                $order->payments()->create([
                    'stripe_payment_intent_id'   => 'factory',
                    'stripe_charge_id'           => 'factory',
                    'status'                     => 'succeeded',
                    'payment_method'             => 'card',
                    'amount'                     => $order->total_fee,
                    'stripe_processing_fee'      => $stripeProcessingFee,
                    'net_amount'                 => $netAmount,
                    'currency'                   => 'chf',
                    'created_at'                 => $order->created_at
                ]);

                $order->update([
                    'is_paid' => true,
                    'stripe_processing_fee'      => $stripeProcessingFee,
                    'net_amount'                 => $netAmount,
                ]);
            }
            if ($order->status === 'cancelled'){
                $stripeFeePercent = 0.029;
                $stripeFixedFee  = 0.30;

                $stripeProcessingFee = round($order->total_fee * $stripeFeePercent + $stripeFixedFee, 2);
                $netAmount = round($order->total_fee - $stripeProcessingFee, 2);

                $order->update([
                    'is_paid' => true,
                    'stripe_processing_fee'      => $stripeProcessingFee,
                    'net_amount'                 => $netAmount,
                ]);

                $payment = $order->payments()->create([
                    'stripe_payment_intent_id'   => 'factory',
                    'stripe_charge_id'           => 'factory',
                    'status'                     => 'succeeded',
                    'payment_method'             => 'card',
                    'amount'                     => $order->total_fee,
                    'stripe_processing_fee'      => $stripeProcessingFee,
                    'net_amount'                 => $netAmount,
                    'currency'                   => 'chf',
                    'created_at'                 => $order->created_at
                ]);

                $order->refund()->create([
                    'payment_id' => $payment->id,
                    'status' => 'requested',
                    'created_at' => $order->created_at
                ]);

                $order->update([
                    'status' => 'cancelled',
                    'status_reason' => $this->faker->sentence(),
                ]);

            }


            // Automatically create payment if status is 'accepted', 'pickedup', 'delivered', 'pending_delivery'
            if (in_array($order->status, ['accepted', 'pickedup', 'pending_delivery',])) {
                $stripeFeePercent = 0.029;
                $stripeFixedFee  = 0.30;

                $stripeProcessingFee = round($order->total_fee * $stripeFeePercent + $stripeFixedFee, 2);
                $netAmount = round($order->total_fee - $stripeProcessingFee, 2);

                $order->payments()->create([
                    'stripe_payment_intent_id'   => 'factory',
                    'stripe_charge_id'           => 'factory',
                    'status'                     => 'succeeded',
                    'payment_method'             => 'card',
                    'amount'                     => $order->total_fee,
                    'stripe_processing_fee'      => $stripeProcessingFee,
                    'net_amount'                 => $netAmount,
                    'currency'                   => 'chf',
                    'created_at'                 => $order->created_at
                ]);

                $order->update([
                    'courier_id' => User::whereHas('courierProfile', function($query) {
                                        $query->where('document_status', 'verified');
                                    })->inRandomOrder()->first()->id,
                    'is_paid' => true,
                    'stripe_processing_fee'      => $stripeProcessingFee,
                    'net_amount'                 => $netAmount,
                ]);
            }
        });
    }
}