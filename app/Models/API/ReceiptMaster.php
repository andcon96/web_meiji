<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptMaster extends Model
{
    use HasFactory;

    protected $table = 'receipt_mstr';

    public function getDetail()
    {
        return $this->hasMany(ReceiptDetail::class, 'rd_rm_id', 'id');
    }

    public function getPurchaseOrderMaster()
    {
        return $this->belongsTo(PurchaseOrderMaster::class, 'id', 'rm_po_mstr_id');
    }
}
