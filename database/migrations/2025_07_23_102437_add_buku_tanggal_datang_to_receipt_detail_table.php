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
        Schema::table('receipt_det', function (Blueprint $table) {
            $table->string('rd_nomor_buku')->after('rd_pod_det_id');
            $table->date('rd_tanggal_datang')->after('rd_nomor_buku')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_det', function (Blueprint $table) {
            $table->dropColumn('rd_nomor_buku');
            $table->dropColumn('rd_tanggal_datang');
        });
    }
};
