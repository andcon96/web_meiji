<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryDetailEpoint extends Model
{
    use HasFactory;

    public $table = 'summary_detail_epoint';

    public function getMaster(){
        return $this->belongsTo(SummaryEpoint::class, 'sde_se_id', 'id');
    }
}
