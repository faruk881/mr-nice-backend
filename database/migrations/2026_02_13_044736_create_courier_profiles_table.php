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
        Schema::create('courier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('city');
            $table->enum('vehicle_type',['bicycle','car','motorbike','cargo-van']);
            $table->enum('package_size',['small','medium','large'])->nullable();
            $table->string('id_document'); // Document path
            $table->enum('document_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('document_reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};
