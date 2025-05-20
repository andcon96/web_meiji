<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountManagementMstr extends Model
{
    use HasFactory;

    protected $table = 'account_management';

    public function getAccountManagementDetail()
    {
        return $this->hasMany(AccountManagementDet::class, 'am_mstr_id', 'id');
    }
}
