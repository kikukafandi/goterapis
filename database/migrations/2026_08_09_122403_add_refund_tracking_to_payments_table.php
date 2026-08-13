<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('refund_amount')->nullable()->after('paid_at');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_amount');
            $table->timestamp('refunded_at')->nullable()->after('refund_requested_at');
            $table->timestamp('refund_failed_at')->nullable()->after('refunded_at');
            $table->text('refund_error')->nullable()->after('refund_failed_at');
            $table->unsignedSmallInteger('refund_attempts')->default(0)->after('refund_error');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'refund_requested_at', 'refunded_at', 'refund_failed_at', 'refund_error', 'refund_attempts']);
        });
    }
};
