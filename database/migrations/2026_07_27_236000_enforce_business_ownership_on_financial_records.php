<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['wallets', 'bank_accounts', 'api_keys'] as $table) {
            if (DB::table($table)->whereNull('business_id')->exists()) {
                throw new RuntimeException(
                    "Cannot enforce {$table}.business_id because unassigned records still exist."
                );
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets MODIFY business_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE bank_accounts MODIFY business_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE api_keys MODIFY business_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('wallets', fn (Blueprint $table) => $table->unsignedBigInteger('business_id')->nullable(false)->change());
            Schema::table('bank_accounts', fn (Blueprint $table) => $table->unsignedBigInteger('business_id')->nullable(false)->change());
            Schema::table('api_keys', fn (Blueprint $table) => $table->unsignedBigInteger('business_id')->nullable(false)->change());
        }

        Schema::table('api_keys', function (Blueprint $table): void {
            $table->index(
                ['merchant_id', 'business_id', 'status', 'environment'],
                'api_keys_merchant_business_status_environment_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            $table->dropIndex('api_keys_merchant_business_status_environment_index');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets MODIFY business_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE bank_accounts MODIFY business_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE api_keys MODIFY business_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('wallets', fn (Blueprint $table) => $table->unsignedBigInteger('business_id')->nullable()->change());
            Schema::table('bank_accounts', fn (Blueprint $table) => $table->unsignedBigInteger('business_id')->nullable()->change());
            Schema::table('api_keys', fn (Blueprint $table) => $table->unsignedBigInteger('business_id')->nullable()->change());
        }
    }
};
