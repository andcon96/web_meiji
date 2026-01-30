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
use Illuminate\Support\Facades\Auth;
use App\Models\API\TransactionHistory;

class APIPengembalian extends Controller
{

    public function getPengembalianQo(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        if ($lot != '') {
            if ($item != '') {

                $wsaData = (new WSAServices())->wsaGetWarehouseSampling($item,  $lot, 'SAMPLING');
                if ($wsaData[0] == 'false') {
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "No Data Available"
                    ], 422);
                } else {
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
        } else {
            $wsaData = (new WSAServices())->wsaGetSamplingData($item,  $lot, 'SAMPLING');
            if ($wsaData[0] == 'false') {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "No Data Available"
                ], 422);
            } else {
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

        // $wsaData = (new WSAServices())->wsaGetSamplingData($item,  $lot,'SAMPLING');
        // if ($wsaData[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // }
        // else {
        //     $listData = $wsaData[1];

        //     return response()->json(
        //     [
        //         'DataWSA' => $listData
        //     ],
        //     200
        // );
        // }

        // return response()->json($wsaData[1]);

    }
    public function transferPengembalianQo(Request $req)
    {
        $data = $req->all();
        $item = $req->item;
        $lot = $req->lot;
        $sitefrom = $req->sitefrom;
        $siteto = $req->siteto;
        $locfrom = 'QC-QRT';
        $locto = $req->locto;
        $whfrom = $req->whfrom;
        $levelfrom = $req->levelfrom;
        $binfrom = $req->binfrom;
        $qty = $req->qty;
        DB::commit();
        // $hasil = (new WSAServices())->wsaTransferSamplingData($item, $lot,$sitefrom,$locto,'SAMPLING',$whfrom,$levelfrom,$binfrom,$qty);
        $hasil = (new WSAServices())->wsaTransferSamplingData($item, $lot, $sitefrom, $locfrom, 'SAMPLING', $whfrom, $levelfrom, $binfrom, $qty);

        if ($hasil == 'false') {
            log::channel('samplingLog')->info('masuk false transfer');
            return response()->json([
                'Status' => 'Error',
                'Message' => "Transfer sampling Item Failed for Item : " . $item
            ], 422);
        } else {
            // $transfer = (new QxtendServices())->qxTransferSingleItemTransfer($item,$qty,$sitefrom,$sitefrom,$locto,'BL3-PM',$lot,$lot,$whfrom,$whfrom,$levelfrom,$levelfrom,$binfrom,$binfrom);
            // if ($hasil == 'false') {
            // return response()->json([
            //     'Status' => 'Error',
            //     'Message' => "Transfer sampling Item Failed for Item : " . $item
            // ], 422);
            // } else {
            log::channel('samplingLog')->info('masuk true transfer');
            $user = Auth::user()->name;
            // Transaction History
            $newTransactionHistory = new TransactionHistory();
            $newTransactionHistory->tr_nbr = 'Sampling';
            $newTransactionHistory->tr_order = '';
            $newTransactionHistory->tr_program = 'Sampling Module';
            $newTransactionHistory->tr_activity = 'Insert Sampling From';
            $newTransactionHistory->tr_user = $user ?? '';
            $newTransactionHistory->tr_part = $item ?? '';
            $newTransactionHistory->tr_uom = '';
            $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
            $newTransactionHistory->tr_lot = $lot ?? '';
            $newTransactionHistory->tr_qty = $qty ?? '';
            $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
            $newTransactionHistory->tr_reference = '';
            $newTransactionHistory->tr_site = $siteto ?? '';
            $newTransactionHistory->tr_location = $locto ?? '';
            $newTransactionHistory->tr_warehouse = $whfrom ?? '';
            $newTransactionHistory->tr_level = $levelfrom ?? '';
            $newTransactionHistory->tr_bin = $binfrom ?? '';
            $newTransactionHistory->tr_remark = '';
            $newTransactionHistory->save();

            return response()->json([
                'Status' => 'Success',
                'Message' => "Transfer sampling Item Success for Item : " . $item
            ], 200);
            // }
        }
    }

    public function getLotPengembalian(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->search ?? '';

        $wsaData = (new WSAServices())->wsaGetLotSampling($item,  $lot, 'SAMPLING');
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
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

    public function checkWarehouseReturn(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->lot ?? '';
        $wh = $req->warehouse ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';

        $wsaData = (new WSAServices())->wsaGetWarehouseCheckReturn($item,  $lot, 'SAMPLING', $wh, $level, $bin);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
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
