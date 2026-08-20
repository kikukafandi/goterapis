<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('shop_order_id')->nullable()->after('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->change();
            $table->unique('order_id');
            $table->unique('shop_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
            $table->dropUnique(['shop_order_id']);
            $table->dropConstrainedForeignId('shop_order_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable(false)->change();
        });
    }
};
