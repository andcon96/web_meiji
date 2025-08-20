<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalSetupDet extends Model
{
    use HasFactory;

    protected $table = 'approval_setup_det';

    public function getApprovalSetupMstr()
    {
        return $this->belongsTo(ApprovalSetupMstr::class, 'asm_id', 'id');
    }

    public function getUsers()
    {
        $userIds = explode(';', $this->acd_approval_user);
        return User::whereIn('id', $userIds)->get();
    }
}
