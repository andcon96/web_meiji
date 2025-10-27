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
        Schema::create("other_shipment_schedule_hist", function (Blueprint $table) {
            $table->id();
            $table->string("ossh_number");
            $table->string("ossh_cust_code");
            $table->string("ossh_cust_desc");
            $table->string("ossh_status_mstr");
            $table->string("ossd_part");
            $table->string("ossd_desc")->nullable();
            $table->string("ossd_uom");
            $table->decimal("ossd_qty_ord", 15, 2);
            $table->decimal("ossd_qty_pick", 15, 2);
            $table->string("ossd_status_det");
            $table->enum("ossd_sent_to_qad", ["Yes", "No"]);
            $table->string("ossl_site");
            $table->string("ossl_warehouse");
            $table->string("ossl_location");
            $table->string("ossl_lotserial")->nullable();
            $table->string("ossl_level");
            $table->string("ossl_bin");
            $table->decimal("ossl_qty_to_pick", 15, 2);
            $table->decimal("ossl_qty_pick", 15, 2);
            $table->string("ossl_action");
            $table->unsignedBigInteger("created_by")->index();
            $table->foreign("created_by")->references("id")->on("users")->onDelete("restrict");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("other_shipment_schedule_hist");
    }
};
