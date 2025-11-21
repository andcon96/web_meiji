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
use App\Services\QxtendServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptServices;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class APISampling extends Controller
{

    public function getSamplingData(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->lot ?? ''; 
        $wsaData = (new WSAServices())->wsaGetSamplingData($item,  $lot);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }
        else {
            $listData = $wsaData[1];
            // foreach ($listData as $key => $value) {
            //     $data[] = [
            //         'domain' => (string)$value->inv_domain,
            //         'part' => (string)$value->inv_part, 
            //         'lot' => (string)$value->inv_lot,
            //         'loc'=> (string)$value->inv_loc,
            //         'site' => (string)$value->inv_site,
            //         'warehouse' => (string)$value->inv_wh,
            //         'bin' => (string)$value->inv_bin,
            //         'level' => (string)$value->inv_level,
            //         'qtyonhand' => (string)$value->inv_qtyoh,
            //     ];
            // }
            return response()->json(
            [
                'DataWSA' => $listData
            ],
            200
        );
        }



        return response()->json($wsaData[1]);

        // $trfid = $req->trfid;
        // $trfdata = singleTransfer::where('st_trfid', $trfid)->first();
        // if (!$trfdata) {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Data Not Found."
        //     ], 422);
        // } else {
        //     return GeneralResources::collection($trfdata);

        // }
    }

    public function getSingleTransferData(Request $req)
    {
        $search = $req->search;

        $trfdata = singleTransfer::where('st_status', 'Open');
        if ($search) {
            $trfdata =  $trfdata->where('st_trfid', 'LIKE', '%' . $search . '%')
                ->orWhere('st_item', 'LIKE', '%' . $search . '%')
                ->orWhere('st_lot', 'LIKE', '%' . $search . '%')
                ->get();
        }
        $trfdata = $trfdata->get();

        if (!$trfdata) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            return GeneralResources::collection($trfdata);
            // return response()->json(
            //     [
            //         'Data' => $trfdata
            //     ],
            //     200
            // );
        }
    }

    
}
