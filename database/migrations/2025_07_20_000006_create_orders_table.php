<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // nomor pesanan
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapist_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained();

            $table->enum('model', ['panggilan', 'tempat']);
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_min');

            // Alamat untuk layanan panggilan
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('notes')->nullable();

            // Rincian biaya (rupiah)
            $table->unsignedInteger('price');
            $table->unsignedInteger('transport_fee')->default(0);
            $table->unsignedInteger('service_fee')->default(0);   // biaya layanan pengguna
            $table->unsignedInteger('total');
            $table->unsignedInteger('commission')->default(0);    // potongan platform
            $table->unsignedInteger('payout')->default(0);        // diterima terapis

            $table->enum('status', [
                'pending_payment', 'paid', 'accepted', 'rejected',
                'in_progress', 'completed', 'cancelled', 'refunded', 'disputed',
            ])->default('pending_payment')->index();

            $table->string('start_pin', 6)->nullable(); // PIN mulai layanan (dipegang user)
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
