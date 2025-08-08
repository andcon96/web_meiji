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
        Schema::table('shipment_schedule_prefix', function (Blueprint $table) {
            $table->tinyInteger('ship_schedule_month')->after('id')->default(1);
            $table->tinyInteger('ship_schedule_year')->after('id')->default(25);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_schedule_prefix', function (Blueprint $table) {
            $table->dropColumn('ship_schedule_month');
            $table->dropColumn('ship_schedule_year');
        });
    }
};
