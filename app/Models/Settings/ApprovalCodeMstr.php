<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalCodeMstr extends Model
{
    use HasFactory;

    protected $table = 'approval_codes';

    public function getApprovalCodeDetail()
    {
        return $this->hasMany(ApprovalCodeDet::class, 'acm_id', 'id');
    }

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function getMenu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }

    public function getDepartment()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
