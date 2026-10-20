<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasColumn('api_keys','secret_key_fingerprint')) Schema::table('api_keys',function(Blueprint $t){
            $t->string('secret_key_fingerprint',64)->nullable()->unique();
        });
        if (! Schema::hasColumn('bank_accounts','withdrawals_available_at')) Schema::table('bank_accounts',function(Blueprint $t){
            $t->timestamp('withdrawals_available_at')->nullable();
        });
        if (! Schema::hasTable('bank_change_challenges')) Schema::create('bank_change_challenges',function(Blueprint $t){
            $t->id(); $t->uuid('reference')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('bank_code',3); $t->string('account_number',10);
            $t->string('otp_hash'); $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('expires_at'); $t->timestamp('consumed_at')->nullable(); $t->timestamps();
        });
    }
    public function down(): void { /* Retain security history and existing key/account data. */ }
};
