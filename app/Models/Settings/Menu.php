<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menus';

    protected $fillable = ['id', "menu_name", "menu_route","has_approval","created_by"];

    public function getMenuName()
    {
        return self::whereNotNull('menu_route')->pluck('menu_route')->first();
    }

    public function isCheckedForRole()
    {
        $roleID = Auth::user()->role_id;
        // Check if there is an association between the menu item and the role
        return $this->roles()->where('role_id', $roleID)->exists();
    }

    /**
     * Define the relationship between Menu and Role models.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_access', 'menu_id', 'role_id');
    }
}
