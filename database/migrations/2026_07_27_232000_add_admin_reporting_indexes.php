<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->index(['business_id', 'status', 'created_at'], 'transactions_business_status_created_idx');
            $table->index(['provider_id', 'status', 'created_at'], 'transactions_provider_status_created_idx');
            $table->index(['merchant_id', 'status', 'created_at'], 'transactions_merchant_status_created_idx');
            $table->index(['type', 'status', 'created_at'], 'transactions_type_status_created_idx');
        });

        Schema::table('payouts', function (Blueprint $table): void {
            $table->index(['business_id', 'status', 'created_at'], 'payouts_business_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_business_status_created_idx');
            $table->dropIndex('transactions_provider_status_created_idx');
            $table->dropIndex('transactions_merchant_status_created_idx');
            $table->dropIndex('transactions_type_status_created_idx');
        });

        Schema::table('payouts', function (Blueprint $table): void {
            $table->dropIndex('payouts_business_status_created_idx');
        });
    }
};
