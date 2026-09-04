<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->index();
        });

        Schema::create('deactivation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->boolean('pending_key')->nullable()->default(true);
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'pending_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deactivation_requests');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('deactivated_at'));
    }
};
