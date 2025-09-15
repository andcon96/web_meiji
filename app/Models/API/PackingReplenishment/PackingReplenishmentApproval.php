<?php

namespace App\Models\API\PackingReplenishment;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingReplenishmentApproval extends Model
{
    use HasFactory;

    protected $table = 'packing_replenishment_approval';

    public function getPackingReplenishmentMstr()
    {
        return $this->belongsTo(PackingReplenishmentMstr::class, 'prm_id', 'id');
    }

    public function getCreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
