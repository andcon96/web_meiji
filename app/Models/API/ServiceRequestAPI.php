<?php

namespace App\Models\API;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestAPI extends Model
{
    protected $table = 'asset_mstr';

    public function getAssetLocation(){
        return $this->hasOne(AssetLocationAPI::class, 'asloc_code', 'asset_loc');
    }
}
