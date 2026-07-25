<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapist_profile_id')->constrained()->cascadeOnDelete();

            // 5 dimensi (1-5); rata-rata dihitung untuk tampilan kartu
            $table->unsignedTinyInteger('rating_service');
            $table->unsignedTinyInteger('rating_punctual');
            $table->unsignedTinyInteger('rating_manners');
            $table->unsignedTinyInteger('rating_hygiene');
            $table->unsignedTinyInteger('rating_accuracy');
            $table->text('body')->nullable();
            $table->boolean('is_hidden')->default(false); // disembunyikan admin bila melanggar
            $table->timestamps();

            $table->unique('order_id'); // satu ulasan per transaksi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
