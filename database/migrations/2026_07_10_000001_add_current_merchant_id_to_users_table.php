<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a persistent "active business" pointer to the users table so the
     * currently selected merchant survives across requests/devices for a
     * Bearer-token (Sanctum) SPA where there is no server session to rely on.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_merchant_id')
                ->nullable()
                ->after('id')
                ->constrained('merchants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_merchant_id');
        });
    }
};
