<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    use HasFactory;

    public $table = 'pod_det';

    protected $fillable = [
        'pod_po_mstr_id',
        'pod_line'
    ];

    public function getMaster()
    {
        return $this->belongsTo(PurchaseOrderMaster::class, 'pod_po_mstr_id');
    }
}
