<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderMaster extends Model
{
    use HasFactory;

    public $table = 'po_mstr';

    protected $fillable = [
        'po_nbr'
    ];

    public function getDetail()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'pod_po_mstr_id');
    }
}
