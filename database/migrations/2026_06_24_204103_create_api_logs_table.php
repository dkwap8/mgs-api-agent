<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('api_key_id')->nullable();
            $table->string('endpoint');
            $table->string('method');
            $table->integer('status_code');
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
            
            $table->index(['merchant_id', 'endpoint', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('api_logs');
    }
};