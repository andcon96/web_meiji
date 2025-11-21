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
        Schema::table('single_transfer', function (Blueprint $table) {
            //
            $table->String('st_wh_from')->nullable();
            $table->String('st_level_from')->nullable();
            $table->String('st_bin_from')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('single_transfer', function (Blueprint $table) {
            //
            $table->DropColumn('st_wh_from');
            $table->DropColumn('st_level_from');
            $table->DropColumn('st_bin_from');
        });
    }
};
