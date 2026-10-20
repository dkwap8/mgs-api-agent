<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_funding_charges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->boolean('is_enabled')
                ->default(false);

            $table->enum('charge_type', [
                'percentage',
                'fixed',
            ])->default('percentage');

            /*
             * Percentage values are stored normally.
             *
             * Example:
             * 1.00 = 1%
             * 1.50 = 1.5%
             */
            $table->decimal('percentage', 8, 4)
                ->default(0);

            $table->decimal('fixed_amount', 15, 2)
                ->default(0);

            $table->decimal('maximum_charge', 15, 2)
                ->nullable();

            $table->decimal('minimum_charge', 15, 2)
                ->nullable();

            /*
             * Determines how the charge is collected:
             *
             * add_on:
             * Customer pays amount + charge.
             * Merchant wallet receives the full funding amount.
             *
             * deduct:
             * Customer pays the funding amount.
             * Wallet receives amount - charge.
             */
            $table->enum('collection_mode', [
                'add_on',
                'deduct',
            ])->default('add_on');

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->unique('provider_id');
            $table->index([
                'is_enabled',
                'charge_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_funding_charges');
    }
};