<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("other_shipment_preparation_hist", function (Blueprint $table) {
            $table->id();
            $table->string("osph_number");
            $table->string("osph_item");
            $table->string("osph_site");
            $table->string("osph_warehouse");
            $table->string("osph_location");
            $table->string("osph_lotserial");
            $table->string("osph_level");
            $table->string("osph_bin");
            $table->decimal("osph_qty_to_pick", 15, 2);
            $table->decimal("osph_qty_pick", 15, 2);
            $table->string("osph_status_qad");
            $table->string("osph_status");
            $table->string("osph_action");
            $table->unsignedBigInteger("created_by");
            $table->foreign("created_by")->references("id")->on("users")->onDelete("restrict");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("other_shipment_preparation_hist");
    }
};
