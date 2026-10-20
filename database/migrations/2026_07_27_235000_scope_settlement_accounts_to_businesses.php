<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table): void {
            $table->foreignId('business_id')
                ->nullable()
                ->after('merchant_id')
                ->constrained('businesses')
                ->cascadeOnDelete();
        });

        DB::transaction(function (): void {
            DB::table('bank_accounts')
                ->orderBy('id')
                ->each(function (object $account): void {
                    $businessId = DB::table('businesses')
                        ->where('merchant_id', $account->merchant_id)
                        ->orderByRaw("CASE WHEN verification_status = 'approved' THEN 0 ELSE 1 END")
                        ->orderBy('id')
                        ->value('id');

                    if (! $businessId) {
                        throw new \RuntimeException(
                            "Cannot assign bank account {$account->id}: merchant {$account->merchant_id} has no business."
                        );
                    }

                    DB::table('bank_accounts')
                        ->where('id', $account->id)
                        ->update(['business_id' => $businessId]);
                });
        });

        Schema::table('bank_accounts', function (Blueprint $table): void {
            $table->unique(
                ['merchant_id', 'business_id', 'bank_code', 'account_number'],
                'bank_accounts_business_bank_number_unique'
            );
            $table->index(
                ['merchant_id', 'business_id', 'status', 'is_default'],
                'bank_accounts_business_status_default_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table): void {
            $table->dropUnique('bank_accounts_business_bank_number_unique');
            $table->dropIndex('bank_accounts_business_status_default_index');
            $table->dropConstrainedForeignId('business_id');
        });
    }
};
