<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kyc_identities', function (Blueprint $table) {
            $table->id();
            $table->string('bvn_hash')->nullable()->unique();
            $table->string('nin_hash')->nullable()->unique();
            $table->string('verified_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('verification_provider')->nullable();
            $table->string('verification_reference')->nullable();
            $table->enum('status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('kyc_identities');
    }
};
