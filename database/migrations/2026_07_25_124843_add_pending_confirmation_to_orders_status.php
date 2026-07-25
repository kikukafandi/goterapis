<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terapis mengonfirmasi sebelum pengguna membayar.
 * Alur: pending_confirmation → pending_payment → paid → in_progress → completed.
 * 'accepted' tetap di enum untuk pesanan lama (alur bayar-dulu).
 */
return new class extends Migration
{
    private const BARU = [
        'pending_confirmation', 'pending_payment', 'paid', 'accepted', 'rejected',
        'in_progress', 'completed', 'cancelled', 'refunded', 'disputed',
    ];

    private const LAMA = [
        'pending_payment', 'paid', 'accepted', 'rejected',
        'in_progress', 'completed', 'cancelled', 'refunded', 'disputed',
    ];

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', self::BARU)->default('pending_confirmation')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', self::LAMA)->default('pending_payment')->change();
        });
    }
};
