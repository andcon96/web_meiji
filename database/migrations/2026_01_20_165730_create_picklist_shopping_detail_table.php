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
        Schema::create('picklist_shopping_detail', function (Blueprint $table) {
            $table->id();
            $table->string('psd_nbr');
            $table->string('psd_part');
            $table->string('psd_lot');
            $table->string('psd_site');
            $table->string('psd_loc');
            $table->string('psd_wh');
            $table->string('psd_level');
            $table->string('psd_bin');
            $table->decimal('psd_qty_req', 10, 2);
            $table->decimal('psd_qty_picked', 10, 2);
            $table->decimal('psd_qty_topick', 10, 2);
            $table->decimal('psd_qty_kemasan', 10, 2);
            $table->string('psd_status');
            $table->string('psd_approver');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picklist_shopping_detail');
    }
};
