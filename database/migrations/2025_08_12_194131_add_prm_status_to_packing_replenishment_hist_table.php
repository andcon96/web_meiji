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
        Schema::table('packing_replenishment_hist', function (Blueprint $table) {
            $table->string('prh_status')->default('Draft')->after('prh_status_qad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_replenishment_hist', function (Blueprint $table) {
            $table->dropColumn('prh_status');
        });
    }
};
