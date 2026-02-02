<?php

namespace App\Models\API;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\API\ApprovalReceiptHistory;
class ApprovalReceiptTempAll extends Model
{
    use HasFactory;

    public $table = 'approval_receipt_temp';

    public function getReceiptDetail()
    {
        return $this->belongsTo(ReceiptDetail::class, 'art_receipt_det_id');
    }

    public function getUserApprove()
    {
        return $this->hasOne(User::class, 'id', 'art_user_approve');
    }

    public function getUserApproveAlt()
    {
        return $this->hasOne(User::class, 'id', 'art_user_approve_alt');
    }

    public function getUserApproveBy()
    {
        return $this->hasOne(User::class, 'id', 'art_approved_by');
    }
    public function getHistory()
    {
        return $this->hasMany(ApprovalReceiptHistory::class, 'arh_receipt_det_id', 'art_receipt_det_id')->orderBy('id','desc');
    }
     
}
