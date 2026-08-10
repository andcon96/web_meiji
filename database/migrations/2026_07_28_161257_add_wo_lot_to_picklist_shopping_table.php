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
            $table->string('ps_wo_lot')->nullable()->after('ps_lot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('picklist_shopping', function (Blueprint $table) {
            //
            $table->dropColumn('ps_wo_lot');
        });
    }
};
