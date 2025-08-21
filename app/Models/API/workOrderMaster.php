<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class workOrderMaster extends Model
{
    use HasFactory;
    protected $fillable = [
        'wo_nbr',
        'wo_id'
    ];
    protected $table = 'wo_mstr';

     public function getDetail()
    {
        return $this->hasMany(workOrderDetail::class, 'wod_wo_id');
    }
}
