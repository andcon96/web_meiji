<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptPallet extends Model
{
    use HasFactory;

    protected $table = 'receipt_det_pallet';

    public function getDetail()
    {
        return $this->belongsTo(ReceiptDetail::class, 'rdp_rd_det_id', 'id');
    }
}
