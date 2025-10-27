<?php

namespace App\Models\API\OtherShipmentSchedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherShipmentScheduleDet extends Model
{
    use HasFactory;

    protected $table = "other_shipment_schedule_det";

    public function getOtherShipmentScheduleMaster()
    {
        return $this->belongsTo(OtherShipmentScheduleMstr::class, "ossm_id", "id");
    }

    public function getOtherShipmentScheduleLocation()
    {
        return $this->hasMany(OtherShipmentScheduleLoc::class, "ossd_id", "id");
    }
}
