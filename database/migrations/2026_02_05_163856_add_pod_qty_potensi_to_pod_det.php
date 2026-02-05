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
        Schema::table('pod_det', function (Blueprint $table) {
            //
            $table->String('pod_qty_potensi')->default(1)->after('pod_qty_ongoing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pod_det', function (Blueprint $table) {
            //
            $table->dropColumn('pod_qty_potensi');
        });
    }
};
