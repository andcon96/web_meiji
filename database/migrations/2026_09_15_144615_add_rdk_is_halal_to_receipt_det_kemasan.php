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
        Schema::table('receipt_det_kemasan', function (Blueprint $table) {
            $table->tinyInteger('rdk_is_halal')->after('rdk_is_alamat_pembuat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_det_kemasan', function (Blueprint $table) {
            $table->dropColumn('rdk_is_halal');
        });
    }
};
