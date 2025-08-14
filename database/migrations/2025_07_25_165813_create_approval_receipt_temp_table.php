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
            $table->unsignedBigInteger('art_user_approve')->index();
            $table->foreign('art_user_approve')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('art_user_approve_alt')->index();
            $table->foreign('art_user_approve_alt')->references('id')->on('users')->onDelete('restrict');
            $table->integer('art_sequence')->nullable();

            $table->string('art_approved_by')->nullable();
            $table->enum('art_status', ['Approved', 'Reject', 'Waiting'])->default('Waiting');
            $table->string('art_reason')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
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
