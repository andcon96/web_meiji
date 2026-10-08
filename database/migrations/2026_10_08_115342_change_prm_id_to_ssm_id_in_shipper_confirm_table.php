<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah relasi shipper_confirm dari packing_replenishment_mstr (prm_id)
     * ke shipment_schedule_mstr (ssm_id).
     *
     * NOTE: Sesuaikan nama tabel master di bawah jika berbeda.
     */
    private string $masterTable = 'shipment_schedule_mstr';

    public function up(): void
    {
        // Data lama mengacu ke packing_replenishment_mstr, tidak valid untuk ssm_id.
        // Backup dulu jika data ini penting.
        DB::table('shipper_confirm')->delete();

        Schema::table('shipper_confirm', function (Blueprint $table) {
            $table->dropForeign('shipper_confirm_prm_id_foreign');
            $table->dropIndex('shipper_confirm_prm_id_index');
        });

        // Raw statement supaya tidak butuh doctrine/dbal (MySQL 8+)
        DB::statement('ALTER TABLE `shipper_confirm` RENAME COLUMN `prm_id` TO `ssm_id`');

        Schema::table('shipper_confirm', function (Blueprint $table) {
            $table->index('ssm_id', 'shipper_confirm_ssm_id_index');
            $table->foreign('ssm_id', 'shipper_confirm_ssm_id_foreign')
                ->references('id')
                ->on($this->masterTable);
        });
    }

    public function down(): void
    {
        DB::table('shipper_confirm')->delete();

        Schema::table('shipper_confirm', function (Blueprint $table) {
            $table->dropForeign('shipper_confirm_ssm_id_foreign');
            $table->dropIndex('shipper_confirm_ssm_id_index');
        });

        DB::statement('ALTER TABLE `shipper_confirm` RENAME COLUMN `ssm_id` TO `prm_id`');

        Schema::table('shipper_confirm', function (Blueprint $table) {
            $table->index('prm_id', 'shipper_confirm_prm_id_index');
            $table->foreign('prm_id', 'shipper_confirm_prm_id_foreign')
                ->references('id')
                ->on('packing_replenishment_mstr');
        });
    }
};
