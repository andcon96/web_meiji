<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PicklistShopping extends Model
{
    use HasFactory;

    protected $table = 'picklist_shopping';
    public function getPicklistShoppingDetail()
    {
        return $this->hasMany(PicklistShoppingDetail::class, 'ps_id');
    }
}
