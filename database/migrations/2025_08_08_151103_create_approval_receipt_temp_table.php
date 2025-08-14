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
        Schema::create('approval_receipt_temp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('art_receipt_det_id')->index();
            $table->foreign('art_receipt_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->string('art_user_approve');
            $table->string('art_user_approve_alt')->nullable();
            $table->string('art_sequence');
            $table->string('art_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_receipt_temp');
    }
};
