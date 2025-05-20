<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWorkCenter extends Model
{
    use HasFactory;

    protected $table = 'user_work_center';

    public function getUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getCreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function getUpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
