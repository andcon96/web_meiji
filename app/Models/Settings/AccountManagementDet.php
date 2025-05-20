<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountManagementDet extends Model
{
    use HasFactory;

    protected $table = 'account_management_detail';

    public function getAccountManagementMstr()
    {
        return $this->belongsTo(AccountManagementMstr::class, 'am_mstr_id', 'id');
    }
}
