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
        Schema::table('approval_setup_det', function (Blueprint $table) {
            $table->tinyInteger('asd_approval_sequence')->after('asd_approval_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_setup_det', function (Blueprint $table) {
            $table->dropColumn('asd_approval_sequence');
        });
    }
};
