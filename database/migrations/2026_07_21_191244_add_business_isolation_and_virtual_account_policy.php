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
         * CUSTOMERS
         */
        if (! Schema::hasColumn('customers', 'business_id')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('merchant_id')
                    ->constrained('businesses')
                    ->cascadeOnDelete();
            });
        }

        $this->backfillCustomerBusinesses();

        if ($this->indexExists(
            'customers',
            'customers_merchant_id_email_unique'
        )) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->dropUnique(
                    'customers_merchant_id_email_unique'
                );
            });
        }

        if (! $this->indexExists(
            'customers',
            'customers_merchant_business_idx'
        )) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->index(
                    ['merchant_id', 'business_id'],
                    'customers_merchant_business_idx'
                );
            });
        }

        if (! $this->indexExists(
            'customers',
            'customers_business_email_uq'
        )) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->unique(
                    ['business_id', 'email'],
                    'customers_business_email_uq'
                );
            });
        }

        if (! $this->indexExists(
            'customers',
            'customers_business_phone_uq'
        )) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->unique(
                    ['business_id', 'phone'],
                    'customers_business_phone_uq'
                );
            });
        }

        if (! $this->indexExists(
            'customers',
            'customers_business_reference_uq'
        )) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->unique(
                    ['business_id', 'external_reference'],
                    'customers_business_reference_uq'
                );
            });
        }

        /*
         * VIRTUAL ACCOUNTS
         */
        if (! Schema::hasColumn('virtual_accounts', 'business_id')) {
            Schema::table('virtual_accounts', function (Blueprint $table): void {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('merchant_id')
                    ->constrained('businesses')
                    ->cascadeOnDelete();
            });
        }

        $this->backfillVirtualAccountBusinesses();

        if (! $this->indexExists(
            'virtual_accounts',
            'virtual_accounts_business_status_idx'
        )) {
            Schema::table('virtual_accounts', function (Blueprint $table): void {
                $table->index(
                    ['merchant_id', 'business_id', 'status'],
                    'virtual_accounts_business_status_idx'
                );
            });
        }

        if (! $this->indexExists(
            'virtual_accounts',
            'virtual_accounts_customer_status_idx'
        )) {
            Schema::table('virtual_accounts', function (Blueprint $table): void {
                $table->index(
                    ['business_id', 'customer_id', 'status'],
                    'virtual_accounts_customer_status_idx'
                );
            });
        }

        /*
         * BUSINESS POLICY
         */
        if (! Schema::hasColumn(
            'businesses',
            'auto_generate_virtual_account'
        )) {
            Schema::table('businesses', function (Blueprint $table): void {
                $table->boolean('auto_generate_virtual_account')
                    ->default(true);
            });
        }

        if (! Schema::hasColumn(
            'businesses',
            'virtual_account_requirement'
        )) {
            Schema::table('businesses', function (Blueprint $table): void {
                $table->enum('virtual_account_requirement', [
                    'none',
                    'bvn',
                    'nin',
                    'bvn_or_nin',
                    'bvn_and_nin',
                ])->default('none');
            });
        }

        if (! Schema::hasColumn(
            'businesses',
            'allow_multiple_virtual_accounts'
        )) {
            Schema::table('businesses', function (Blueprint $table): void {
                $table->boolean('allow_multiple_virtual_accounts')
                    ->default(false);
            });
        }

        if (! Schema::hasColumn(
            'businesses',
            'preferred_virtual_account_provider_id'
        )) {
            Schema::table('businesses', function (Blueprint $table): void {
                $table->foreignId(
                    'preferred_virtual_account_provider_id'
                )
                    ->nullable()
                    ->constrained('providers')
                    ->nullOnDelete();
            });
        }

        /*
         * SYSTEM POLICY
         *
         * No ->after(...) calls are used here because existing installations
         * may have different system_settings columns.
         */
        if (! Schema::hasColumn(
            'system_settings',
            'enforce_virtual_account_policy'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->boolean('enforce_virtual_account_policy')
                    ->default(false);
            });
        }

        if (! Schema::hasColumn(
            'system_settings',
            'virtual_account_requirement'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->enum('virtual_account_requirement', [
                    'none',
                    'bvn',
                    'nin',
                    'bvn_or_nin',
                    'bvn_and_nin',
                ])->default('none');
            });
        }

        if (! Schema::hasColumn(
            'system_settings',
            'default_auto_generate_virtual_account'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->boolean(
                    'default_auto_generate_virtual_account'
                )->default(true);
            });
        }

        if (! Schema::hasColumn(
            'system_settings',
            'default_allow_multiple_virtual_accounts'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->boolean(
                    'default_allow_multiple_virtual_accounts'
                )->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(
            'system_settings',
            'default_allow_multiple_virtual_accounts'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->dropColumn(
                    'default_allow_multiple_virtual_accounts'
                );
            });
        }

        if (Schema::hasColumn(
            'system_settings',
            'default_auto_generate_virtual_account'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->dropColumn(
                    'default_auto_generate_virtual_account'
                );
            });
        }

        if (Schema::hasColumn(
            'system_settings',
            'virtual_account_requirement'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->dropColumn('virtual_account_requirement');
            });
        }

        if (Schema::hasColumn(
            'system_settings',
            'enforce_virtual_account_policy'
        )) {
            Schema::table('system_settings', function (Blueprint $table): void {
                $table->dropColumn(
                    'enforce_virtual_account_policy'
                );
            });
        }

        if (Schema::hasColumn(
            'businesses',
            'preferred_virtual_account_provider_id'
        )) {
            Schema::table('businesses', function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'preferred_virtual_account_provider_id'
                );
            });
        }

        foreach ([
            'allow_multiple_virtual_accounts',
            'virtual_account_requirement',
            'auto_generate_virtual_account',
        ] as $column) {
            if (Schema::hasColumn('businesses', $column)) {
                Schema::table('businesses', function (
                    Blueprint $table
                ) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }

        foreach ([
            'virtual_accounts_customer_status_idx',
            'virtual_accounts_business_status_idx',
        ] as $index) {
            if ($this->indexExists('virtual_accounts', $index)) {
                Schema::table('virtual_accounts', function (
                    Blueprint $table
                ) use ($index): void {
                    $table->dropIndex($index);
                });
            }
        }

        if (Schema::hasColumn('virtual_accounts', 'business_id')) {
            Schema::table('virtual_accounts', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('business_id');
            });
        }

        foreach ([
            'customers_business_reference_uq',
            'customers_business_phone_uq',
            'customers_business_email_uq',
            'customers_merchant_business_idx',
        ] as $index) {
            if ($this->indexExists('customers', $index)) {
                Schema::table('customers', function (
                    Blueprint $table
                ) use ($index): void {
                    $table->dropIndex($index);
                });
            }
        }

        if (Schema::hasColumn('customers', 'business_id')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('business_id');
            });
        }

        if (! $this->indexExists(
            'customers',
            'customers_merchant_id_email_unique'
        )) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->unique(
                    ['merchant_id', 'email'],
                    'customers_merchant_id_email_unique'
                );
            });
        }
    }

    private function backfillCustomerBusinesses(): void
    {
        $businesses = DB::table('businesses')
            ->select('merchant_id', DB::raw('MIN(id) as business_id'))
            ->groupBy('merchant_id')
            ->havingRaw('COUNT(*) = 1')
            ->get();

        foreach ($businesses as $business) {
            DB::table('customers')
                ->where('merchant_id', $business->merchant_id)
                ->whereNull('business_id')
                ->update(['business_id' => $business->business_id]);
        }
    }

    private function backfillVirtualAccountBusinesses(): void
    {
        DB::table('virtual_accounts')
            ->whereNull('business_id')
            ->orderBy('id')
            ->chunkById(500, function ($accounts): void {
                $customerIds = $accounts->pluck('customer_id')->filter()->unique();
                $businesses = DB::table('customers')
                    ->whereIn('id', $customerIds)
                    ->pluck('business_id', 'id');

                foreach ($accounts as $account) {
                    $businessId = $businesses[$account->customer_id] ?? null;
                    if ($businessId) {
                        DB::table('virtual_accounts')
                            ->where('id', $account->id)
                            ->update(['business_id' => $businessId]);
                    }
                }
            });
    }

    private function indexExists(string $table, string $index): bool
    {
        return Schema::hasIndex($table, $index);
    }
};
