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
        Schema::table('po_mstr', function (Blueprint $table) {
            $table->string('po_site')->nullable()->after('po_stat');
            $table->string('po_loc_def')->nullable()->after('po_site');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_mstr', function (Blueprint $table) {
            $table->dropColumn('po_site');
            $table->dropColumn('po_loc_def');
        });
    }
};
