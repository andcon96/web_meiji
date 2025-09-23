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
        Schema::create('receipt_det_pallet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rdp_rd_det_id')->index();
            $table->foreign('rdp_rd_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->string('rdp_level_penyimpanan')->nullable();
            $table->string('rdp_bin_penyimpanan')->nullable();
            $table->string('rdp_qty_penyimpanan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det_pallet');
    }
};
