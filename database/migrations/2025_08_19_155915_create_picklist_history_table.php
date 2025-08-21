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
        Schema::create('picklist_history', function (Blueprint $table) {
            $table->id();
            $table->string('pl_nbr')->nullable();
            $table->string('pl_status', 50)->nullable();
            $table->string('created_by', 50)->nullable();
           
            $table->string('pl_wo_nbr', 8);
            $table->string('pl_wo_id', 10);
            $table->string('pl_wo_site',10);
            $table->string('pl_wo_part',50);
            $table->string('pl_wo_part_desc',50);
            $table->string('pl_wo_status',50);   
            $table->decimal('pl_wo_qty_ord',10);
            $table->decimal('pl_wo_qty_comp',50);
            $table->decimal('pl_wo_qty_rjct',10);
            $table->date('pl_wo_order_date');
            $table->date('pl_wo_release_date')->nullable();
            $table->date('pl_wo_due_date');

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
            $table->string('status')->nullable();  
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picklist_history');
    }
};
