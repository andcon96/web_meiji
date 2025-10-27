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
        Schema::create("other_shipment_schedule_location", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ossd_id")->index();
            $table->foreign("ossd_id")->references("id")->on("other_shipment_schedule_det")->onDelete("restrict");
            $table->string("ossl_site");
            $table->string("ossl_warehouse");
            $table->string("ossl_location");
            $table->string("ossl_lotserial")->nullable();
            $table->string("ossl_level");
            $table->string("ossl_bin");
            $table->decimal("ossl_qty_to_pick", 15, 2);
            $table->decimal("ossl_qty_pick", 15, 2);
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
        Schema::dropIfExists("other_shipment_schedule_location");
    }
};
