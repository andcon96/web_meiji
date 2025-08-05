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
        Schema::create('shipment_schedule_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ssm_id')->index();
            $table->foreign('ssm_id')->references('id')->on('shipment_schedule_mstr')->onDelete('restrict');
            $table->string('ssd_sod_nbr')->index();
            $table->string('ssd_sod_line')->index();
            $table->string('ssd_sod_part')->index();
            $table->string('ssd_sod_desc')->nullable();
            $table->decimal('ssd_sod_qty_ord', 15, 2)->default(0.00);
            $table->decimal('ssd_sod_qty_pick', 15, 2)->default(0.00);
            $table->string('ssd_status');
            $table->enum('ssd_sent_to_qad', ['Yes', 'No'])->default('No');
            $table->unsignedBigInteger('created_by')->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_schedule_det');
    }
};
