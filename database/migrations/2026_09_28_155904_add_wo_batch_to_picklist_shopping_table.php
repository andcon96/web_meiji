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
            $table->String('ps_wo_part')->nullable()->after('ps_bin');
            $table->String('ps_wo_batch')->nullable()->after('ps_wo_part');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('picklist_shopping', function (Blueprint $table) {
            //
            $table->dropColumn('ps_wo_part');
            $table->dropColumn('ps_wo_batch');
        });
    }
};
