<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kyc_identity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_reference')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('kyc_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['merchant_id', 'external_reference']);
            $table->unique(['merchant_id', 'email']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('customers');
    }
};