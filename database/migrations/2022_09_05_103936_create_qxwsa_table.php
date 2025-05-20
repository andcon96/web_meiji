<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQxwsaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qxwsa', function (Blueprint $table) {
            $table->increments('id');
            $table->string('wsas_domain')->nullable();
            $table->string('wsas_url');
            $table->string('wsas_path');
            $table->tinyInteger('qx_enable')->nullable()->default('1');
            $table->string('qx_url')->nullable();
            $table->string('qx_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qxwsa');
    }
}
