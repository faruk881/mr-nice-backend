<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            // Link to payment
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

            // Amount (optional if always full)
            $table->decimal('amount', 10, 2)->nullable();

            // Status
            $table->enum('status', [
                'requested',    // user cancelled, waiting admin
                'processing',   // admin sent to Stripe
                'succeeded',    // refund done
                'failed'
            ])->default('requested');

            // Stripe response (optional, for debugging)
            $table->json('stripe_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
