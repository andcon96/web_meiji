<?php

namespace App\Models\API\ShipmentSchedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentScheduleDet extends Model
{
    use HasFactory;

    protected $table = 'shipment_schedule_det';

    protected $fillable = [
        'ssd_sod_nbr', 'ssd_sod_site', 'ssd_sod_shipto', 'ssd_sod_line',
        'ssd_sod_part', 'ssd_sod_desc', 'ssd_sod_qty_ord',
        'created_by', 'ssd_status', 'ssd_sod_qty_pick',
    ];

    public function getShipmentScheduleMaster()
    {
        return $this->belongsTo(ShipmentScheduleMstr::class, 'ssm_id', 'id');
    }

    public function getShipmentScheduleLocation()
    {
        return $this->hasMany(ShipmentScheduleLoc::class, 'ssd_id', 'id');
    }
}
