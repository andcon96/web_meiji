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
        Schema::create('receipt_det_kendaraan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rdken_rd_det_id')->index();
            $table->foreign('rdken_rd_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->tinyInteger('rdken_is_bersih')->default(0);
            $table->tinyInteger('rdken_is_tidak_bersih')->default(0);
            $table->tinyInteger('rdken_is_ada_serangga')->default(0);
            $table->string('rdken_keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det_kendaraan');
    }
};
