<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable karena akun lama & pendaftar lewat Google belum punya nilainya;
        // pengisian dipaksa lewat middleware sebelum memesan.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['pria', 'wanita'])->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
