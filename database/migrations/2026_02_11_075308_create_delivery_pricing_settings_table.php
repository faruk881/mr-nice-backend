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
        Schema::create('delivery_pricing_settings', function (Blueprint $table) {
            $table->id();
            // Currency (ISO Code)
            $table->string('currency', 3)->default('CHF');

            // Pricing Fields
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->decimal('price_per_km', 10, 2)->default(0);

            $table->decimal('small_package_price', 10, 2)->default(0);
            $table->decimal('medium_package_price', 10, 2)->default(0);
            $table->decimal('large_package_price', 10, 2)->default(0);

            // Track which admin updated it
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_pricing_settings');
    }
};
