<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('payouts', 'net_amount')) {
                $table->decimal('net_amount', 15, 2)
                    ->nullable()
                    ->after('fee');
            }

            if (! Schema::hasColumn('payouts', 'processing_mode')) {
                $table->enum('processing_mode', [
                    'automatic',
                    'manual',
                ])
                    ->default('manual')
                    ->after('risk_level');
            }

            if (! Schema::hasColumn('payouts', 'review_reason')) {
                $table->string('review_reason', 500)
                    ->nullable()
                    ->after('processing_mode');
            }

            if (! Schema::hasColumn('payouts', 'failure_reason')) {
                $table->text('failure_reason')
                    ->nullable()
                    ->after('review_reason');
            }

            if (! Schema::hasColumn('payouts', 'funds_reserved_at')) {
                $table->timestamp('funds_reserved_at')
                    ->nullable()
                    ->after('approved_by');
            }

            if (! Schema::hasColumn('payouts', 'processed_at')) {
                $table->timestamp('processed_at')
                    ->nullable()
                    ->after('funds_reserved_at');
            }

            if (! Schema::hasColumn('payouts', 'refunded_at')) {
                $table->timestamp('refunded_at')
                    ->nullable()
                    ->after('processed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $columns = [
                'net_amount',
                'processing_mode',
                'review_reason',
                'failure_reason',
                'funds_reserved_at',
                'processed_at',
                'refunded_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('payouts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};