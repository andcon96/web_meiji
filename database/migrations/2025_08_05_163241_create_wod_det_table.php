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
        Schema::create('wod_det', function (Blueprint $table) {
            $table->id();
            $table->string('wod_nbr', 20);
            $table->string('picklist_wo_nbr', 8);
            $table->string('picklist_wo_id', 255);
            $table->date('picklist_site');
            $table->date('picklist_status');    
            $table->string('Release_Date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
