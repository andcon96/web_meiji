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
            $table->string('rdp_expire_date',64)->change();
            $table->string('rdp_mfg_date',64)->change();
            $table->string('rdp_suhu',64)->change();
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_history', function (Blueprint $table) {
        $table->date('rdp_expire_date')->change();
        $table->date('rdp_mfg_date')->change();
        $table->string('rdp_suhu')->change();
        });
        //
    }
};
