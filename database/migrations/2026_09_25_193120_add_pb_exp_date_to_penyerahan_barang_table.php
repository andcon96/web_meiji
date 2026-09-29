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
        Schema::table('penyerahan_barang', function (Blueprint $table) {
            //
            $table->date('pb_exp_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penyerahan_barang', function (Blueprint $table) {
            //
            $table->dropColumn('pb_exp_date');
        });
    }
};
