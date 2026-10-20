<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Preserve legacy user references; do not guess which historic IDs were admins.
        Schema::table('payouts', fn (Blueprint $table) => $table->foreignId('approved_by_admin_id')->nullable()->constrained('admins')->nullOnDelete());
        Schema::table('business_activations', function (Blueprint $table): void {
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('rejected_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
        });
        foreach (['audit_logs', 'activity_logs'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete());
        }
    }
    public function down(): void
    {
        Schema::table('payouts', fn (Blueprint $table) => $table->dropConstrainedForeignId('approved_by_admin_id'));
        Schema::table('business_activations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by_admin_id');
            $table->dropConstrainedForeignId('rejected_by_admin_id');
        });
        foreach (['audit_logs', 'activity_logs'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('admin_id'));
        }
    }
};
