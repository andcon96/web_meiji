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
        Schema::table('receipt_mstr', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE receipt_mstr MODIFY rm_status ENUM('Draft', 'Waiting For Recheck','Waiting For Approval', 'Approved', 'Rejected')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_mstr', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE receipt_mstr MODIFY rm_status ENUM('Draft', 'Waiting For Approval', 'Approved', 'Rejected')");
        });
    }
};
