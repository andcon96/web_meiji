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
        Schema::create('xxinv_det_approval', function (Blueprint $table) {
            $table->id();
            $table->String('xxinv_domain')->nullable();
            $table->String('xxinv_part')->nullable();
            $table->String('xxinv_lot')->nullable();
            $table->String('xxinv_locfrom')->nullable();
            $table->String('xxinv_locto')->nullable();
            $table->String('xxinv_binto')->nullable();
            $table->String('xxinv_levelto')->nullable();
            $table->String('xxinv_siteto')->nullable();
            $table->String('xxinv_wrhto')->nullable();
            $table->String('xxinv_binfrom')->nullable();
            $table->String('xxinv_levelfrom')->nullable();
            $table->String('xxinv_sitefrom')->nullable();
            $table->String('xxinv_wrhfrom')->nullable();
            $table->decimal('xxinv_qtyoh', 17, 5)->nullable();
            $table->decimal('xxinv_qty_pick', 17, 2)->nullable();
            $table->String('xxinv__chr01')->nullable();
            $table->String('xxinv__chr02')->nullable();
            $table->String('xxinv__long1', 100)->nullable();
            $table->String('xxinv_status')->nullable();
            $table->String('xxinv_approver')->nullable();
            $table->decimal('xxinv__dec01', 17, 2)->nullable();
            $table->decimal('xxinv__dec02', 17, 2)->nullable();
            $table->date('xxinv__dte01')->nullable();
            $table->String('xxinv_ref', 18)->nullable();
            $table->date('xxinv_entry_date')->nullable();
            $table->date('xxinv_exp_date')->nullable();
            $table->date('xxinv_due_date')->nullable();
            $table->date('xxinv_rel_date')->nullable();
            $table->date('xxinv_ord_date')->nullable();

            $table->decimal('xxinv_qty_wrh', 20, 5)->nullable();
            $table->decimal('xxinv_qty_smp', 20, 5)->nullable();
            $table->decimal('xxinv_qty_shp', 20, 5)->nullable();
            $table->decimal('xxinv_qty_wip', 20, 5)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xxinv_det_approval');
    }
};
