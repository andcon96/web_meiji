<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuAccess extends Model
{
    use HasFactory;

    protected $table = 'menu_access';

    public function getRole()
    {
        return $this->belongsTo(Role::class, 'role_id', 'menu_id');
    }
}
