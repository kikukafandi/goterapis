<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('legal_version')->nullable()->after('blocked_at');
            $table->timestamp('legal_accepted_at')->nullable()->after('legal_version');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['legal_version', 'legal_accepted_at']);
        });
    }
};
