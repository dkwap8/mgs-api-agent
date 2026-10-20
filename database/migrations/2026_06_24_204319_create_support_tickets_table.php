<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('message');
            $table->string('category', 50)->default('general');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['status', 'priority', 'created_at'], 'support_tickets_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
