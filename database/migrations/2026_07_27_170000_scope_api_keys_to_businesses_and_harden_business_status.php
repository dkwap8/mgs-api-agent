<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE businesses MODIFY verification_status ENUM('pending','under_review','approved','rejected','suspended') NOT NULL DEFAULT 'pending'");
        }
        Schema::table('api_keys', function (Blueprint $table): void {
            if (! Schema::hasColumn('api_keys', 'business_id')) {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('merchant_id')
                    ->constrained('businesses')
                    ->cascadeOnDelete();

                $table->index(
                    ['merchant_id', 'business_id', 'environment', 'status'],
                    'api_keys_business_environment_status_index'
                );
            }
        });

        DB::table('api_keys')
            ->whereNull('business_id')
            ->orderBy('id')
            ->chunkById(200, function ($keys): void {
                foreach ($keys as $key) {
                    $businessId = DB::table('businesses')
                        ->where('merchant_id', $key->merchant_id)
                        ->orderByRaw("CASE WHEN verification_status = 'approved' THEN 0 ELSE 1 END")
                        ->orderBy('id')
                        ->value('id');

                    if ($businessId) {
                        DB::table('api_keys')
                            ->where('id', $key->id)
                            ->update(['business_id' => $businessId]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('businesses')->where('verification_status', 'suspended')->update([
            'verification_status' => 'rejected',
            'is_verified' => false,
        ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE businesses MODIFY verification_status ENUM('pending','under_review','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
        Schema::table('api_keys', function (Blueprint $table): void {
            if (Schema::hasColumn('api_keys', 'business_id')) {
                $table->dropIndex('api_keys_business_environment_status_index');
                $table->dropConstrainedForeignId('business_id');
            }
        });
    }
};
