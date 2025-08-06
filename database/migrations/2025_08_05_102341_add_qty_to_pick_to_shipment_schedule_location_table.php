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
        Schema::table('shipment_schedule_location', function (Blueprint $table) {
            $table->decimal('ssl_qty_to_pick', 15, 2)->default(0.00)->after('ssl_bin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_schedule_location', function (Blueprint $table) {
            $table->dropColumn('ssl_qty_to_pick');
        });
    }
};
