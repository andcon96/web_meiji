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
            $table->string('ssh_sod_site')->default('2100')->after('ssh_sod_nbr');
            $table->string('ssh_sod_shipto')->nullable()->after('ssh_sod_site');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_schedule_hist', function (Blueprint $table) {
            $table->dropColumn('ssh_sod_site');
            $table->dropColumn('ssh_sod_shipto');
        });
    }
};
