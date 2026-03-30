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
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();

            // User who created the support message
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('email')->nullable();

            // Optional order reference
            $table->string('order_number')->nullable()->index();

            // Message topic/title
            $table->string('topic');

            // Main message body
            $table->text('message');

            // Status workflow
            $table->enum('status', ['pending','in_progress','resolved'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
