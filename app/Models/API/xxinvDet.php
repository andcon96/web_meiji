<?php

namespace App\Models\API;
use App\Models\Settings\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class xxinvDet extends Model
{
    use HasFactory;
    /*
     protected $fillable = [
        'pl_wod_nbr'
    ];
    */
    protected $table = 'xxinv_det';
    public function itemMaster()
{ 
    return $this->belongsTo(Item::class, 'xxinv_part', 'im_item_part'); 
}
    
}
