<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->nullable()->constrained('webhook_endpoints')->nullOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 100);
            $table->string('url', 1000);
            $table->json('payload')->nullable();
            $table->json('request_headers')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedInteger('attempt')->default(1);
            $table->string('status', 30)->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'created_at'], 'webhook_logs_merchant_created_idx');
            $table->index(['status', 'next_retry_at'], 'webhook_logs_status_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
