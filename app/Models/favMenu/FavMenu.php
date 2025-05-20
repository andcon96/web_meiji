<?php

namespace App\Models\favMenu;

use App\Models\MenuMaster\MenuMaster;
use Illuminate\Database\Eloquent\Model;

class FavMenu extends Model
{
    protected $table = 'fav_menu';

    public function getMenu()
    {
        return $this->belongsTo(MenuMaster::class, 'fm_mm_id');
    }
}
