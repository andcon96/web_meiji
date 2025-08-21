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
        Schema::create('picklist_wo_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pl_wod_wo_id')->index();
            $table->foreign('pl_wod_wo_id')->references('id')->on('picklist_wo')->onDelete('restrict');
            $table->string('pl_wod_nbr', 20);
            $table->string('pl_wod_op', 20)->nullable();
            $table->string('pl_wod_part', 8)->nullable();
            $table->string('pl_wod_part_desc', 255)->nullable();
            $table->string('pl_wod_um')->nullable();
            $table->string('pl_wod_site')->nullable();
            $table->string('pl_wod_loc')->nullable();
            $table->string('pl_wod_lot')->nullable();
            $table->string('pl_wod_ref')->nullable();
            $table->string('pl_wod_warehouse')->nullable();
            $table->string('pl_wod_bin')->nullable();
            $table->string('pl_wod_level')->nullable();
            $table->decimal('pl_wod_qty_req')->nullable();
            $table->decimal('pl_wod_qty_pick')->nullable();  
            $table->decimal('pl_wod_qty_oh')->nullable();
            $table->decimal('pl_wod_qty_pick_inv')->nullable();
            $table->date('pl_wod_entry_date')->nullable();
            $table->date('pl_wod_exp_date')->nullable();
            $table->string('pl_wod_picklist_type')->nullable();  
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picklist_wo_det');
    }
};
