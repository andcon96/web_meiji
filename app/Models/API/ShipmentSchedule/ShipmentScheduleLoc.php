<?php

namespace App\Models\API\ShipmentSchedule;

use App\Models\API\PackingReplenishment\PackingReplenishmentDet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentScheduleLoc extends Model
{
    use HasFactory;

    protected $table = 'shipment_schedule_location';

    protected $fillable = [
        'ssd_id', 'ssl_site', 'ssl_warehouse', 'ssl_location', 'ssl_lotserial',
        'ssl_level', 'ssl_bin', 'ssl_qty_to_pick', 'ssl_qty_pick', 'created_by',
    ];

    public function getShipmentScheduleDet()
    {
        return $this->belongsTo(ShipmentScheduleDet::class, 'ssd_id', 'id');
    }

    public function getPackingReplenishmentDet()
    {
        return $this->hasOne(PackingReplenishmentDet::class, 'ssl_id', 'id');
    }
    
}
