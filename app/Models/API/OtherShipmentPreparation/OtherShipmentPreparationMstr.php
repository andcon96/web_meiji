<?php

namespace App\Models\API\OtherShipmentPreparation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\User;

class OtherShipmentPreparationMstr extends Model
{
    use HasFactory;

    protected $table = "other_shipment_preparation_mstr";

    public function getOtherShipmentPreparationDet()
    {
        return $this->hasMany(OtherShipmentPreparationDet::class, "ospm_id", "id");
    }

    public function getCreatedBy()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }
}
