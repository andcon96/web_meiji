<?php

namespace App\Models\API;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalReceiptHistory extends Model
{
    use HasFactory;

    public $table = 'approval_receipt_history';

    public function getUserApprove()
    {
        return $this->hasOne(User::class, 'id', 'arh_user_approve');
    }

    public function getUserApproveAlt()
    {
        return $this->hasOne(User::class, 'id', 'arh_user_approve_alt');
    }

    public function getUserApproveBy()
    {
        return $this->hasOne(User::class, 'id', 'arh_approved_by');
    }
}
