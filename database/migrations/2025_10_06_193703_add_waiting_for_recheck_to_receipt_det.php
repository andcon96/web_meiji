<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receipt_det', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE receipt_det MODIFY rd_status ENUM('Draft', 'Checked','Waiting', 'Approved', 'Reject')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_det', function (Blueprint $table) {
            DB::statement("ALTER TABLE receipt_det MODIFY rd_status ENUM('Draft', 'Waiting', 'Approved', 'Reject')");
            //
        });
    }
};
