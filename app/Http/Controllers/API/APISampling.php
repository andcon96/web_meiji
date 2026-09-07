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
use App\Models\API\xxinvDet;
use App\Models\Settings\Domain;
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
        $search = $req->query('search') ?? '';
        // dd('a');
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        if ($lot != '') {
            if ($item != '') {
                $xxinvdet = xxinvDet::join('item_master', 'item_master.im_item_part',  'xxinv_det.xxinv_part')
                ->where('xxinv_domain', $domainCode)
                    
                    ->where('xxinv_loc', 'QC-QRT')
                    ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
                    ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
                    
                    ->select([
                        'xxinv_domain  as inv_domain',
                        'xxinv_part    as inv_part',
                        'xxinv_lot     as inv_lot',
                        'xxinv_wrh     as inv_wh',
                        'xxinv_level   as inv_level',
                        'xxinv_bin     as inv_bin',
                        'xxinv_qtyoh   as inv_qtyoh',
                        'xxinv_qty_smp as inv_qtysmp',
                        'xxinv_site    as inv_site',
                        'item_master.im_item_um as inv_um',
                    ])
                    ->get()
                    ->values();
                if ($xxinvdet->isEmpty()) {
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "No Data Available"
                    ], 422);
                } else {
                    return response()->json(
                        [
                            'DataWSA' => $xxinvdet
                        ],
                        200
                    );
                }
                // // $wsaData = (new WSAServices())->wsaGetWarehouseSampling($item,  $lot, 'QC-QRT');
                // if ($wsaData[0] == 'false') {
                //     return response()->json([
                //         'Status' => 'Error',
                //         'Message' => "No Data Available"
                //     ], 422);
                // } else {
                //     $listData = $wsaData[1];

                //     return response()->json(
                //         [
                //             'DataWSA' => $listData
                //         ],
                //         200
                //     );
                // }

                // return response()->json($wsaData[1]);
            }
        } else {

            $xxinvdet = xxinvDet::join('item_master', 'item_master.im_item_part',  'xxinv_det.xxinv_part')
            ->where('xxinv_domain', $domainCode)
                ->where('xxinv_loc', 'QC-QRT')
                ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
                ->select([
                    'xxinv_domain as inv_domain',
                    'xxinv_part   as inv_part',
                    'xxinv_lot    as inv_lot',
                    'xxinv_wrh    as inv_wh',
                    'xxinv_level  as inv_level',
                    'xxinv_bin    as inv_bin',
                    'xxinv_qtyoh  as inv_qtyoh',
                    'im_item_um    as inv_um',
                ])
                ->orderBy('xxinv_part')
                ->get()
                ->unique('inv_part') // first row per part, same as FIRST-OF
                ->values();
            if ($xxinvdet->isEmpty()) {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "No Data Available"
                ], 422);
            } else {
                return response()->json(
                    [
                        'DataWSA' => $xxinvdet
                    ],
                    200
                );
            }
            // dd($xxinvdet);
            // $wsaData = (new WSAServices())->wsaGetSamplingData($item,  $lot, 'QC-QRT');
            // if ($wsaData[0] == 'false') {
            //     return response()->json([
            //         'Status' => 'Error',
            //         'Message' => "No Data Available"
            //     ], 422);
            // } else {
            //     $listData = $wsaData[1];

            //     return response()->json(
            //         [
            //             'DataWSA' => $listData
            //         ],
            //         200
            //     );
            // }

            return response()->json($wsaData[1]);
        }
    }

    public function getLotSampling(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->search ?? '';
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $xxinvDet = xxinvDet::join('item_master', 'item_master.im_item_part',  'xxinv_det.xxinv_part')
        ->where('xxinv_domain', $domainCode)
            ->where('xxinv_loc', 'QC-QRT')
            ->where('xxinv_part', $item)
            ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
            ->select([
                'xxinv_domain as inv_domain',
                'xxinv_lot    as inv_lot',
                'xxinv_site   as inv_site',
                'item_master.im_item_um as inv_um',
                'xxinv_qtyoh  as inv_qtyoh',
                'xxinv_part    as inv_part',
                
            ])
            ->orderBy('xxinv_lot')
            ->get()
            ->unique('inv_lot') // first row per xxinv_lot, same as FIRST-OF
            ->values();
        if ($xxinvDet->isEmpty()) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
            return response()->json(
                [
                    'DataWSA' => $xxinvDet
                ],
                200
            );
        }
        // $wsaData = (new WSAServices())->wsaGetLotSampling($item,  $lot, 'QC-QRT');
        // if ($wsaData[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // } else {
        //     $listData = $wsaData[1];

        //     return response()->json(
        //         [
        //             'DataWSA' => $listData
        //         ],
        //         200
        //     );
        // }

        // return response()->json($wsaData[1]);
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
        DB::beginTransaction();
        try {
            $domain = Domain::first();
            $domainCode = $domain->domain ?? '';
            $xxinvdet = xxinvDet::where('xxinv_domain', $domainCode)
                ->where('xxinv_part', $item)
                ->where('xxinv_lot', $lot)
                ->where('xxinv_site', $sitefrom)
                ->where('xxinv_loc', 'QC-QRT')
                ->where('xxinv_wrh', $whfrom)
                ->where('xxinv_level', $levelfrom)
                ->where('xxinv_bin', $binfrom)
                ->first();


            if (!$xxinvdet) {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Transfer sampling Item Failed for Item : " . $item
                ], 422);
            } else {
                //     if xxinv_det.xxinv_qty_wrh = 0 then lvc_sumqty = xxinv_qtyoh.
                // else lvc_sumqty = xxinv_qty_wrh.
                // lvc_sumqty = lvc_sumqty - inpqtyoh.
                // assign
                // xxinv_det.xxinv_qty_smp = xxinv_det.xxinv_qty_smp + inpqtyoh
                // xxinv_det.xxinv_qty_wrh = lvc_sumqty. 
                // outOk = true.
                // $lvc_sumqty = 0;
                if ($xxinvdet->xxinv_qty_wrh == 0) {
                    $lvc_sumqty = $xxinvdet->xxinv_qtyoh;
                } else {
                    $lvc_sumqty = $xxinvdet->xxinv_qty_wrh;
                }
                log::info($qty);
                $lvc_sumqty = $lvc_sumqty - $qty;
                $xxinvdet->xxinv_qty_smp = $xxinvdet->xxinv_qty_smp + $qty;
                $xxinvdet->xxinv_qty_wrh = $lvc_sumqty;
                $xxinvdet->save();


                //getDetail Receipt

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

                $newTransactionHistory->tr_site = $siteto ?? '';
                $newTransactionHistory->tr_location = $locto ?? '';
                $newTransactionHistory->tr_warehouse = $whfrom ?? '';
                $newTransactionHistory->tr_level = $levelfrom ?? '';
                $newTransactionHistory->tr_bin = $binfrom ?? '';
                $newTransactionHistory->tr_remark = '';
                $newTransactionHistory->save();
                DB::commit();

                return response()->json([
                    'Status' => 'Success',
                    'Message' => "Transfer sampling Item Success for Item : " . $item
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Transfer sampling Item Failed for Item : " . $item . " Error : " . $e->getMessage()
            ], 422);
        }
        // DB::commit();
        // $hasil = (new WSAServices())->wsaTransferSamplingData($item, $lot,$sitefrom,$locto,'QC-QRT',$whfrom,$levelfrom,$binfrom,$qty);

    }

     public function checkWarehouseSampling(Request $req)
    {
        // dd($req->all());
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';
        $wh = $req->warehouse ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $domain = Domain::first();
        $inpdomain = $domain->domain ?? '';
        $records = xxinvDet::where('xxinv_domain', $inpdomain)
            ->where('xxinv_loc', 'QC-QRT')
            ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
            ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
            ->where('xxinv_wrh', $wh)
            ->where('xxinv_level', $level)
            ->where('xxinv_bin', $bin)
            // ->where('xxinv_qty_smp', '>', 0)
            ->first();
            // dd($inpdomain, $item, $lot, $wh, $level, $bin, $records);
        if (!$records) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
            return response()->json($records->xxinv_qtyoh,
                200
            );
        }
        // $wsaData = (new WSAServices())->wsaGetWarehouseCheckReturn($item,  $lot, 'SAMPLING', $wh, $level, $bin);
        // if ($wsaData[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // } else {
        //     $listData = $wsaData[1];

        //     return response()->json(
        //         [
        //             'DataWSA' => $listData
        //         ],
        //         200
        //     );
        // }

        // return response()->json($wsaData[1]);
    }
}
