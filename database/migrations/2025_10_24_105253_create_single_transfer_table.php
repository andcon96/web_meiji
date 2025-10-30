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
        Schema::create('single_transfer', function (Blueprint $table) {
            $table->id();
            $table->string('st_trfid')->nullable();
            $table->string('st_item')->nullable();
            $table->string('st_lot')->nullable();
            $table->string('st_site_from')->nullable();
            $table->string('st_site_to')->nullable();
            $table->string('st_loc_from')->nullable();
            $table->string('st_loc_to')->nullable();
            $table->string('st_ref')->nullable();
            $table->integer('st_qty')->nullable();
            $table->string('st_wh')->nullable();
            $table->string('st_level')->nullable();
            $table->string('st_bin')->nullable();
            $table->string('st_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('single_transfer');
    }
};
