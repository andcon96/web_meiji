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
        Schema::create('wo_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('wo_nbr', 8);
            $table->string('wo_id', 10);
            $table->string('wo_site',10);
            $table->string('wo_part',50);
            $table->string('wo_line',50);
            $table->string('wo_status',50);   
            $table->decimal('wo_qty_ord',10);
            $table->decimal('wo_qty_comp',50);
            $table->decimal('wo_qty_rjct',10);
            $table->date('wo_order_date');
            $table->date('wo_release_date')->nullable();
            $table->date('wo_due_date');
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
        Schema::dropIfExists('wo_mstr');
        //
    }
};
