<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\LocationDetail;
use App\Models\API\workOrderMaster;
use App\Models\API\workOrderDetail;
use App\Models\API\picklistMstr;
use App\Models\API\picklistWo;
use App\Models\API\picklistWoDet;
use App\Models\API\prefixWorkOrder;
use App\Models\API\picklistHistory;
use App\Models\API\picklistLocationTo;
use App\Models\Settings\SingleTransferPrefix;
use App\Models\API\SingleTransfer;
use App\Services\WSAServices;
use App\Models\API\TransactionHistory;
use App\Services\QxtendServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptServices;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;

class APISampling extends Controller
{

    public function getSamplingData(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->lot ?? ''; 
       if($lot != ''){
        if($item != ''){
            
            $wsaData = (new WSAServices())->wsaGetWarehouseSampling($item,  $lot,'QC-QRT');
            if ($wsaData[0] == 'false') {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "No Data Available"
                ], 422);
            }
            else {
            $listData = $wsaData[1];

            return response()->json(
            [
                'DataWSA' => $listData
            ],
            200
        );
        }

        return response()->json($wsaData[1]);
        }
       }
       else{
         $wsaData = (new WSAServices())->wsaGetSamplingData($item,  $lot,'QC-QRT');
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }
        else {
            $listData = $wsaData[1];

            return response()->json(
            [
                'DataWSA' => $listData
            ],
            200
        );
        }

        return response()->json($wsaData[1]);
       }
       

    }

     public function getLotSampling(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->search ?? ''; 
       
        $wsaData = (new WSAServices())->wsaGetLotSampling($item,  $lot,'QC-QRT');
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }
        else {
            $listData = $wsaData[1];

            return response()->json(
            [
                'DataWSA' => $listData
            ],
            200
        );
        }

        return response()->json($wsaData[1]);

    }

    public function transferSampling(Request $req)
    {

        /*
        $item = $data['item'];
        $sitefrom = $data['sitefrom'];
        $siteto = $data['siteto'];
        $locfrom = $data['locfrom'];
        $locto = $data['locto'];
        $whfrom = $data['whfrom'];
        $levelfrom = $data['levelfrom'];
        $binfrom = $data['binfrom'];
        $qty = $data['qty'];
        $wh = $this->nullConversion($data['wh']);
        $ref = $this->nullConversion($data['ref']);
        $level = $this->nullConversion($data['level']);
        $bin = $this->nullConversion($data['bin']);
        $lot = $this->nullConversion($data['lot']);
        $prefixTable = singleTransferPrefix::first();
        $prefix = $prefixTable->stp_prefix;
        $runningnbr = $prefixTable->stp_running_nbr;
        $nextrunningnbr = (int) $runningnbr + 1;
        $newRunningNbr = str_pad($nextrunningnbr, 6, '0', STR_PAD_LEFT);
        $newPrefix = $prefix . $newRunningNbr;
        */
            $data = $req->all();
            $item = $req->item;
            $lot = $req->lot;
            $sitefrom = $req->sitefrom;
            $siteto = $req->siteto;
            $locfrom = $req->locfrom;
            $locto = $req->locto;
            $whfrom = $req->whfrom;
            $levelfrom = $req->levelfrom;
            $binfrom = $req->binfrom;   
            $qty = $req->qty;
            DB::commit();
            $hasil = (new WSAServices())->wsaTransferSamplingData($item, $lot,$sitefrom,$locto,'QC-QRT',$whfrom,$levelfrom,$binfrom,$qty);
            
            if ($hasil == false) {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Transfer sampling Item Failed for Item : " . $item
                ], 422);
            } else {

                //getDetail Receipt

                    $user = Auth::user()->name;
                     // Transaction History
                        $newTransactionHistory = new TransactionHistory();
                        $newTransactionHistory->tr_nbr = 'Sampling';
                        $newTransactionHistory->tr_program = 'Sampling Module';
                        $newTransactionHistory->tr_activity = 'Insert Sampling From';
                        $newTransactionHistory->tr_user = $user;
                        $newTransactionHistory->tr_part = $item;
                        $newTransactionHistory->tr_uom = '';
                        $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                        $newTransactionHistory->tr_lot = $lot;
                        $newTransactionHistory->tr_qty = $qty;
                        $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
 
                        $newTransactionHistory->tr_site = $siteto;
                        $newTransactionHistory->tr_location = $locto;
                        $newTransactionHistory->tr_warehouse = $whfrom;
                        $newTransactionHistory->tr_level = $levelfrom;
                        $newTransactionHistory->tr_bin = $binfrom;
                        $newTransactionHistory->tr_remark = '';
                        $newTransactionHistory->save();


                return response()->json([
                    'Status' => 'Success',
                    'Message' => "Transfer sampling Item Success for Item : " . $item
                ], 200);
            }
        
    }


    
}
