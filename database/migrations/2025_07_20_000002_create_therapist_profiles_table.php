<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('gender', ['pria', 'wanita'])->nullable();
            $table->text('bio')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);

            // Status verifikasi berjenjang (segel-daun)
            $table->enum('verification_status', [
                'anggota', 'identitas', 'berpengalaman', 'terdaftar', 'pilihan',
            ])->default('anggota')->index();
            $table->boolean('is_featured')->default(false); // "Dipromosikan" berbayar

            // Model layanan
            $table->boolean('serves_call')->default(false);   // terapis panggilan
            $table->boolean('serves_place')->default(false);  // di tempat praktik

            // Wilayah layanan (dropdown MVP)
            $table->string('province')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('district')->nullable();
            $table->unsignedInteger('transport_fee')->default(0); // biaya transport panggilan

            // Tempat praktik
            $table->string('place_address')->nullable();
            $table->decimal('place_lat', 10, 7)->nullable();
            $table->decimal('place_lng', 10, 7)->nullable();
            $table->json('place_photos')->nullable();
            $table->json('facilities')->nullable();

            // Metrik reputasi (di-cache)
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);

            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapist_profiles');
    }
};
