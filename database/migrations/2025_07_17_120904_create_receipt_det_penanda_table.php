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
        Schema::create('receipt_det_penanda', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rdp_rd_det_id')->index();
            $table->foreign('rdp_rd_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->string('rdp_nama_barang')->nullable();
            $table->string('rdp_nomor_lot')->nullable();
            $table->date('rdp_expire_date')->nullable();
            $table->date('rdp_mfg_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det_penanda');
    }
};
