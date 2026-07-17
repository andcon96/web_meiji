<?php

namespace App\Models\API\PackingReplenishment;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingReplenishmentApproval extends Model
{
    use HasFactory;

    protected $table = 'packing_replenishment_approval';

    protected $fillable = [
        'prm_id', 'pra_sequence', 'pra_user_approver', 'pra_status',
        'created_by', 'updated_by',
    ];

    public function getPackingReplenishmentMstr()
    {
        return $this->belongsTo(PackingReplenishmentMstr::class, 'prm_id', 'id');
    }

    public function getCreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
