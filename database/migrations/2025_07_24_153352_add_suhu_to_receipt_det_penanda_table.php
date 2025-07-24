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
        Schema::table('receipt_det_penanda', function (Blueprint $table) {
            $table->string('rdp_suhu')->after('rdp_mfg_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_det_penanda', function (Blueprint $table) {
            $table->dropColumn('rdp_suhu');
        });
    }
};
