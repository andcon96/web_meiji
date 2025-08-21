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
        Schema::table('packing_replenishment_mstr', function (Blueprint $table) {
            $table->string('prm_status')->default('Draft')->after('prm_shipper_nbr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_replenishment_mstr', function (Blueprint $table) {
            $table->dropColumn('prm_status');
        });
    }
};
