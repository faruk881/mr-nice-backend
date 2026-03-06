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
        Schema::create('platform_commission_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('commission_amount', 10, 2)->default(0);  // safer than string
            $table->decimal('commission_percent', 5, 2)->default(0);  // e.g., 5.25%
            $table->enum('active_commission', ['commission_amount', 'commission_percent'])->default('commission_amount');
            $table->string('currency')->default('CHF');
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_commission_settings');
    }
};
