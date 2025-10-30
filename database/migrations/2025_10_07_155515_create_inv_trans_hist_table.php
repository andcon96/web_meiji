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
        Schema::create('inv_trans_hist', function (Blueprint $table) {
            $table->id();
            $table->enum('trans_type', ['IN', 'OUT'])->comment('IN = rct-unp, OUT = iss-unp');
            $table->string('product_code', 255);
            $table->string('product_name', 255)->nullable();
            $table->string('supplier', 255)->nullable();
            $table->string('site', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('pallet_no', 255)->nullable()->comment('lotserial di qad');
            $table->string('batch_no', 255)->nullable()->comment('reference di qad');
            $table->bigInteger('quantity');
            $table->string('created_by', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_trans_hist');
    }
};
