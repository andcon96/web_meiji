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
        Schema::create('approval_setup_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asm_id')->index();
            $table->foreign('asm_id')->references('id')->on('approval_setup_mstr')->onDelete('restrict');
            $table->string('asd_approval_role')->nullable();
            $table->string('asd_approval_user')->nullable();
            $table->string('asd_notify_role')->nullable();
            $table->string('asd_notify_user')->nullable();
            $table->decimal('asd_amount', 14, 2)->default(0.00);
            $table->unsignedBigInteger('created_by')->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_setup_det');
    }
};
