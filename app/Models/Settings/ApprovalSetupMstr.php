<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalSetupMstr extends Model
{
    use HasFactory;

    protected $table = 'approval_setup_mstr';

    public function getApprovalSetupDet()
    {
        return $this->hasMany(ApprovalSetupDet::class, 'asm_id', 'id');
    }

    public function getMenu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }
}
