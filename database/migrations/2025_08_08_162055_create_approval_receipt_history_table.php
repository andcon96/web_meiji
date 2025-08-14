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
        Schema::create('approval_receipt_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arh_receipt_det_id')->index();
            $table->foreign('arh_receipt_det_id')->references('id')->on('receipt_det')->onDelete('restrict');
            $table->string('arh_user_approve');
            $table->string('arh_user_approve_alt')->nullable();
            $table->string('arh_sequence');
            $table->string('arh_approved_by')->nullalbe();
            $table->string('arh_status');
            $table->string('arh_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_receipt_history');
    }
};
