<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('accepted');
            $table->string('proof_path')->nullable();
            $table->text('student_remark')->nullable();
            $table->text('admin_remark')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'status',
                'proof_path',
                'student_remark',
                'admin_remark',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};
