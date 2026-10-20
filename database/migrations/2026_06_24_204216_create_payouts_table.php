<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('fee', 18, 2)->default(0);
            $table->string('narration')->nullable();
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'reversed', 'held']);
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('provider_response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['merchant_id', 'status', 'risk_level']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('payouts');
    }
};