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
            $table->string('prm_shipper_nbr')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_replenishment_mstr', function (Blueprint $table) {
            $table->string('prm_shipper_nbr')->nullable(false)->change();
        });
    }
};
