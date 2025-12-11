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
        Schema::create('transaction_history', function (Blueprint $table) {
            $table->id();
            $table->string('tr_nbr')->nullable();
            $table->string('tr_program')->nullable();
            $table->string('tr_activity')->nullable();
            $table->string('tr_user')->nullable();
            $table->string('tr_part')->nullable();
            $table->string('tr_lot')->nullable();
            $table->string('tr_location')->nullable();
            $table->string('tr_site')->nullable();
            $table->string('tr_uom')->nullable();
            $table->string('tr_reference')->nullable();
            $table->string('tr_warehouse')->nullable();
            $table->string('tr_level')->nullable();
            $table->string('tr_bin')->nullable();
            $table->string('tr_remark')->nullable();
            $table->string('tr_date')->nullable();
            $table->text('tr_qty')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_history');
    }
};
