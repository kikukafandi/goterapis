<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dokumen untuk verifikasi manual admin (Filament)
        Schema::create('therapist_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'ktp', 'sertifikat_pelatihan', 'sertifikat_pengalaman', 'stpt', 'foto_tempat', 'rekening',
            ])->index();
            $table->string('path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->string('note')->nullable(); // catatan admin bila ditolak
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapist_documents');
    }
};
