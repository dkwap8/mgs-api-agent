<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_business_id')) {
                $table->foreignId('current_business_id')->nullable()->after('current_merchant_id')
                    ->constrained('businesses')->nullOnDelete();
            }
        });

        Schema::table('business_activations', function (Blueprint $table) {
            if (! Schema::hasColumn('business_activations', 'business_id')) {
                $table->foreignId('business_id')->nullable()->after('merchant_id')
                    ->constrained('businesses')->nullOnDelete();
                $table->index(['business_id', 'status']);
            }
        });

        // Ensure every existing merchant has at least one business row.
        DB::table('merchants')->orderBy('id')->each(function ($merchant) {
            $business = DB::table('businesses')
                ->where('merchant_id', $merchant->id)
                ->oldest('id')
                ->first();

            if (! $business) {
                $code = 'MGS'.str_pad((string) $merchant->id, 6, '0', STR_PAD_LEFT);
                while (DB::table('businesses')->where('business_code', $code)->exists()) {
                    $code = 'MGS'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
                }

                $businessId = DB::table('businesses')->insertGetId([
                    'merchant_id' => $merchant->id,
                    'name' => $merchant->business_name ?: 'My Business',
                    'business_code' => $code,
                    'verification_status' => $merchant->is_verified ? 'approved' : 'pending',
                    'is_verified' => (bool) $merchant->is_verified,
                    'verified_at' => $merchant->verified_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')->where('id', $merchant->user_id)
                    ->whereNull('current_business_id')
                    ->update(['current_business_id' => $businessId]);
            } else {
                DB::table('users')->where('id', $merchant->user_id)
                    ->whereNull('current_business_id')
                    ->update(['current_business_id' => $business->id]);
            }
        });

        // Link old activation rows to matching existing businesses where possible.
        DB::table('business_activations')->orderBy('id')->each(function ($activation) {
            if ($activation->business_id) {
                return;
            }

            $business = DB::table('businesses')
                ->where('merchant_id', $activation->merchant_id)
                ->where('name', $activation->business_name)
                ->first();

            if ($business) {
                DB::table('business_activations')->where('id', $activation->id)
                    ->update(['business_id' => $business->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_activations', function (Blueprint $table) {
            if (Schema::hasColumn('business_activations', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id', 'status']);
                $table->dropColumn('business_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_business_id')) {
                $table->dropForeign(['current_business_id']);
                $table->dropColumn('current_business_id');
            }
        });
    }
};
