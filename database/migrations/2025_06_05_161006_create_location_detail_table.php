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
        Schema::create('location_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ld_location_id')->index()->nullable();
            $table->foreign('ld_location_id')->references('id')->on('location')->onDelete('restrict');
            $table->string('ld_lot_serial')->nullable();
            $table->string('ld_building')->nullable();
            $table->string('ld_rak')->nullable();
            $table->string('ld_bin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_detail');
    }
};
