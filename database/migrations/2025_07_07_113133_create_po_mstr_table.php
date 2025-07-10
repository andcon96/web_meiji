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
        Schema::create('po_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('po_nbr', 8);
            $table->string('po_vend', 8);
            $table->string('po_vend_desc', 255);
            $table->date('po_ord_date');
            $table->date('po_due_date');
            $table->string('po_rmks')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_mstr');
    }
};
