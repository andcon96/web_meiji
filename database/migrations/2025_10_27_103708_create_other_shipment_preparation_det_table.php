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
        Schema::create("other_shipment_preparation_det", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ospm_id")->index();
            $table->foreign("ospm_id")->references("id")->on("other_shipment_preparation_mstr")->onDelete("restrict");
            $table->unsignedBigInteger("ossl_id")->index();
            $table->foreign("ossl_id")->references("id")->on("other_shipment_schedule_location")->onDelete("restrict");
            $table->enum("ospd_status", ["No", "Yes"])->default("No");
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
        Schema::dropIfExists("other_shipment_preparation_det");
    }
};
