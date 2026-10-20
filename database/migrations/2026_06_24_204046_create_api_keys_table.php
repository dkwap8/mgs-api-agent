<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('public_key')->unique();
            $table->string('secret_key_hash');
            $table->string('webhook_secret')->nullable();
            $table->enum('environment', ['test', 'live'])->default('test');
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->json('ip_whitelist')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('api_keys');
    }
};