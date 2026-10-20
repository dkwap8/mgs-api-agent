<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('business_activations', 'business_id')) {
            return;
        }

        // Repair only records created by the removed buggy controller, which
        // inserted these exact placeholder KYC values and auto-approved them.
        $fakeActivations = DB::table('business_activations')
            ->where('nin', '12345678901')
            ->where('bvn', '12345678901')
            ->where('business_description', 'Business description')
            ->where('proof_of_address', 'proof_of_address.jpg')
            ->get();

        foreach ($fakeActivations as $activation) {
            if ($activation->business_id) {
                DB::table('businesses')->where('id', $activation->business_id)->update([
                    'verification_status' => 'pending',
                    'is_verified' => false,
                    'verified_at' => null,
                    'activation_id' => null,
                    'updated_at' => now(),
                ]);
            }

            DB::table('business_activations')->where('id', $activation->id)->delete();
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: fake KYC records must not be recreated.
    }
};
