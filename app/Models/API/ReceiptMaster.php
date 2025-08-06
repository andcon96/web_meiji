<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptMaster extends Model
{
    use HasFactory;

    protected $table = 'receipt_mstr';

    public function getDetailReceipt()
    {
        return $this->hasMany(ReceiptDetail::class, 'rd_rm_id', 'id');
    }

    public function getPurchaseOrderMaster()
    {
        return $this->belongsTo(PurchaseOrderMaster::class, 'rm_po_mstr_id', 'id');
    }
}
