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
        Schema::create('item_master', function (Blueprint $table) {
            $table->id();
            $table->string('im_item_part', 20);
            $table->string('im_item_desc', 50);
            $table->string('im_item_um', 3);
            $table->string('im_item_prod_line', 8);
            $table->string('im_item_group', 8);
            $table->string('im_item_type', 8);
            $table->tinyInteger('im_item_isRfq');
            $table->string('im_item_pm', 2)->nullable();
            $table->integer('im_item_safety_stk')->default(0);
            $table->decimal('im_item_price', 16, 2)->default(0);
            $table->string('im_item_promo')->nullable();
            $table->string('im_item_design')->nullable();
            $table->string('im_item_safety_email')->nullable();
            $table->integer('im_item_day1')->nullable();
            $table->integer('im_item_day2')->nullable();
            $table->integer('im_item_day3')->nullable();
            $table->string('im_item_day_email1')->nullable();
            $table->string('im_item_day_email2')->nullable();
            $table->string('im_item_day_email3')->nullable();
            $table->string('im_item_acc')->nullable();
            $table->string('im_item_subacc')->nullable();
            $table->string('im_item_costcenter')->nullable();
            $table->enum('im_item_from', ['QAD', 'WEB'])->default('QAD');
            $table->longText('im_item_hyperlink')->nullable();
            $table->unsignedBigInteger('load_by_id')->index();
            $table->foreign('load_by_id')->references('id')->on('users')->onDelete('restrict');
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_master');
    }
};
