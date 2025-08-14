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
        Schema::create('packing_replenishment_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prm_id')->index();
            $table->foreign('prm_id')->references('id')->on('packing_replenishment_mstr')->onDelete('restrict');
            $table->unsignedBigInteger('ssl_id')->index();
            $table->foreign('ssl_id')->references('id')->on('shipment_schedule_location')->onDelete('restrict');
            $table->enum('prd_status_qad', ['No', 'Yes'])->default('No');
            $table->unsignedBigInteger('prd_created_by')->index();
            $table->foreign('prd_created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_replenishment_det');
    }
};
