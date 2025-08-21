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
            $table->unsignedBigInteger('arh_user_approve')->index();
            $table->foreign('arh_user_approve')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('arh_user_approve_alt')->index();
            $table->foreign('arh_user_approve_alt')->references('id')->on('users')->onDelete('restrict');
            $table->integer('arh_sequence')->nullable();

            $table->string('arh_approved_by')->nullable();
            $table->enum('arh_status', ['Approved', 'Reject', 'Waiting'])->nullable();
            $table->string('arh_reason')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
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
