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
        Schema::create('prefix_number', function (Blueprint $table) {
            $table->id();
            $table->string('prefix_receipt', 5);
            $table->string('running_nbr_receipt', 18);
            $table->string('prefix_buku_penerimaan', 5);
            $table->string('running_nbr_buku_penerimaan', 18);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prefix_number');
    }
};
