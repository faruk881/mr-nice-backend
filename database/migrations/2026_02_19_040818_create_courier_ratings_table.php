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
        Schema::create('courier_ratings', function (Blueprint $table) {

            $table->id();
            // The customer who gives the rating
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            // The courier being rated
            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();

            // Rating: 1-5 stars (or any scale you prefer)
            $table->tinyInteger('rating')->unsigned()->comment('Rating from 1 to 5');

            // Optional relation to specific order (if rating is tied to a delivery)
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->timestamps();

            // Prevent duplicate ratings for the same courier by the same customer for the same order
            $table->unique(['customer_id', 'courier_id', 'order_id'], 'unique_courier_rating');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_ratings');
    }
};
