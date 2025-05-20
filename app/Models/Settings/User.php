<?php

namespace App\Models\Settings;

// use App\Models\favMenu\FavMenu;
use App\Models\Settings\Department;
use App\Models\Settings\Domain;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, HasFactory;

    protected $table = 'users';

    public $primaryKey = 'id';

    public function getRole()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function getDepartment()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function getWorkCenter()
    {
        return $this->hasMany(UserWorkCenter::class, 'user_id', 'id');
    }

    // public function getFavMenu()
    // {
    //     return $this->hasMany(FavMenu::class, 'fm_user_id', 'id');
    // }
}
