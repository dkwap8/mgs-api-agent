<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // The asynchronous lifecycle writes "processing"; the original enum excluded it.
        Schema::table('customer_kyc_verifications',function(Blueprint $t){$t->string('status',32)->default('pending')->change();});
    }
    public function down(): void { /* Do not narrow existing asynchronous statuses. */ }
};
