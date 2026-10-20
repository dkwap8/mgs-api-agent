<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('business_type', ['starter', 'registered'])->default('starter');
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('under_review');

            $table->string('business_name');
            $table->string('business_email');
            $table->string('business_phone');
            $table->string('business_category');
            $table->text('business_description');

            $table->string('cac_number')->nullable();
            $table->string('cac_certificate')->nullable();
            $table->string('cac_status_report')->nullable();

            $table->string('country')->default('Nigeria');
            $table->string('state');
            $table->string('city');
            $table->string('street_address');
            $table->string('landmark')->nullable();
            $table->string('proof_of_address');

            $table->string('nin', 11);
            $table->string('bvn', 11);

            $table->string('bank_name');
            $table->string('account_number', 10);
            $table->string('account_name');

            $table->boolean('accept_terms')->default(false);

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_activations');
    }
};