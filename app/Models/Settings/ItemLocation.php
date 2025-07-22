<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemLocation extends Model
{
    use HasFactory;

    public $table = 'item_location';

    public function getItem()
    {
        return $this->belongsTo(Item::class, 'il_item_id');
    }

    public function getLocationDetail()
    {
        return $this->belongsTo(LocationDetail::class, 'il_ld_id');
    }
}
