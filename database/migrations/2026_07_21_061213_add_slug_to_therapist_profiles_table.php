<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('user_id');
        });

        // Isi slug untuk data yang sudah ada: nama terapis + suffix acak.
        DB::table('therapist_profiles')
            ->join('users', 'users.id', '=', 'therapist_profiles.user_id')
            ->select('therapist_profiles.id', 'users.name')
            ->orderBy('therapist_profiles.id')
            ->each(function ($row) {
                DB::table('therapist_profiles')->where('id', $row->id)->update([
                    'slug' => Str::slug($row->name).'-'.Str::lower(Str::random(4)),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('therapist_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
