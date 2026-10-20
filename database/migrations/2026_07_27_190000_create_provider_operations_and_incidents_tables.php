<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_operation_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('operation', 80);
            $table->string('reference')->nullable();
            $table->string('status', 30);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('attempted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['provider_id', 'operation', 'attempted_at'], 'provider_operation_lookup');
            $table->index(['status', 'attempted_at']);
            $table->index('reference');
        });

        Schema::create('provider_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->string('severity', 20)->default('warning');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['provider_id', 'status', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_incidents');
        Schema::dropIfExists('provider_operation_attempts');
    }
};
