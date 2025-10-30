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
        Schema::create("other_shipment_preparation_mstr", function (Blueprint $table) {
            $table->id();
            $table->string("ospm_number");
            $table->string("ospm_status");
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
        Schema::dropIfExists("other_shipment_preparation_mstr");
    }
};
