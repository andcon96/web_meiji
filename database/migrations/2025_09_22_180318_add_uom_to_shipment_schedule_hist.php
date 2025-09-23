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
        Schema::table('shipment_schedule_hist', function (Blueprint $table) {
            $table->string('ssh_uom')->nullable()->after('ssh_sod_desc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_schedule_hist', function (Blueprint $table) {
            $table->dropColumn('ssh_uom');
        });
    }
};
