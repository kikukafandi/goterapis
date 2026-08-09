<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->decimal('service_lat', 10, 7)->nullable()->after('district');
            $table->decimal('service_lng', 10, 7)->nullable()->after('service_lat');
            $table->index(['service_lat', 'service_lng']);
        });
    }

    public function down(): void
    {
        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->dropIndex(['service_lat', 'service_lng']);
            $table->dropColumn(['service_lat', 'service_lng']);
        });
    }
};
