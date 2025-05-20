<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerMstr extends Model
{
    use HasFactory;

    protected $table = 'buyer_mstr';

    public function getBuyerDet()
    {
        return $this->hasMany(BuyerDet::class, 'buyer_mstr_id');
    }
}
