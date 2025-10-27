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
        Schema::create("other_shipment_schedule_det", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ossm_id")->index();
            $table->foreign("ossm_id")->references("id")->on("other_shipment_schedule_mstr")->onDelete("restrict");
            $table->string("ossd_part");
            $table->string("ossd_desc")->nullable();
            $table->string("ossd_uom");
            $table->decimal("ossd_qty_ord", 15, 2);
            $table->decimal("ossd_qty_pick", 15, 2);
            $table->string("ossd_status");
            $table->enum("ossd_sent_to_qad", ["Yes", "No"]);
            $table->unsignedBigInteger("created_by")->index();
            $table->foreign("created_by")->references("id")->on("users")->onDelete("restrict");
            $table->unsignedBigInteger("updated_by")->index()->nullable();
            $table->foreign("updated_by")->references("id")->on("users")->onDelete("restrict");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("other_shipment_schedule_det");
    }
};
