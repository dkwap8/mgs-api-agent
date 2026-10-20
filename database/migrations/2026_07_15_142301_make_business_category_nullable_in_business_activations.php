<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_activations', function (Blueprint $table) {
            $table->string('business_category')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('business_activations', function (Blueprint $table) {
            $table->string('business_category')->nullable(false)->change();
        });
    }
};