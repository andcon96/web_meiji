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
        Schema::table('approval_receipt_temp', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE approval_receipt_temp MODIFY art_status ENUM('Waiting', 'Approved','Reject', 'Checked')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_receipt_temp', function (Blueprint $table) {
            DB::statement("ALTER TABLE approval_receipt_temp MODIFY art_status ENUM('Waiting', 'Approved','Reject')");
            //
        });
    }
};
