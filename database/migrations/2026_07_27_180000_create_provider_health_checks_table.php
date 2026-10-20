<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('checked_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'checked_at']);
            $table->index(['provider_id', 'status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_health_checks');
    }
};
