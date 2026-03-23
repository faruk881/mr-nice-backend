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
        Schema::create('refund_policy_settings', function (Blueprint $table) {
            $table->id();

            // Only one active refund type
            $table->enum('refund_type', ['partial_refund', 'full_refund', 'custom_refund'])->default('partial_refund');
            
            // Used only when active = custom
            $table->decimal('custom_refund_deduction_amount', 10, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_policy_settings');
    }
};
