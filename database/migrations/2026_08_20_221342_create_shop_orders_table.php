<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->text('address');
            $table->string('city');
            $table->string('postal_code', 10)->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_cost')->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->enum('status', ['waiting_shipping', 'pending_payment', 'paid', 'processing', 'shipped', 'completed', 'cancelled'])->default('waiting_shipping')->index();
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};
