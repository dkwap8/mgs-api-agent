<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('revenues')) Schema::create('revenues', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('merchant_id')->constrained()->restrictOnDelete();
            $t->foreignId('business_id')->constrained()->restrictOnDelete();
            $t->decimal('fee', 18, 2);
            $t->decimal('provider_cost', 18, 2)->nullable();
            $t->string('currency', 3)->default('NGN');
            $t->timestamps();
        });
        if (! Schema::hasColumn('api_logs', 'business_id')) Schema::table('api_logs', function (Blueprint $t) {
            $t->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $t->index(['business_id', 'created_at']);
        });
    }
    public function down(): void {
        // Financial evidence is deliberately retained; use a reviewed forward migration.
    }
};
