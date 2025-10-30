<?php

namespace App\Models\API\OtherShipmentPreparation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleLoc;

class OtherShipmentPreparationDet extends Model
{
    use HasFactory;

    protected $table = "other_shipment_preparation_det";

    public function getOtherShipmentScheduleLocation()
    {
        return $this->belongsTo(OtherShipmentScheduleLoc::class, "ossl_id", "id");
    }

    public function getOtherShipmentPreparationMstr()
    {
        return $this->belongsTo(OtherShipmentPreparationMstr::class, "ospm_id", "id");
    }
}
