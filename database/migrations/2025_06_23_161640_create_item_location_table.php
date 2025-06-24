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
        Schema::create('item_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('il_ld_id')->index()->nullable();
            $table->foreign('il_ld_id')->references('id')->on('location_detail')->onDelete('restrict');
            $table->unsignedBigInteger('il_item_id')->index()->nullable();
            $table->foreign('il_item_id')->references('id')->on('item_master')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_location');
    }
};
