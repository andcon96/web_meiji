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
        Schema::table('picklist_shopping', function (Blueprint $table) {
            //
            $table->string('ps_warehouse')->nullable()->after('ps_wo_lot');
            $table->string('ps_level')->nullable()->after('ps_warehouse');
            $table->string('ps_bin')->nullable()->after('ps_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('picklist_shopping', function (Blueprint $table) {
            //
            $table->dropColumn('ps_warehouse');
            $table->dropColumn('ps_level');
            $table->dropColumn('ps_bin');
        });
    }
};
