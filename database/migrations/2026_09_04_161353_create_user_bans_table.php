<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('unbanned_at')->nullable();
            $table->foreignId('unbanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bans');
    }
};
