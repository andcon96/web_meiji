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
        Schema::create('packing_replenishment_approval_hist', function (Blueprint $table) {
            $table->id();
            $table->string('prah_shipper_number');
            $table->tinyInteger('prah_sequence');
            $table->string('prah_user_approver')->nullable();
            $table->string('prah_alt_user_approver')->nullable();
            $table->string('prah_status');
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_replenishment_approval_hist');
    }
};
