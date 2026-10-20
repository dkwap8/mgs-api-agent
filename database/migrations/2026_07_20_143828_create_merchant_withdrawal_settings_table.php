<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'merchant_withdrawal_settings',
            function (Blueprint $table) {

                $table->id();

                /*
                 * Supported merchant types:
                 *
                 * starter
                 * registered
                 */
                $table->string('merchant_type', 50)
                    ->unique();

                $table->boolean('withdrawals_enabled')
                    ->default(true);

                $table->boolean('automatic_withdrawals_enabled')
                    ->default(false);

                $table->decimal(
                    'automatic_withdrawal_limit',
                    15,
                    2
                )->default(0);

                $table->decimal(
                    'minimum_withdrawal_amount',
                    15,
                    2
                )->default(100);

                $table->decimal(
                    'maximum_withdrawal_amount',
                    15,
                    2
                )->nullable();

                $table->decimal(
                    'daily_withdrawal_limit',
                    15,
                    2
                )->nullable();

                $table->boolean('charge_enabled')
                    ->default(false);

                $table->enum(
                    'charge_type',
                    [
                        'percentage',
                        'fixed',
                    ]
                )->default('fixed');

                $table->decimal(
                    'percentage',
                    8,
                    4
                )->default(0);

                $table->decimal(
                    'fixed_amount',
                    15,
                    2
                )->default(0);

                $table->decimal(
                    'maximum_charge',
                    15,
                    2
                )->nullable();

                $table->decimal(
                    'minimum_charge',
                    15,
                    2
                )->nullable();

                /*
                 * deduct:
                 * Fee is deducted from the withdrawal amount.
                 *
                 * add_on:
                 * Withdrawal amount + fee is debited from wallet.
                 */
                $table->enum(
                    'fee_collection_mode',
                    [
                        'deduct',
                        'add_on',
                    ]
                )->default('add_on');

                $table->unsignedInteger(
                    'daily_request_limit'
                )->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                /*
                 * IMPORTANT:
                 * Use a short custom index name.
                 * MariaDB has identifier length limits.
                 */
                $table->index(
                    [
                        'withdrawals_enabled',
                        'automatic_withdrawals_enabled',
                    ],
                    'mws_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'merchant_withdrawal_settings'
        );
    }
};