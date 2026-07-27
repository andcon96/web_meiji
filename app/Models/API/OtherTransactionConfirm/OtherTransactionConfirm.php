<?php

namespace App\Models\API\OtherTransactionConfirm;

use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationMstr; // 👈 FIX: namespace yang benar
use  App\Models\Settings\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherTransactionConfirm extends Model
{
    use HasFactory;

    protected $table = 'other_transaction_confirm';

    protected $fillable = [
        'otpm_id',
        'otc_sequence',
        'otc_user_approver',
        'otc_alt_user_approver',
        'otc_status',
        'otc_reason',
        'created_by',
        'updated_by',
    ];

    /**
     * Relasi ke master Other Shipment Preparation
     */
    public function getOtherShipmentPreparationMstr()
    {
        return $this->belongsTo(OtherShipmentPreparationMstr::class, 'otpm_id');
    }

    /**
     * Relasi ke user pembuat data
     */
    public function getCreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang update data
     */
    public function getUpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
