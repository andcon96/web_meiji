<?php

namespace App\Models\API\OtherShipmentSchedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationDet;

class OtherShipmentScheduleLoc extends Model
{
    use HasFactory;

    protected $table = "other_shipment_schedule_location";

    public function getOtherShipmentScheduleDet()
    {
        return $this->belongsTo(OtherShipmentScheduleDet::class, "ossd_id", "id");
    }

    public function getOtherShipmentPreparationDet()
    {
        return $this->hasOne(OtherShipmentPreparationDet::class, "ossl_id", "id");
    }
}
