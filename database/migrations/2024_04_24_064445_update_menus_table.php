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
        Schema::table('menus', function (Blueprint $table) {
            $table->dropForeign(['menu_parent_id']);
            $table->dropColumn('menu_parent_id');
            $table->dropForeign(['menu_icon_id']);
            $table->dropColumn('menu_icon_id');
            $table->dropColumn('menu_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_parent_id')->index()->nullable();
            $table->foreign('menu_parent_id')->references('id')->on('menus')->onDelete('restrict');
            $table->unsignedBigInteger('menu_icon_id')->index()->nullable();
            $table->foreign('menu_icon_id')->references('id')->on('icon_master')->onDelete('restrict');
            $table->tinyInteger('menu_sequence');
        });
    }
};
