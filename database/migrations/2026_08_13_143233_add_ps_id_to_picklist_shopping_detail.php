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
    Schema::table('picklist_shopping_detail', function (Blueprint $table) {
        $table->unsignedBigInteger('ps_id')->after('id')->index(); // after() goes here
        $table->foreign('ps_id')->references('id')->on('picklist_shopping');
    });
}

public function down(): void
{
        Schema::table('picklist_shopping_detail', function (Blueprint $table) {
            $table->dropForeign(['ps_id']);
            $table->dropColumn('ps_id'); 
        });
    }
};
