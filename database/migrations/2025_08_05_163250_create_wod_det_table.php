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
        Schema::create('wod_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wod_wo_id')->index();
            $table->foreign('wod_wo_id')->references('id')->on('wo_mstr')->onDelete('restrict');
            $table->string('wod_nbr', 20);
            $table->string('wod_op', 20)->nullable();
            $table->string('wod_part', 8)->nullable();
            $table->string('wod_part_desc', 255)->nullable();
            $table->string('wod_um')->nullable();
            $table->string('wod_site')->nullable();
            $table->string('wod_loc')->nullable();
            $table->string('wod_lot')->nullable();
            $table->string('wod_ref')->nullable();
            $table->string('wod_warehouse')->nullable();
            $table->string('wod_bin')->nullable();
            $table->string('wod_level')->nullable();
            $table->decimal('wod_qty_req')->nullable();
            $table->decimal('wod_qty_pick')->nullable();
            $table->decimal('wod_qty_oh')->nullable();
            $table->decimal('wod_qty_pick_inv')->nullable();
            $table->date('wod_entry_date')->nullable();
            $table->date('wod_exp_date')->nullable();
            $table->string('wod_picklist_type')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('wod_det');
    }
};
