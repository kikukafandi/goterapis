<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('weight_grams')->nullable();
            $table->string('origin')->nullable();
            $table->text('storage_instructions')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_promoted')->default(false);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
