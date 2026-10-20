<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('payouts', 'business_id')) {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('merchant_id')
                    ->constrained('businesses')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('payouts', 'withdrawal_setting_id')) {
                $table->foreignId('withdrawal_setting_id')
                    ->nullable()
                    ->after('business_id')
                    ->constrained('merchant_withdrawal_settings')
                    ->nullOnDelete();
            }

            $table->index(
                ['merchant_id', 'business_id', 'created_at'],
                'payout_business_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropIndex('payout_business_date_idx');

            if (Schema::hasColumn('payouts', 'withdrawal_setting_id')) {
                $table->dropConstrainedForeignId('withdrawal_setting_id');
            }

            if (Schema::hasColumn('payouts', 'business_id')) {
                $table->dropConstrainedForeignId('business_id');
            }
        });
    }
};