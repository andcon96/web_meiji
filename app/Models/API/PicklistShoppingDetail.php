<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PicklistShoppingDetail extends Model
{
    use HasFactory;

    protected $table = 'picklist_shopping_detail';

    public function getPicklistShopping()
    {
        return $this->belongsTo(PicklistShopping::class, 'ps_id');
    }
}
