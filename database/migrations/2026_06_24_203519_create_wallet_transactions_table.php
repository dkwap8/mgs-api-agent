<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->enum('type', ['credit', 'debit', 'fee', 'refund', 'adjustment']);
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed']);
            $table->decimal('amount', 18, 2);
            $table->decimal('fee', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            
            $table->index(['reference', 'type', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('wallet_transactions');
    }
};