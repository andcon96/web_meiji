<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetailPriceControlMstr extends Model
{
    use HasFactory;

    protected $table = 'retail_price_control_mstr';

    public function getDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'id');
    }

    public function getRetailPriceControlDet()
    {
        return $this->hasMany(RetailPriceControlDet::class, 'rpcm_id');
    }
}
