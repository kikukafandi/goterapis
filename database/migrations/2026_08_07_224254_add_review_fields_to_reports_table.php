<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('reported_user_id')->nullable()->after('reporter_id')->constrained('users')->nullOnDelete();
            $table->json('evidence')->nullable()->after('detail');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable()->after('reviewed_by');
            $table->timestamp('reviewed_at')->nullable()->after('admin_note');
            $table->unique(['reporter_id', 'reportable_type', 'reportable_id', 'reason'], 'reports_reporter_reportable_reason_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique('reports_reporter_reportable_reason_unique');
            $table->dropConstrainedForeignId('reported_user_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['evidence', 'admin_note', 'reviewed_at']);
        });
    }
};
