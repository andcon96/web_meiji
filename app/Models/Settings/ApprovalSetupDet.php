<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalSetupDet extends Model
{
    use HasFactory;

    protected $table = 'approval_setup_mstr';

    public function getApprovalSetupMstr()
    {
        return $this->belongsTo(ApprovalSetupMstr::class, 'asm_id', 'id');
    }
}
