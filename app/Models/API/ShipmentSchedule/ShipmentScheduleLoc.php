<?php

namespace App\Models\API\ShipmentSchedule;

use App\Models\API\PackingReplenishment\PackingReplenishmentDet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentScheduleLoc extends Model
{
    use HasFactory;

    protected $table = 'shipment_schedule_location';

    public function getShipmentScheduleDet()
    {
        return $this->belongsTo(ShipmentScheduleDet::class, 'ssd_id', 'id');
    }

    public function getPackingReplenishmentDet()
    {
        return $this->hasOne(PackingReplenishmentDet::class, 'ssl_id', 'id');
    }
}
