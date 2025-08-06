<?php

namespace App\Models\API\ShipmentSchedule;

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
}
