<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostControlMstr extends Model
{
    use HasFactory;

    protected $table = 'cost_control_mstr';

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function getCostControlDet()
    {
        return $this->hasMany(CostControlDet::class, 'ccm_id', 'id');
    }
}
