<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_kyc_verifications', function (Blueprint $table): void {
            $table->text('identity_number')->nullable()->after('type');
            $table->string('identity_hash', 64)->nullable()->after('identity_number');
            $table->unsignedSmallInteger('attempts')->default(0)->after('status');
            $table->timestamp('next_retry_at')->nullable()->after('attempts');
            $table->timestamp('processing_at')->nullable()->after('next_retry_at');
            $table->timestamp('verified_at')->nullable()->after('processing_at');
            $table->timestamp('failed_at')->nullable()->after('verified_at');
            $table->text('last_error')->nullable()->after('failed_at');
            $table->json('metadata')->nullable()->after('provider_response');
            $table->unique(['customer_id', 'type'], 'customer_kyc_type_unique');
            $table->index(['status', 'next_retry_at'], 'customer_kyc_retry_index');
            $table->index(['identity_hash', 'status'], 'customer_kyc_identity_status_index');
        });

        Schema::table('business_activations', function (Blueprint $table): void {
            $table->string('kyc_status', 32)->default('pending')->after('status');
            $table->foreignId('owner_customer_id')->nullable()->after('user_id')->constrained('customers')->nullOnDelete();
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
            $table->text('kyc_failure_reason')->nullable()->after('kyc_verified_at');
            $table->index(['business_id', 'kyc_status'], 'business_activation_kyc_index');
        });
    }

    public function down(): void
    {
        Schema::table('business_activations', function (Blueprint $table): void {
            $table->dropIndex('business_activation_kyc_index');
            $table->dropConstrainedForeignId('owner_customer_id');
            $table->dropColumn(['kyc_status', 'kyc_verified_at', 'kyc_failure_reason']);
        });

        Schema::table('customer_kyc_verifications', function (Blueprint $table): void {
            $table->dropUnique('customer_kyc_type_unique');
            $table->dropIndex('customer_kyc_retry_index');
            $table->dropIndex('customer_kyc_identity_status_index');
            $table->dropColumn([
                'identity_number', 'identity_hash', 'attempts', 'next_retry_at',
                'processing_at', 'verified_at', 'failed_at', 'last_error', 'metadata',
            ]);
        });
    }
};
