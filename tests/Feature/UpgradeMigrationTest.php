<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpgradeMigrationTest extends TestCase
{
    public function test_legacy_webhook_outbox_upgrade_is_additive_and_repeatable(): void
    {
        config(['database.connections.upgrade_outbox_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $original = DB::getDefaultConnection();
        DB::setDefaultConnection('upgrade_outbox_test');
        try {
            Schema::create('transactions', fn ($table) => $table->id());
            Schema::create('webhook_logs', function ($table) { $table->id(); $table->string('event'); $table->text('payload'); });
            $id = DB::table('webhook_logs')->insertGetId(['event' => 'legacy.event', 'payload' => '{"preserved":true}']);
            $migration = require database_path('migrations/2026_10_04_000002_repair_legacy_webhook_outbox_columns.php');
            $migration->up();
            $migration->up();
            foreach (['transaction_id', 'url', 'request_headers', 'response_body', 'last_error', 'delivered_at'] as $column) $this->assertTrue(Schema::hasColumn('webhook_logs', $column));
            $this->assertSame('{"preserved":true}', DB::table('webhook_logs')->where('id', $id)->value('payload'));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('upgrade_outbox_test');
        }
    }
    public function test_mfa_upgrade_preserves_existing_administrator_and_schema(): void
    {
        config(['database.connections.upgrade_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $original = DB::getDefaultConnection();
        DB::setDefaultConnection('upgrade_test');
        try {
            $paths = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($path) => ! str_contains($path, '2026_10_04_000001')));
            Artisan::call('migrate', ['--path' => $paths, '--realpath' => true, '--force' => true]);
            $id = DB::table('admins')->insertGetId(['name' => 'Existing admin', 'email' => 'existing@example.test', 'password' => 'retained-hash', 'role' => 'super_admin', 'status' => 'active']);
            $before = Schema::getTableListing();
            Artisan::call('migrate', ['--path' => database_path('migrations/2026_10_04_000001_add_administrator_mfa.php'), '--realpath' => true, '--force' => true]);
            $this->assertSame('retained-hash', DB::table('admins')->where('id', $id)->value('password'));
            $this->assertSame($before, Schema::getTableListing());
            $this->assertTrue(Schema::hasColumn('admins', 'two_factor_secret'));
            $this->assertNull(DB::table('admins')->where('id', $id)->value('two_factor_confirmed_at'));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('upgrade_test');
        }
    }
}
