<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_otps', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();

            $table->decimal('amount', 15, 2);
            $table->string('otp_hash');
            $table->json('payload')->nullable();

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);

            $table->enum('status', [
                'pending',
                'processing',
                'used',
                'expired',
            ])->default('pending');

            $table->timestamp('last_sent_at');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index(
                ['user_id', 'business_id', 'status'],
                'withdrawal_otp_user_business_idx'
            );

            $table->index(
                ['merchant_id', 'created_at'],
                'withdrawal_otp_merchant_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_otps');
    }
};