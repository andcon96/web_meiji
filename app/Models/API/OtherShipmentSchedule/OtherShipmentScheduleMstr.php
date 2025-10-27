<?php

namespace App\Models\API\OtherShipmentSchedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherShipmentScheduleMstr extends Model
{
    use HasFactory;

    protected $table = "other_shipment_schedule_mstr";

    public function getOtherShipmentScheduleDetail()
    {
        return $this->hasMany(OtherShipmentScheduleDet::class, "ossm_id", "id");
    }

    public function otherShipmentScheduleLoc()
    {
        return $this->hasManyThrough(
            OtherShipmentScheduleLoc::class,
            OtherShipmentScheduleDet::class,
            "ossm_id", // FK on shipment_schedule_det
            "ossd_id", // FK on shipment_schedule_loc
            "id", // PK on shipment_schedule_mstr
            "id", // PK on shipment_schedule_det
        );
    }
}
