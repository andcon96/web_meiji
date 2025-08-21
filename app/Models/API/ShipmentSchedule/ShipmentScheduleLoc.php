<?php

namespace App\Models\API\ShipmentSchedule;

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
}
