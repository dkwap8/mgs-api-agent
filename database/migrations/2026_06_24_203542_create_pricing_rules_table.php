<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['collection', 'payout']);
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->decimal('fee_amount', 18, 2)->nullable();
            $table->decimal('fee_percentage', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('pricing_rules');
    }
};