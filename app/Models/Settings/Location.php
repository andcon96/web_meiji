<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    public $table = 'location';

    public function getDetailLocation()
    {
        return $this->hasMany(LocationDetail::class, 'ld_location_id');
    }
}
