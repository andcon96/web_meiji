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
        Schema::table('picklist_wo_det', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('wod_pl_id')->index()->nullable();
            $table->foreign('wod_pl_id')->references('id')->on('picklist_mstr')->onDelete('restrict');
            $table->dropForeign('picklist_wo_det_pl_wod_wo_id_foreign');
            $table->unsignedBigInteger('pl_wod_wo_id')->nullable()->change();
            $table->foreign('pl_wod_wo_id')->references('id')->on('picklist_wo')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('picklist_wo_det', function (Blueprint $table) {
            //
            $table->dropForeign('picklist_wo_det_wod_pl_id_foreign');
            $table->dropColumn('wod_pl_id');
        });
    }
};
