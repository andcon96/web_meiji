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
        Schema::create('penyerahan_barang', function (Blueprint $table) {
            $table->id();
            $table->string('pb_trfid')->nullable();
            $table->string('pb_item')->nullable();
            $table->string('pb_lot')->nullable();
            $table->string('pb_site_from')->nullable();
            $table->string('pb_site_to')->nullable();
            $table->string('pb_loc_from')->nullable();
            $table->string('pb_loc_to')->nullable();
            $table->string('pb_ref')->nullable();
            $table->integer('pb_qty')->nullable();
            $table->string('pb_wh')->nullable();
            $table->string('pb_level')->nullable();
            $table->string('pb_bin')->nullable();
            $table->string('pb_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyerahan_barang');
    }
};
