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
        Schema::create('picklist_shopping', function (Blueprint $table) {
            $table->id();
            $table->string('ps_number')->nullable();
            $table->string('ps_status')->nullable();
            $table->string('ps_approver')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picklist_shopping');
    }
};
