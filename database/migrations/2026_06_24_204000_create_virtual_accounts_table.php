<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained();
            $table->string('provider_customer_id')->nullable();
            $table->string('account_number');
            $table->string('account_name');
            $table->string('bank_name');
            $table->string('bank_code')->nullable();
            $table->string('reference')->unique();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_test')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['merchant_id', 'customer_id', 'status']);
            $table->unique(['provider_id', 'account_number']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('virtual_accounts');
    }
};