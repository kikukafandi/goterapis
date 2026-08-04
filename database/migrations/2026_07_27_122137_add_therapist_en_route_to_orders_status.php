<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BARU = ['pending_confirmation', 'pending_payment', 'paid', 'therapist_en_route', 'accepted', 'rejected', 'in_progress', 'completed', 'cancelled', 'refunded', 'disputed'];

    private const LAMA = ['pending_confirmation', 'pending_payment', 'paid', 'accepted', 'rejected', 'in_progress', 'completed', 'cancelled', 'refunded', 'disputed'];

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', self::BARU)->default('pending_confirmation')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', self::LAMA)->default('pending_confirmation')->change();
        });
    }
};
