<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationDetail extends Model
{
    use HasFactory;

    public $table = 'location_detail';

    protected $fillable = ['id', 'ld_location_id', 'ld_lot_serial', 'ld_rak', 'ld_bin'];

    public function getMaster()
    {
        return $this->belongsTo(Location::class, 'ld_location_id');
    }

    public function getListItem()
    {
        return $this->hasMany(ItemLocation::class, 'il_ld_id');
    }
}
