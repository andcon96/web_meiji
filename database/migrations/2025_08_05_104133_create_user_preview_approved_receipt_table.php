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
        Schema::create('receipt_det_user_preview', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rdup_rd_id')->index();
            $table->foreign('rdup_rd_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->unsignedBigInteger('rdup_user')->index();
            $table->foreign('rdup_user')->references('id')->on('users')->onDelete('restrict');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_det_user_preview');
    }
};
