<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalCodeDet extends Model
{
    use HasFactory;

    protected $table = 'approval_code_detail';

    public function getApprovalCodeMstr()
    {
        return $this->belongsTo(ApprovalCodeMstr::class, 'acm_id', 'id');
    }

    public function getRole()
    {
        return $this->belongsTo(Role::class, 'acd_approval_role', 'id');
    }

    public function getNotifyRole()
    {
        return $this->belongsTo(Role::class, 'acd_notify_role', 'id');
    }

    public function getUsers()
    {
        $userIds = explode(';', $this->acd_approval_user);
        return User::whereIn('id', $userIds)->get();
    }

    public function getNotifUsers()
    {
        $userIds = explode(';', $this->acd_notify_user);
        return User::whereIn('id', $userIds)->get();
    }
}
