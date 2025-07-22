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
        Schema::create('receipt_det_attachment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rda_rd_det_id')->index();
            $table->foreign('rda_rd_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->string('rda_filepath')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det_attachment');
    }
};
