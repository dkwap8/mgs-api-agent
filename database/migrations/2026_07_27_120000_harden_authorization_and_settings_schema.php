<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('system_settings', 'key')) {
                $table->string('key')->nullable();
            }
            if (! Schema::hasColumn('system_settings', 'value')) {
                $table->text('value')->nullable();
            }
            if (! Schema::hasColumn('system_settings', 'group')) {
                $table->string('group')->default('general');
            }
        });

        if (Schema::hasColumn('system_settings', 'app_name')) {
            $legacy = DB::table('system_settings')->whereNull('key')->first();
            if ($legacy) {
                DB::table('system_settings')->where('id', $legacy->id)->update([
                    'key' => 'app_name',
                    'value' => $legacy->app_name ?: 'MGS API',
                    'group' => 'general',
                ]);
            }
        }

        $this->addUniqueIndexIfMissing('system_settings', ['key'], 'system_settings_key_unique');
        $this->addUniqueIndexIfMissing('role_user', ['role_id', 'user_id'], 'role_user_role_user_unique');
        $this->addUniqueIndexIfMissing('permission_role', ['permission_id', 'role_id'], 'permission_role_permission_role_unique');
        $this->addUniqueIndexIfMissing('wallets', ['merchant_id', 'currency'], 'wallets_merchant_currency_unique');

        if (Schema::hasTable('webhook_endpoints') && ! Schema::hasColumn('webhook_endpoints', 'business_id')) {
            Schema::table('webhook_endpoints', function (Blueprint $table): void {
                $table->foreignId('business_id')->nullable()->after('merchant_id')->constrained('businesses')->cascadeOnDelete();
                $table->index(['merchant_id', 'business_id'], 'webhook_endpoints_merchant_business_idx');
            });
        }

        if (Schema::hasTable('webhook_logs') && ! Schema::hasColumn('webhook_logs', 'business_id')) {
            Schema::table('webhook_logs', function (Blueprint $table): void {
                $table->foreignId('business_id')->nullable()->after('merchant_id')->constrained('businesses')->cascadeOnDelete();
                $table->index(['merchant_id', 'business_id', 'created_at'], 'webhook_logs_merchant_business_created_idx');
            });
        }

        if (Schema::hasTable('payouts') && Schema::hasColumn('payouts', 'approved_by')) {
            $this->normalisePayoutApproverForeignKey();
        }
    }

    public function down(): void
    {
        // Deliberately retain integrity constraints and key/value settings data.
    }

    private function addUniqueIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->unique($columns, $name);
        });
    }

    private function normalisePayoutApproverForeignKey(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('UPDATE payouts p LEFT JOIN users u ON u.id = p.approved_by SET p.approved_by = NULL WHERE p.approved_by IS NOT NULL AND u.id IS NULL');

            $foreign = DB::selectOne("SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payouts' AND COLUMN_NAME = 'approved_by' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1");
            if ($foreign?->name) {
                DB::statement('ALTER TABLE payouts DROP FOREIGN KEY `'.str_replace('`', '``', $foreign->name).'`');
            }

            DB::statement('ALTER TABLE payouts ADD CONSTRAINT payouts_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL');
        }
    }
};
