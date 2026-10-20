<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('status');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_login_at')->nullable()->after('locked_until');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
        });

        Schema::table('login_histories', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->string('email')->nullable()->after('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['ip_address', 'created_at']);
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->unique(['user_id', 'device_id'], 'devices_user_device_unique');
            $table->timestamp('revoked_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropUnique('devices_user_device_unique');
            $table->dropColumn('revoked_at');
        });

        Schema::table('login_histories', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['ip_address', 'created_at']);
            $table->dropColumn('email');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['failed_login_attempts', 'locked_until', 'last_login_at', 'password_changed_at']);
        });
    }
};
