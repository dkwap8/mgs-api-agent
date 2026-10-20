<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('business_email')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_address')->nullable();
            $table->string('cac_number')->nullable();
            $table->string('cac_document')->nullable();
            $table->enum('verification_status', ['pending', 'under_review', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->boolean('live_access')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['verification_status', 'is_verified']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('merchants');
    }
};