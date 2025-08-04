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
        Schema::create('shipment_schedule_hist', function (Blueprint $table) {
            $table->id();
            $table->string('ssh_number');
            $table->string('ssh_cust_code');
            $table->string('ssh_cust_desc');
            $table->string('ssh_status_mstr');
            $table->string('ssh_sod_nbr')->index();
            $table->string('ssh_sod_line')->index();
            $table->string('ssh_sod_part')->index();
            $table->string('ssh_sod_desc')->nullable();
            $table->decimal('ssh_sod_qty_ord', 15, 2)->default(0.00);
            $table->decimal('ssh_sod_qty_pick', 15, 2)->default(0.00);
            $table->string('ssh_status_det');
            $table->enum('ssh_sent_to_qad', ['Yes', 'No'])->default('No');
            $table->string('ssh_warehouse');
            $table->string('ssh_location');
            $table->string('ssh_lotserial');
            $table->string('ssh_level');
            $table->string('ssh_bin');
            $table->decimal('ssh_qty_pick', 15, 2)->default(0.00);
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_schedule_hist');
    }
};
