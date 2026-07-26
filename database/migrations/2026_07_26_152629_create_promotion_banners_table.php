<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_banners', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('title', 120);
            $table->string('subtitle', 220)->nullable();
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['sort_order', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_banners');
    }
};
