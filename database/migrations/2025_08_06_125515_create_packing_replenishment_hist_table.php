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
        Schema::create('packing_replenishment_hist', function (Blueprint $table) {
            $table->id();
            $table->string('prh_shipper_nbr', 20);
            $table->string('prh_so_nbr');
            $table->string('prh_so_line');
            $table->string('prh_site');
            $table->string('prh_warehouse');
            $table->string('prh_location');
            $table->string('prh_lotserial')->nullable();
            $table->string('prh_level');
            $table->string('prh_bin');
            $table->decimal('prh_qty_pick', 15, 2);
            $table->string('prh_status_qad');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_replenishment_hist');
    }
};
