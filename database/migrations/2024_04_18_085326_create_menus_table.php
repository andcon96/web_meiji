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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_parent_id')->index()->nullable();
            $table->foreign('menu_parent_id')->references('id')->on('menus')->onDelete('restrict');
            $table->unsignedBigInteger('menu_icon_id')->index()->nullable();
            $table->foreign('menu_icon_id')->references('id')->on('icon_master')->onDelete('restrict');
            $table->string('menu_name');
            $table->string('menu_route')->nullable();
            $table->tinyInteger('menu_sequence');
            $table->string('created_by', 100);
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
