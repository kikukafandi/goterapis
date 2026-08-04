<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->boolean('schedule_configured')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->dropColumn('schedule_configured');
        });
    }
};
