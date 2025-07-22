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
        Schema::create('receipt_det_dokumen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rdd_rd_det_id')->index();
            $table->foreign('rdd_rd_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->tinyInteger('rdd_is_purchase_order')->default(0);
            $table->tinyInteger('rdd_is_msds')->default(0);
            $table->tinyInteger('rdd_is_packing_list')->default(0);
            $table->tinyInteger('rdd_is_coa')->default(0);
            $table->tinyInteger('rdd_is_surat_jalan')->default(0);
            $table->string('rdd_surat_jalan')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det_dokumen');
    }
};
