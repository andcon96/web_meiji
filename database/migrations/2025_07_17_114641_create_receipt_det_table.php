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
        Schema::create('receipt_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rd_rm_id')->index();
            $table->foreign('rd_rm_id')->references('id')->on('receipt_mstr')->onDelete('restrict');
            $table->unsignedBigInteger('rd_pod_det_id')->index();
            $table->foreign('rd_pod_det_id')->references('id')->on('pod_det')->onDelete('restrict');

            $table->string('rd_nama_barang')->nullable();
            $table->string('rd_nama_barang_note')->nullable();
            $table->string('rd_batch')->nullable();
            $table->string('rd_batch_note')->nullable();
            $table->date('rd_tgl_expire')->nullable();
            $table->string('rd_tgl_expire_note')->nullable();
            $table->date('rd_tgl_retest')->nullable();
            $table->string('rd_tgl_retest_note')->nullable();
            $table->string('rd_kode_cetak')->nullable();
            $table->string('rd_kode_cetak_note')->nullable();
            $table->decimal('rd_qty_terima', 15, 2)->default(0);
            $table->string('rd_qty_terima_note')->nullable();
            $table->decimal('rd_qty_potensi', 15, 2)->default(0);
            $table->decimal('rd_qty_pallete', 15, 2)->default(0);

            $table->string('rd_site_penyimpanan')->nullable();
            $table->string('rd_location_penyimpanan')->nullable();
            $table->string('rd_level_penyimpanan')->nullable();
            $table->string('rd_bin_penyimpanan')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det');
    }
};
