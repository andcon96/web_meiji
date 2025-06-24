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
        Schema::create('menu_structure', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id')->index();
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('restrict');
            $table->unsignedBigInteger('menu_icon_id')->index()->nullable();
            $table->foreign('menu_icon_id')->references('id')->on('icon_master')->onDelete('restrict');
            $table->unsignedBigInteger('menu_parent_id')->index()->nullable();
            $table->foreign('menu_parent_id')->references('id')->on('menus')->onDelete('restrict');
            $table->tinyInteger('menu_sequence');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_structure');
    }
};
