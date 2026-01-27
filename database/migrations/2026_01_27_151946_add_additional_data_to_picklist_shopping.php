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
            $table->String('ps_part')->after('ps_number')->nullable();
            $table->String('ps_lot')->after('ps_part')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('picklist_shopping', function (Blueprint $table) {
            //
            $table->dropColumn('ps_part');
            $table->dropColumn('ps_lot');
        });
    }
};
