<?php

namespace App\Models\API\ShipmentSchedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentScheduleDet extends Model
{
    use HasFactory;

    protected $table = 'shipment_schedule_det';

    public function getShipmentScheduleMaster()
    {
        return $this->belongsTo(ShipmentScheduleMstr::class , 'ssm_id', 'id');
    }

    public function getShipmentScheduleLocation()
    {
        return $this->hasMany(ShipmentScheduleLoc::class, 'ssd_id', 'id');
    }
}
