<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Akurasi (meter) titik lokasi pelanggan saat pesan; dipakai melonggarkan cek jarak.
            $table->unsignedInteger('loc_accuracy')->nullable()->after('lng');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('loc_accuracy');
        });
    }
};
