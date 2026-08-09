<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('allowed_gender')->nullable()->after('category');
        });

        $obsoleteIds = DB::table('services')
            ->where('category', 'refleksi')
            ->orWhereIn('slug', ['pijat-relaksasi', 'relaksasi', 'bekam-kering', 'totok-wajah'])
            ->pluck('id');

        DB::table('services')->whereIn('id', $obsoleteIds)->update(['is_active' => false]);
        DB::table('therapist_service')->whereIn('service_id', $obsoleteIds)->delete();

        DB::table('services')->updateOrInsert(
            ['slug' => 'spot-massage'],
            ['name' => 'Spot Massage', 'category' => 'pijat', 'allowed_gender' => null, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
        );
        DB::table('services')->updateOrInsert(
            ['slug' => 'spa-massage'],
            ['name' => 'Spa Massage', 'category' => 'pijat', 'allowed_gender' => 'wanita', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('allowed_gender');
        });
    }
};
