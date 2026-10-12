<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_schedule_mstr', function (Blueprint $table) {
            //
            $table->string('ssm_cust_code')->nullable()->change();
            $table->string('ssm_cust_desc')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_schedule_mstr', function (Blueprint $table) {
            //
            $table->string('ssm_cust_code')->nullable(false)->change();
            $table->string('ssm_cust_desc')->nullable(false)->change();
        });
    }
};
