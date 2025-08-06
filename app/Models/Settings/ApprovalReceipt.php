<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalReceipt extends Model
{
    use HasFactory;

    public $table = 'approval_receipt';

    public $fillable = [
        'ar_sequence',
        'ar_user_approve',
        'ar_user_approve_alt'
    ];

    public function getUserApprove()
    {
        return $this->hasOne(User::class, 'id', 'ar_user_approve');
    }

    public function getUserApproveAlt()
    {
        return $this->hasOne(User::class, 'id', 'ar_user_approve_alt');
    }
}
