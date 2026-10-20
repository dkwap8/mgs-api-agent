<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Add business_id if the previous failed migration has not
         * already created it.
         */
        if (! Schema::hasColumn('wallets', 'business_id')) {
            Schema::table('wallets', function (Blueprint $table): void {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('merchant_id')
                    ->constrained('businesses')
                    ->cascadeOnDelete();
            });
        }

        /*
         * MariaDB may currently be using the old composite unique index
         * to support the merchant_id foreign key.
         *
         * Create a standalone merchant_id index before removing the old
         * merchant_id + currency unique index.
         */
        if (! $this->indexExists(
            'wallets',
            'wallets_merchant_id_index'
        )) {
            Schema::table('wallets', function (Blueprint $table): void {
                $table->index(
                    'merchant_id',
                    'wallets_merchant_id_index'
                );
            });
        }

        /*
         * Remove the old merchant-level unique indexes before creating
         * additional business wallets.
         */
        $this->dropIndexIfExists(
            'wallets',
            'wallets_merchant_currency_unique'
        );

        $this->dropIndexIfExists(
            'wallets',
            'wallets_merchant_id_currency_unique'
        );

        DB::transaction(function (): void {
            /*
             * Assign existing wallets to the merchant's current business,
             * or to their first business when no current business exists.
             */
            DB::table('wallets')
                ->select([
                    'id',
                    'merchant_id',
                    'business_id',
                ])
                ->orderBy('id')
                ->chunkById(100, function ($wallets): void {
                    foreach ($wallets as $wallet) {
                        if ($wallet->business_id !== null) {
                            continue;
                        }

                        $businessId = DB::table('users')
                            ->join(
                                'merchants',
                                'merchants.user_id',
                                '=',
                                'users.id'
                            )
                            ->where(
                                'merchants.id',
                                $wallet->merchant_id
                            )
                            ->value('users.current_business_id');

                        /*
                         * Confirm that the selected current business
                         * belongs to the same merchant.
                         */
                        if ($businessId) {
                            $belongsToMerchant = DB::table('businesses')
                                ->where('id', $businessId)
                                ->where(
                                    'merchant_id',
                                    $wallet->merchant_id
                                )
                                ->exists();

                            if (! $belongsToMerchant) {
                                $businessId = null;
                            }
                        }

                        if (! $businessId) {
                            $businessId = DB::table('businesses')
                                ->where(
                                    'merchant_id',
                                    $wallet->merchant_id
                                )
                                ->orderBy('id')
                                ->value('id');
                        }

                        if ($businessId) {
                            DB::table('wallets')
                                ->where('id', $wallet->id)
                                ->update([
                                    'business_id' => $businessId,
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                });

            /*
             * Create an NGN wallet for every business that does not
             * already have one.
             */
            DB::table('businesses')
                ->select([
                    'id',
                    'merchant_id',
                ])
                ->orderBy('id')
                ->chunkById(100, function ($businesses): void {
                    foreach ($businesses as $business) {
                        $exists = DB::table('wallets')
                            ->where(
                                'merchant_id',
                                $business->merchant_id
                            )
                            ->where(
                                'business_id',
                                $business->id
                            )
                            ->where('currency', 'NGN')
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        DB::table('wallets')->insert([
                            'merchant_id' => $business->merchant_id,
                            'business_id' => $business->id,
                            'currency' => 'NGN',
                            'available_balance' => 0,
                            'ledger_balance' => 0,
                            'reserved_balance' => 0,
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        });

        /*
         * Add the new business-scoped unique constraint.
         */
        if (! $this->indexExists(
            'wallets',
            'wallets_merchant_business_currency_unique'
        )) {
            Schema::table('wallets', function (Blueprint $table): void {
                $table->unique(
                    [
                        'merchant_id',
                        'business_id',
                        'currency',
                    ],
                    'wallets_merchant_business_currency_unique'
                );
            });
        }

        if (! $this->indexExists(
            'wallets',
            'wallets_merchant_business_status_index'
        )) {
            Schema::table('wallets', function (Blueprint $table): void {
                $table->index(
                    [
                        'merchant_id',
                        'business_id',
                        'status',
                    ],
                    'wallets_merchant_business_status_index'
                );
            });
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists(
            'wallets',
            'wallets_merchant_business_currency_unique'
        );

        $this->dropIndexIfExists(
            'wallets',
            'wallets_merchant_business_status_index'
        );

        if (Schema::hasColumn('wallets', 'business_id')) {
            Schema::table('wallets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('business_id');
            });
        }

        /*
         * Keep the standalone merchant_id index because it may still be
         * required by the merchant_id foreign key.
         */
    }

    private function indexExists(
        string $table,
        string $index
    ): bool {
        return Schema::hasIndex($table, $index);
    }

    private function dropIndexIfExists(
        string $table,
        string $index
    ): void {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }
};