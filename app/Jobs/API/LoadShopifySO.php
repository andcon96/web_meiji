<?php

namespace App\Jobs\API;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CustomerShipTo\CustomerShipTo;
use App\Models\SalesOrder\SOMstr;
use App\Services\APIServices;
use Illuminate\Support\Facades\Log;

class LoadShopifySO //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data, $userid, $key;

    /**
     * Create a new job instance.
     */
    public function __construct($data, $userid, $key)
    {
        $this->data = $data;
        $this->userid = $userid;
        $this->key = $key;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;
        $userid = $this->userid;
        $key = $this->key;


        // Find if existing Shipto
        $findShipTo = CustomerShipTo::where('cst_shopify_id', $data->customer->cstmr_id)->first();
        if (!$findShipTo) {
            // Create Shipto & Send QAD
            $createShipTo = (new APIServices())->createShipTo($data, $userid);
            if ($createShipTo[0] == false) {
                Log::channel('ShopifySO')->info($data->header->order_id ?? '' . ' ' . $createShipTo[1]);
                return; // prevent lanjut ke create so kalau nomor shipto tidak ada
            }
        } else {
            $createShipTo = [true, $findShipTo->cst_ship_to_code];
        }
        // dd($shopify_id);

        // Create SO & Send QAD
        $custShipTo = $createShipTo[1];
        $findSalesOrder = SOMstr::where('shopify_id', $key)->first();
        if (!$findSalesOrder) {
            $savedata = (new APIServices())->saveSalesOrderShopify($data, $custShipTo, $userid, false, $key);
            if ($savedata[0] == false) {
                Log::channel('ShopifySO')->info('saveSalesOrderShopify return error : ' . $data->header->order_id . ' ' . $savedata[1]);
            }
        }
    }
}
