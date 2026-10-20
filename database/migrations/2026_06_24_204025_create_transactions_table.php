<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('virtual_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('fee', 18, 2)->default(0);
            $table->decimal('merchant_amount', 18, 2);
            $table->enum('type', ['collection', 'payout', 'refund']);
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'reversed']);
            $table->string('description')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['merchant_id', 'reference', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};