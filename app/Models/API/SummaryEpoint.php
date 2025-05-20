<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryEpoint extends Model
{
    use HasFactory;

    protected $table = 'summary_epoint';

    public function getDetailSummary()
    {
        return $this->hasMany(SummaryDetailEpoint::class, 'sde_se_id', 'id');
    }

    public function getDetailSummaryWithError()
    {
        return $this->hasMany(SummaryDetailEpoint::class, 'sde_se_id', 'id')->whereNotNull('sde_error_qxtend');
    }
}
