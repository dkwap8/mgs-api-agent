<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','processing','approved','completed','successful','failed','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('payouts')
            ->whereIn('status', ['approved', 'completed', 'rejected'])
            ->update(['status' => 'failed']);

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','processing','successful','failed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
