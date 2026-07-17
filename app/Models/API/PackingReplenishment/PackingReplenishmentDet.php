<?php

namespace App\Models\API\PackingReplenishment;

use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingReplenishmentDet extends Model
{
    use HasFactory;

    protected $table = 'packing_replenishment_det';

    protected $fillable = ['prm_id', 'ssl_id', 'prd_status_qad', 'prd_created_by'];
    public function getShipmentScheduleLocation()
    {
        return $this->belongsTo(ShipmentScheduleLoc::class, 'ssl_id', 'id');
    }

    public function getPackingReplenishmentMaster()
    {
        return $this->belongsTo(PackingReplenishmentMstr::class, 'prm_id', 'id');
    }
}
