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
        Schema::create('orders', function (Blueprint $table) {
            
            // Primary
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number')->unique();

            // Pickup location
            $table->string('pickup_address');
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_long', 10, 7);
            $table->string('pickup_notes')->nullable();

            // Delivery Location
            $table->string('delivery_address');
            $table->decimal('delivery_lat', 10, 7);
            $table->decimal('delivery_long', 10, 7);
            $table->string('delivery_notes')->nullable();

            // For fee
            $table->decimal('distance',8,2);
            $table->text('package_items');
            $table->enum('package_size',['small','medium','large']);
            $table->string('additional_notes')->nullable();
            $table->decimal('base_fare', 10, 2);
            $table->decimal('per_km_fee', 10, 2);
            $table->decimal('package_fee', 10, 2);
            $table->decimal('total_fee', 10, 2);
            $table->boolean('is_paid')->default(false);

            // Status
            $table->enum('status',['pending_payment','pending','accepted','pickedup','delivered','cancelled'])->default('pending_payment');
            $table->string('status_reason')->nullable();

            // Timestamps
            $table->timestamp('booking_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
