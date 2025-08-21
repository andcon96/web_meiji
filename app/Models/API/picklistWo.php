<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class picklistWo extends Model
{
    use HasFactory;
     protected $fillable = [
        'pl_wo_nbr',
        'pl_wo_id'
    ];
    protected $table = 'picklist_wo';

     public function getDetail()
    {
        return $this->hasMany(picklistWoDet::class, 'pl_wod_wo_id');
    }
    public function getPicklist()
    {
        return $this->belongsTo(picklistMstr::class, 'pl_id');
    }
}
