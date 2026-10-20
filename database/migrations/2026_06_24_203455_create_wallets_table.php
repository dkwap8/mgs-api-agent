<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('NGN');
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('ledger_balance', 18, 2)->default(0);
            $table->decimal('reserved_balance', 18, 2)->default(0);
            $table->enum('status', ['active', 'frozen', 'suspended'])->default('active');
            $table->timestamps();

            $table->unique(['merchant_id', 'currency']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('wallets');
    }
};