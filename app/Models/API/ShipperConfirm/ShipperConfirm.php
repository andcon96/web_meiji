<?php

namespace App\Models\API\ShipperConfirm;

use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\User;

class ShipperConfirm extends Model
{
    use HasFactory;

    protected $table = "shipper_confirm";

    public function getShipmentScheduleMaster()
    {
        return $this->belongsTo(ShipmentScheduleMstr::class, "ssm_id", "id");
    }

    public function getCreatedBy()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }
}
