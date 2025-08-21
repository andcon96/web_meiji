<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class picklistWoDet extends Model
{
    use HasFactory;
     protected $fillable = [
        'pl_wod_nbr'
    ];
    protected $table = 'picklist_wo_det';

     public function getMaster()
    {
        return $this->belongsTo(picklistWo::class, 'pl_wod_wo_id');
    }
}
