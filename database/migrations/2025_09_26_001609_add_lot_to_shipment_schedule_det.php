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
        Schema::table('shipment_schedule_det', function (Blueprint $table) {
            $table->string('ssd_sod_lot')->nullable()->after('ssd_sod_qty_pick');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_schedule_det', function (Blueprint $table) {
            $table->dropColumn('ssd_sod_lot');
        });
    }
};
