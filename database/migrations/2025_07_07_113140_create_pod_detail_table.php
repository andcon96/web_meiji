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
        Schema::create('pod_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pod_po_mstr_id')->index();
            $table->foreign('pod_po_mstr_id')->references('id')->on('po_mstr')->onDelete('restrict');
            $table->integer('pod_line');
            $table->string('pod_part');
            $table->string('pod_part_desc');
            $table->string('pod_qty_ord');
            $table->string('pod_qty_rcpt')->default(0);
            $table->string('pod_qty_ongoing')->default(0);
            $table->string('pod_um');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pod_det');
    }
};
