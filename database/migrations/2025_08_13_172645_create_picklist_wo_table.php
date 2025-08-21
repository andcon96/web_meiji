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
        Schema::create('picklist_wo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pl_id')->index();
            $table->foreign('pl_id')->references('id')->on('picklist_mstr')->onDelete('restrict');
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
            $table->string('created_by', 50); 
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picklist_wo');
    }
};
