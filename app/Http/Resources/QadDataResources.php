<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QadDataResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        // $data = [
        //     // 'rn_number' => $this['qd_rn'],
        //     // 'data' => $this['qd_data'],
        //     // 'tipe' => $this['qd_url']
        //     json_decode($this['qd_data']) 
        // ];
        $item = json_decode($this['qd_data']);
        
        return $item[0];

        // return parent::toArray($request);
    }
}
