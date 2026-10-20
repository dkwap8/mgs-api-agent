<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'business_id')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('merchant_id')
                    ->constrained('businesses')
                    ->nullOnDelete();

                $table->index(
                    ['merchant_id', 'business_id', 'status', 'created_at'],
                    'transactions_business_status_created_index'
                );
            });
        }

        DB::table('transactions')
            ->whereNull('business_id')
            ->orderBy('id')
            ->chunkById(200, function ($transactions): void {
                foreach ($transactions as $transaction) {
                    $metadata = json_decode((string) ($transaction->metadata ?? '{}'), true);
                    $businessId = is_array($metadata) ? ($metadata['business_id'] ?? null) : null;

                    if (! $businessId) {
                        $businessId = DB::table('virtual_accounts')
                            ->where('id', $transaction->virtual_account_id)
                            ->value('business_id');
                    }

                    if (! $businessId) {
                        $businessId = DB::table('businesses')
                            ->where('merchant_id', $transaction->merchant_id)
                            ->orderByRaw("CASE WHEN verification_status = 'approved' THEN 0 ELSE 1 END")
                            ->orderBy('id')
                            ->value('id');
                    }

                    if ($businessId) {
                        DB::table('transactions')
                            ->where('id', $transaction->id)
                            ->update(['business_id' => $businessId]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'business_id')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropIndex('transactions_business_status_created_index');
                $table->dropConstrainedForeignId('business_id');
            });
        }
    }
};
