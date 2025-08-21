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
        Schema::create('prefix_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('prefix_wo')->nullable();
            $table->string('prefix_year_wo')->nullable();
            $table->string('prefix_month_wo')->nullable();
            $table->string('prefix_day_wo')->nullable();
            $table->string('running_nbr_wo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prefix_work_orders');
    }
};
