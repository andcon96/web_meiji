<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Sesuaikan nama tabel jika berbeda dengan $table di model ShipmentScheduleHist.
     */
    private string $tableName = 'shipment_schedule_hist';

    public function up(): void
    {
        if (! Schema::hasColumn($this->tableName, 'ssh_reason')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('ssh_reason', 255)->nullable()->after('ssh_action');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn($this->tableName, 'ssh_reason')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('ssh_reason');
            });
        }
    }
};
