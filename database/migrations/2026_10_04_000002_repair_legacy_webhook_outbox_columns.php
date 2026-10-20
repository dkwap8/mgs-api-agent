<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('webhook_logs')) return;
        $missing = array_filter(['transaction_id', 'url', 'request_headers', 'response_body', 'last_error', 'delivered_at'],
            fn ($column) => ! Schema::hasColumn('webhook_logs', $column));
        if (! $missing) return;
        Schema::table('webhook_logs', function (Blueprint $table) use ($missing): void {
            if (in_array('transaction_id', $missing, true)) $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            if (in_array('url', $missing, true)) $table->string('url', 1000)->nullable();
            if (in_array('request_headers', $missing, true)) $table->json('request_headers')->nullable();
            if (in_array('response_body', $missing, true)) $table->longText('response_body')->nullable();
            if (in_array('last_error', $missing, true)) $table->text('last_error')->nullable();
            if (in_array('delivered_at', $missing, true)) $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        // Retain outbox evidence and additive compatibility fields on rollback.
    }
};
