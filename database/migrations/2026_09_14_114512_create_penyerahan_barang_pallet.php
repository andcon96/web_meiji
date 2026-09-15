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
        Schema::create('penyerahan_barang_pallet', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('pbp_pb_id')->index();
            $table->foreign('pbp_pb_id')->references('id')->on('penyerahan_barang')->onDelete('restrict');
            $table->string('pbp_level_penyimpanan')->nullable();
            $table->string('pbp_bin_penyimpanan')->nullable();
            $table->string('pbp_qty_penyimpanan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyerahan_barang_pallet');
    }
};
