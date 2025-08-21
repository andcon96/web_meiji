<?php

namespace App\Models\API\PackingReplenishment;

use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingReplenishmentDet extends Model
{
    use HasFactory;

    protected $table = 'packing_replenishment_det';

    public function getShipmentScheduleLocation()
    {
        return $this->belongsTo(ShipmentScheduleLoc::class, 'ssl_id', 'id');
    }
}
