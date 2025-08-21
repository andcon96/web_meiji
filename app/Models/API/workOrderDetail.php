<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class workOrderDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'wod_nbr',
        'wod_part',
        'wod_entry_date',
        'wod_exp_date'
    ];
    protected $table = 'wod_det';

    public function getMaster()
    {
        return $this->belongsTo(workOrderMaster::class, 'wod_wo_id');
    }
}
