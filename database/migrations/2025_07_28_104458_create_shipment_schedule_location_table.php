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
        Schema::create('shipment_schedule_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ssd_id')->index();
            $table->foreign('ssd_id')->references('id')->on('shipment_schedule_det')->onDelete('restrict');
            $table->string('ssl_warehouse');
            $table->string('ssl_location');
            $table->string('ssl_lotserial');
            $table->string('ssl_level');
            $table->string('ssl_bin');
            $table->decimal('ssl_qty_pick', 15, 2)->default(0.00);
            $table->unsignedBigInteger('created_by')->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('updated_by')->index();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_schedule_location');
    }
};
