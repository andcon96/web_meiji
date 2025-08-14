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
        Schema::create('transfer_stock', function (Blueprint $table) {
            $table->id();
            $table->string('ts_part');
            $table->string('ts_part_desc')->nullable();
            $table->decimal('ts_qty_oh_sample')->default(0);
            $table->decimal('ts_qty_oh_return')->default(0);
            $table->enum('ts_status', ['Created', 'Returned']);
            $table->string('ts_site_from')->nullable();
            $table->string('ts_site_to')->nullable();
            $table->string('ts_loc_from')->nullable();
            $table->string('ts_loc_to')->nullable();
            $table->string('ts_lot_from')->nullable();
            $table->string('ts_lot_to')->nullable();
            $table->string('ts_wrh_from')->nullable();
            $table->string('ts_wrh_to')->nullable();
            $table->string('ts_level_from')->nullable();
            $table->string('ts_level_to')->nullable();
            $table->string('ts_bin_from')->nullable();
            $table->string('ts_bin_to')->nullable();
            $table->unsignedBigInteger('ts_created_by')->index();
            $table->foreign('ts_created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_stock');
    }
};
