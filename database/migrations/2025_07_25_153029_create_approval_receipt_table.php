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
        Schema::create('approval_receipt', function (Blueprint $table) {
            $table->id();
            $table->integer('ar_sequence');
            $table->unsignedBigInteger('ar_user_approve')->index();
            $table->foreign('ar_user_approve')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('ar_user_approve_alt')->index();
            $table->foreign('ar_user_approve_alt')->references('id')->on('users')->onDelete('restrict');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_receipt');
    }
};
