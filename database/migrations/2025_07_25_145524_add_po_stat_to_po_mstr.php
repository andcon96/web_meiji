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
            $table->string('po_stat', 8)->nullable()->after('po_rmks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_mstr', function (Blueprint $table) {
            $table->dropColumn('po_stat');
        });
    }
};
