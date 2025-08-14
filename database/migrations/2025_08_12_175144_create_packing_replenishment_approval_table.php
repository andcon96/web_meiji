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
        Schema::create('packing_replenishment_approval', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prm_id')->index();
            $table->foreign('prm_id')->references('id')->on('packing_replenishment_mstr')->onDelete('restrict');
            $table->tinyInteger('pra_sequence');
            $table->string('pra_user_approver')->nullable();
            $table->string('pra_alt_user_approver')->nullable();
            $table->string('pra_status');
            $table->unsignedBigInteger('created_by')->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('updated_by')->index();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_replenishment_approval');
    }
};
