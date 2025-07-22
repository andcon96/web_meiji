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
        Schema::create('receipt_det_kemasan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rdk_rd_det_id')->index();
            $table->foreign('rdk_rd_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->tinyInteger('rdk_is_pabrik_pembuat')->default(0);
            $table->tinyInteger('rdk_is_alamat_pembuat')->default(0);
            $table->tinyInteger('rdk_is_agen_pemasuk')->default(0);
            $table->string('rdk_jenis_kemasan_luar')->nullable();
            $table->string('rdk_jenis_kemasan_dalam')->nullable();
            $table->string('rdk_isi_per_kemasan')->nullable();
            $table->string('rdk_isi_total_kemasan')->nullable();
            $table->string('rdk_jumlah_kemasan_luar')->nullable();
            $table->string('rdk_jumlah_kemasan_luar_baik')->nullable();
            $table->string('rdk_jumlah_kemasan_luar_tidak_baik')->nullable();
            $table->string('rdk_jumlah_kemasan_dalam')->nullable();
            $table->string('rdk_jumlah_kemasan_dalam_baik')->nullable();
            $table->string('rdk_jumlah_kemasan_dalam_tidak_baik')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det_kemasan');
    }
};
