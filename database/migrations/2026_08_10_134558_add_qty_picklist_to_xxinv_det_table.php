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
        Schema::table('xxinv_det', function (Blueprint $table) {
            //
            $table->decimal('xxinv_qty_picklist', 20, 5)->nullable()->after('xxinv_qty_wip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xxinv_det', function (Blueprint $table) {
            //
            $table->dropColumn('xxinv_qty_picklist');
        });
    }
};
