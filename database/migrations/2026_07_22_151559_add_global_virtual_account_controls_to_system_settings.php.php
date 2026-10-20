<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('system_settings', 'virtual_accounts_enabled')) {
                $table->boolean('virtual_accounts_enabled')
                    ->default(true);
            }

            if (! Schema::hasColumn('system_settings', 'maximum_virtual_accounts_per_customer')) {
                $table->unsignedInteger('maximum_virtual_accounts_per_customer')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('system_settings', 'maximum_virtual_accounts_per_customer')) {
                $table->dropColumn('maximum_virtual_accounts_per_customer');
            }

            if (Schema::hasColumn('system_settings', 'virtual_accounts_enabled')) {
                $table->dropColumn('virtual_accounts_enabled');
            }
        });
    }
};
