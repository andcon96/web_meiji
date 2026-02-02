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
            //
            $table->string('rd_pt_um')->nullable()->after('rd_nama_barang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_det', function (Blueprint $table) {
            //
            $table->dropColumn('rd_pt_um');
        });
    }
};
