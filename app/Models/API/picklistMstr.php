<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class picklistMstr extends Model
{
    use HasFactory;
     protected $fillable = [
        'pl_nbr'
    ];
    protected $table = 'picklist_mstr';

     public function getWo()
    {
        return $this->hasMany(picklistWo::class, 'pl_id');
    }
}
