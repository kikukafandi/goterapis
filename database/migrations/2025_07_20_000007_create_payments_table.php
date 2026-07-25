<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('midtrans');
            $table->string('gateway_ref')->nullable()->index(); // order_id/transaction_id gateway
            $table->string('method')->nullable();               // qris, va, gopay, ...
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'refunded'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw')->nullable(); // payload notifikasi gateway
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
