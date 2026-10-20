<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_pending_secret')->nullable();
            $table->unsignedBigInteger('two_factor_last_counter')->nullable();
        });
    }
    public function down(): void
    {
        // Preserve authentication state during an application rollback.
    }
};
