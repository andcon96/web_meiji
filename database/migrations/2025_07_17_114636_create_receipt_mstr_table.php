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
        Schema::create('receipt_mstr', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rm_po_mstr_id')->index();
            $table->foreign('rm_po_mstr_id')->references('id')->on('po_mstr')->onDelete('restrict');
            $table->string('rm_rn_number');
            $table->enum('rm_status', ['Draft', 'Waiting For Approval', 'Approved', 'Rejected']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_mstr');
    }
};
