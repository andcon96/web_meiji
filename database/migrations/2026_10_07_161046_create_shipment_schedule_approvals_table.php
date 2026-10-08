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
        Schema::create('shipment_schedule_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ssm_id');
            $table->tinyInteger('ssa_sequence');
            $table->string('ssa_user_approver')->nullable();
            $table->string('ssa_alt_user_approver')->nullable();
            $table->string('ssa_status');
            $table->string('ssa_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            // Index
            $table->index('ssm_id');
            $table->index('created_by');
            $table->index('updated_by');

            // Foreign Key Constraints
            $table->foreign('ssm_id')
                ->references('id')
                ->on('shipment_schedule_mstr')
                ->onDelete('restrict');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_schedule_approvals');
    }
};
