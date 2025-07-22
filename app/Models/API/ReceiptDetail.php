<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptDetail extends Model
{
    use HasFactory;

    protected $table = 'receipt_det';

    public function getMaster()
    {
        return $this->belongsTo(ReceiptMaster::class, 'rd_rm_id', 'id');
    }

    public function getPurchaseOrderDetail()
    {
        return $this->belongsTo(PurchaseOrderDetail::class, 'rd_pod_det_id', 'id');
    }

    public function getAttachment()
    {
        return $this->hasMany(ReceiptAttachment::class, 'rda_rd_det_id', 'id');
    }

    public function getDokumen()
    {
        return $this->hasOne(ReceiptDokumen::class, 'rdd_rd_det_id', 'id');
    }

    public function getKemasan()
    {
        return $this->hasOne(ReceiptKemasan::class, 'rdk_rd_det_id', 'id');
    }

    public function getKendaraan()
    {
        return $this->hasOne(ReceiptKendaraan::class, 'rdken_rd_det_id', 'id');
    }

    public function getPenanda()
    {
        return $this->hasOne(ReceiptPenanda::class, 'rdp_rd_det_id', 'id');
    }
}
