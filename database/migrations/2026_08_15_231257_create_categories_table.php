<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Label kategori yang selama ini dikeraskan di controller dan view. */
    private const AWAL = [
        'pijat' => 'Pijat',
        'bekam' => 'Bekam',
        'kretek' => 'Kretek',
        'refleksi' => 'Refleksi',
        'lainnya' => 'Kerik & Totok',
    ];

    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('icon_path')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // Enum mengunci daftar kategori di level skema; admin tidak akan pernah bisa
        // menambah kategori baru selama kolomnya masih enum.
        Schema::table('services', function (Blueprint $table) {
            // Indeks lama ikut bertahan saat tipe kolom diubah, jadi tak perlu dipasang ulang.
            $table->string('category')->change();
        });

        $urutan = 0;
        foreach (self::AWAL as $slug => $name) {
            DB::table('categories')->insert([
                'slug' => $slug,
                'name' => $name,
                'position' => $urutan++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');

        Schema::table('services', function (Blueprint $table) {
            $table->enum('category', array_keys(self::AWAL))->change();
        });
    }
};
