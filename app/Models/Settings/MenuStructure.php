<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MenuStructure extends Model
{
    use HasFactory;
    use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

    protected $table = 'menu_structure';

    protected $fillable = ['menu_id', 'menu_icon_id', 'menu_parent_id', 'menu_sequence', 'created_by'];

    public function getParentKeyName()
    {
        return 'menu_parent_id';
    }

    public function getLocalKeyName()
    {
        return 'menu_id';
    }

    public function getMenu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }

    public function getMenuParent()
    {
        return $this->belongsTo(Menu::class, 'menu_parent_id', 'id');
    }

    public function getIcon()
    {
        return $this->belongsTo(Icon::class, 'menu_icon_id', 'id');
    }
}
