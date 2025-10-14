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
        Schema::create("shipper_confirm", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("prm_id")->index();
            $table->foreign("prm_id")->references("id")->on("packing_replenishment_mstr");
            $table->tinyInteger("sc_sequence")->default(1);
            $table->string("sc_user_approver")->nullable();
            $table->string("sc_alt_user_approver")->nullable();
            $table->string("sc_status");
            $table->string("sc_reason")->nullable();
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
        Schema::dropIfExists("shipper_confirm");
    }
};
