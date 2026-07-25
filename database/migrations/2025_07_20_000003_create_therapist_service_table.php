<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga & durasi per layanan yang dikuasai terapis
        Schema::create('therapist_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');           // rupiah
            $table->unsignedSmallInteger('duration_min'); // menit
            $table->timestamps();
            $table->unique(['therapist_profile_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapist_service');
    }
};
