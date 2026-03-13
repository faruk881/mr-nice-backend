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
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            // Stripe's unique event ID
            $table->string('event_id')->unique();

            // Stripe event type (e.g., payout.paid, transfer.failed)
            $table->string('type');

            // Optional: related Stripe object IDs for easier querying
            $table->string('object_id')->nullable(); // e.g., payout ID or transfer ID
            $table->string('object_type')->nullable(); // e.g., payout, transfer

            // Store raw payload for debugging/audit
            $table->json('payload');

            // Processed flag (in case you need retries)
            $table->boolean('processed')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
