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
        Schema::create('other_transaction_confirm', function (Blueprint $table) {
            $table->id();
    $table->unsignedBigInteger("otpm_id")->index();
            $table->foreign("otpm_id")->references("id")->on("other_shipment_preparation_mstr");
            $table->tinyInteger("otc_sequence")->default(1);
            $table->string("otc_user_approver")->nullable();
            $table->string("otc_alt_user_approver")->nullable();
            $table->string("otc_status");
            $table->string("otc_reason")->nullable();
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
        Schema::dropIfExists('other_transaction_confirm');
    }
};
