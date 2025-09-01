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
        Schema::table('pod_det', function (Blueprint $table) {
            $table->string('pod_part_desc1')->nullable()->after('pod_part_desc');
            $table->string('pod_part_desc2')->nullable()->after('pod_part_desc1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pod_det', function (Blueprint $table) {
            $table->dropColumn('pod_part_desc1');
            $table->dropColumn('pod_part_desc2');
        });
    }
};
