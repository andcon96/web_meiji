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
            $table->string('prh_action')->nullable()->after('prh_status');
            $table->string('created_by')->after('prh_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_replenishment_hist', function (Blueprint $table) {
            $table->dropColumn('prh_action');
            $table->dropColumn('created_by');
        });
    }
};
