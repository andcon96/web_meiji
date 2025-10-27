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
        Schema::create("other_shipment_preparation_prefix", function (Blueprint $table) {
            $table->id();
            $table->tinyInteger("other_shipment_preparation_year")->default(25);
            $table->tinyInteger("other_shipment_preparation_month")->default(10);
            $table->string("other_shipment_preparation_prefix", 5)->default("OP");
            $table->string("other_shipment_preparation_running_nbr", 18)->default("0");
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
        Schema::dropIfExists("other_shipment_preparation_prefix");
    }
};
