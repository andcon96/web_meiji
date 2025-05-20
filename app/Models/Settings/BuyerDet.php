<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerDet extends Model
{
    use HasFactory;

    protected $table = 'buyer_det';

    public function getBuyerMstr()
    {
        return $this->belongsTo(BuyerMstr::class, 'buyer_mstr_id');
    }

    public function getUser()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'id');
    }
}
