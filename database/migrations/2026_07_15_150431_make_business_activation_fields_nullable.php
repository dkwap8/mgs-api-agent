<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_activations', function (Blueprint $table) {
            // Make all fields nullable that don't need to be required
            $table->string('business_type')->nullable()->change();
            $table->string('business_category')->nullable()->change();
            $table->text('business_description')->nullable()->change();
            $table->string('country')->nullable()->change();
            $table->string('state')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('street_address')->nullable()->change();
            $table->string('landmark')->nullable()->change();
            $table->string('cac_number')->nullable()->change();
            $table->string('cac_certificate')->nullable()->change();
            $table->string('cac_status_report')->nullable()->change();
            $table->string('proof_of_address')->nullable()->change();
            $table->string('nin')->nullable()->change();
            $table->string('bvn')->nullable()->change();
            $table->string('bank_name')->nullable()->change();
            $table->string('account_number')->nullable()->change();
            $table->string('account_name')->nullable()->change();
            $table->boolean('accept_terms')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('business_activations', function (Blueprint $table) {
            $table->string('business_type')->nullable(false)->change();
            $table->string('business_category')->nullable(false)->change();
            $table->text('business_description')->nullable(false)->change();
            $table->string('country')->nullable(false)->change();
            $table->string('state')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('street_address')->nullable(false)->change();
            $table->string('landmark')->nullable(false)->change();
            $table->string('cac_number')->nullable(false)->change();
            $table->string('cac_certificate')->nullable(false)->change();
            $table->string('cac_status_report')->nullable(false)->change();
            $table->string('proof_of_address')->nullable(false)->change();
            $table->string('nin')->nullable(false)->change();
            $table->string('bvn')->nullable(false)->change();
            $table->string('bank_name')->nullable(false)->change();
            $table->string('account_number')->nullable(false)->change();
            $table->string('account_name')->nullable(false)->change();
            $table->boolean('accept_terms')->nullable(false)->change();
        });
    }
};