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
        Schema::create('mobile_apk_hist', function (Blueprint $table) {
            $table->id();
            $table->string('apk_updated_number');
            $table->text('apk_url')->nullable();
            $table->string('apk_version');
            $table->text('apk_release_notes');
            $table->string('apk_is_active');
            $table->string('apk_updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_apk_hist');
    }
};
