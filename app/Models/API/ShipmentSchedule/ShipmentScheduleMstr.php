<?php

namespace App\Models\API\ShipmentSchedule;

use App\Models\API\PackingReplenishment\PackingReplenishmentDet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentScheduleMstr extends Model
{
    use HasFactory;

    protected $table = 'shipment_schedule_mstr';

    public function getShipmentScheduleDetail()
    {
        return $this->hasMany(ShipmentScheduleDet::class, 'ssm_id', 'id');
    }

    public function shipmentScheduleLoc()
    {
        return $this->hasManyThrough(
            ShipmentScheduleLoc::class,
            ShipmentScheduleDet::class,
            'ssm_id', // FK on shipment_schedule_det
            'ssd_id', // FK on shipment_schedule_loc
            'id',     // PK on shipment_schedule_mstr
            'id'      // PK on shipment_schedule_det
        );
    }

    public function packingReplenishmentDet()
    {
        return $this->hasManyThrough(
            PackingReplenishmentDet::class,
            ShipmentScheduleLoc::class,
            'ssd_id', // FK on shipment_schedule_loc
            'ssl_id', // FK on packing_replenishment_det
            'id',     // PK on shipment_schedule_mstr
            'id'      // PK on shipment_schedule_loc
        );
    }
}
