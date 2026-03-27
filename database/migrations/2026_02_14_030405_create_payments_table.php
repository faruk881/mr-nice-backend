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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable(); // for payment links
            $table->string('payment_link')->nullable(); // optional: store actual link URL

            $table->decimal('amount', 10, 2);
            $table->decimal('stripe_processing_fee', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->string('currency')->default('chf'); // TWINT uses CHF

            $table->enum('status', ['pending','requires_action','processing','succeeded','failed','refunded'])->default('pending');

            $table->string('payment_method')->nullable(); // card, twint
            $table->json('stripe_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
