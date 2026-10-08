<?php

namespace App\Models\API\ShipmentSchedule;

use App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentScheduleApproval extends Model
{
    use HasFactory;

    protected $table = 'shipment_schedule_approvals';

    protected $fillable = [
        'ssm_id',
        'ssa_sequence',
        'ssa_user_approver',
        'ssa_alt_user_approver',
        'ssa_status',
        'ssa_reason',
        'created_by',
        'updated_by',
    ];

    public function master()
    {
        return $this->belongsTo(ShipmentScheduleMstr::class, 'ssm_id', 'id');
    }

    public function approverUser()
    {
        return $this->belongsTo(User::class, 'ssa_user_approver', 'id');
    }

    public function altApproverUser()
    {
        return $this->belongsTo(User::class, 'ssa_alt_user_approver', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
