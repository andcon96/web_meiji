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
use App\Models\API\xxinvDet;
use App\Models\API\xxinvDetApproval;
use App\Models\Settings\Domain;
use App\Models\Settings\User;
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
        $domain = Domain::first();
        $inpdomain = $domain->domain ?? '';
        if ($lot != '') {
            if ($item != '') {
                $records = xxinvDet::where('xxinv_domain', $inpdomain)
                    ->where('xxinv_loc', 'QC-QRT')
                    ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
                    ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
                    ->where('xxinv_qty_smp', '>', 0)
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
                    ])
                    ->get()
                    ->values();
                if ($records->isEmpty()) {
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "No Data Available"
                    ], 422);
                } else {
                    return response()->json(
                        [
                            'DataWSA' => $records
                        ],
                        200
                    );
                }
                // $wsaData = (new WSAServices())->wsaGetWarehouseSampling($item,  $lot, 'SAMPLING');
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
            $records = xxinvDet::where('xxinv_domain', $inpdomain)
                ->where('xxinv_loc', 'QC-QRT')
                ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
                ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
                ->where('xxinv_qty_smp', '>', 0)
                ->select([
                    'xxinv_domain as inv_domain',
                    'xxinv_part   as inv_part',
                    'xxinv_lot    as inv_lot',
                    'xxinv_wrh    as inv_wh',
                    'xxinv_level  as inv_level',
                    'xxinv_bin    as inv_bin',
                    'xxinv_qtyoh  as inv_qtyoh',
                ])
                ->orderBy('xxinv_part')
                ->get()
                ->unique('inv_part') // first row per xxinv_part, same as FIRST-OF
                ->values();

            if ($records->isEmpty()) {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "No Data Available"
                ], 422);
            } else {
                return response()->json(
                    [
                        'DataWSA' => $records
                    ],
                    200
                );
            }
            // $wsaData = (new WSAServices())->wsaGetSamplingData($item,  $lot, 'SAMPLING');
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
        $approver = $req->approver;
        $userapprover = User::where('name', $approver)->first();
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
                $xxinvdet->xxinv_qty_smp = $xxinvdet->xxinv_qty_smp - $qty;
                // $xxinvdet->xxinv_qty_wrh = $xxinvdet->xxinv_qty_wrh + $qty;
                $xxinvdet->save();

                $xxinvdetApproval = new xxinvDetApproval();
                $xxinvdetApproval->xxinv_domain = $xxinvdet->xxinv_domain;
                $xxinvdetApproval->xxinv_part = $xxinvdet->xxinv_part;
                $xxinvdetApproval->xxinv_lot = $xxinvdet->xxinv_lot;
                $xxinvdetApproval->xxinv_locfrom = 'SAMPLING';
                $xxinvdetApproval->xxinv_binfrom = $xxinvdet->xxinv_bin;
                $xxinvdetApproval->xxinv_levelfrom = $xxinvdet->xxinv_level;
                $xxinvdetApproval->xxinv_sitefrom = $siteto;
                $xxinvdetApproval->xxinv_wrhfrom = $xxinvdet->xxinv_wrh;
                $xxinvdetApproval->xxinv_locto = $locto;
                $xxinvdetApproval->xxinv_binto = $xxinvdet->xxinv_bin;
                $xxinvdetApproval->xxinv_levelto = $xxinvdet->xxinv_level;
                $xxinvdetApproval->xxinv_siteto = $siteto;
                $xxinvdetApproval->xxinv_wrhto = $xxinvdet->xxinv_wrh;
                $xxinvdetApproval->xxinv_qtyoh = $xxinvdet->xxinv_qtyoh;
                $xxinvdetApproval->xxinv_qty_pick = $qty;
                $xxinvdetApproval->xxinv__chr01 = $xxinvdet->xxinv__chr01;
                $xxinvdetApproval->xxinv__chr02 = $xxinvdet->xxinv__chr02;
                $xxinvdetApproval->xxinv__long1 = $xxinvdet->xxinv__long1;
                $xxinvdetApproval->xxinv__dec01 = $xxinvdet->xxinv__dec01;
                $xxinvdetApproval->xxinv__dec02 = $xxinvdet->xxinv__dec02;
                $xxinvdetApproval->xxinv__dte01 = $xxinvdet->xxinv__dte01;
                $xxinvdetApproval->xxinv_ref = $xxinvdet->xxinv_ref;
                $xxinvdetApproval->xxinv_entry_date = $xxinvdet->xxinv_entry_date;
                $xxinvdetApproval->xxinv_exp_date = $xxinvdet->xxinv_exp_date;
                $xxinvdetApproval->xxinv_due_date = $xxinvdet->xxinv_due_date;
                $xxinvdetApproval->xxinv_rel_date = $xxinvdet->xxinv_rel_date;
                $xxinvdetApproval->xxinv_ord_date = $xxinvdet->xxinv_ord_date;
                $xxinvdetApproval->xxinv_qty_wrh = $xxinvdet->xxinv_qty_wrh;
                $xxinvdetApproval->xxinv_qty_smp = $xxinvdet->xxinv_qty_smp;
                $xxinvdetApproval->xxinv_qty_shp = $xxinvdet->xxinv_qty_shp;
                $xxinvdetApproval->xxinv_qty_wip = $xxinvdet->xxinv_qty_wip;
                $xxinvdetApproval->xxinv_status = 'Waiting';
                $xxinvdetApproval->xxinv_approver = $userapprover->username ?? '';
                $xxinvdetApproval->save();
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
                DB::commit();
                return response()->json([
                    'Status' => 'Success',
                    'Message' => "Transfer sampling Item Success for Item : " . $item
                ], 200);
                // }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Transfer pengembalian Item Failed for Item : " . $item . " Error: " . $e->getMessage()
            ], 422);
        }



        // $hasil = (new WSAServices())->wsaTransferSamplingData($item, $lot,$sitefrom,$locto,'SAMPLING',$whfrom,$levelfrom,$binfrom,$qty);
        // $hasil = (new WSAServices())->wsaTransferSamplingData($item, $lot, $sitefrom, $locfrom, 'SAMPLING', $whfrom, $levelfrom, $binfrom, $qty);

        // if ($hasil == 'false') {
        //     log::channel('samplingLog')->info('masuk false transfer');
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Transfer sampling Item Failed for Item : " . $item
        //     ], 422);
        // } else {

    }

    public function getLotPengembalian(Request $req)
    {

        $item = $req->item ?? '';
        $lot = $req->search ?? '';
        $domain = Domain::first();
        $inpdomain = $domain->domain ?? '';
        $records = xxinvDet::where('xxinv_domain', $inpdomain)
            ->where('xxinv_loc', 'QC-QRT')
            ->where('xxinv_part', $item)
            ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
            ->where('xxinv_qty_smp', '>', 0)
            ->select([
                'xxinv_domain as inv_domain',
                'xxinv_lot    as inv_lot',
                'xxinv_site   as inv_site',
            ])
            ->orderBy('xxinv_lot')
            ->get()
            ->unique('inv_lot') // first row per xxinv_lot, same as FIRST-OF
            ->values();
        if ($records->isEmpty()) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
            return response()->json(
                [
                    'DataWSA' => $records
                ],
                200
            );
        }
        // $wsaData = (new WSAServices())->wsaGetLotSampling($item,  $lot, 'SAMPLING');
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

    public function checkWarehouseReturn(Request $req)
    {

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
            ->where('xxinv_qty_smp', '>', 0)
            ->select([
                'xxinv_domain  as inv_domain',
                'xxinv_part    as inv_part',
                'xxinv_lot     as inv_lot',
                'xxinv_wrhfrom     as inv_whfrom',
                'xxinv_levelfrom   as inv_levelfrom',
                'xxinv_binfrom     as inv_binfrom',
                'xxinv_sitefrom    as inv_sitefrom',
                'xxinv_locfrom    as inv_locfrom',
                'xxinv_locto    as inv_locto',
                'xxinv_wrhto     as inv_whto',
                'xxinv_levelto   as inv_levelto',
                'xxinv_binto     as inv_binto',
                'xxinv_siteto    as inv_siteto',
                'xxinv_locto    as inv_locto',
                'xxinv_qtyoh   as inv_qtyoh',
                'xxinv_qty_smp as inv_qtysmp',
                'xxinv_qty_pick as inv_qtypick',
            ])
            ->get()
            ->values();
        if ($records->isEmpty()) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
            return response()->json(
                [
                    'DataWSA' => $records
                ],
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
    public function getApproverSampling(Request $req)
    {
        $approver = User::where('is_active', 'Active')->where('is_approver', 'Yes')->select('name')->orderBy('name')->get()->values();

        if ($approver->isEmpty()) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
            return response()->json(
                [
                    'DataWSA' => $approver
                ],
                200
            );
        }
    }

    public function getSamplingApproval(Request $req)
    {
        $domain = Domain::first();
        $inpdomain = $domain->domain ?? '';
        $records = xxinvDetApproval::where('xxinv_domain', $inpdomain)
            ->where('xxinv_status', 'Waiting')
            // ->where('xxinv_approver', auth()->user()->username ?? '')
            ->select([
                'xxinv_status as status',
                'xxinv_domain  as inv_domain',
                'xxinv_part    as inv_part',
                'xxinv_lot     as inv_lot',
                'xxinv_wrhfrom     as inv_whfrom',
                'xxinv_levelfrom   as inv_levelfrom',
                'xxinv_binfrom     as inv_binfrom',
                'xxinv_sitefrom    as inv_sitefrom',
                'xxinv_locfrom    as inv_locfrom',
                'xxinv_locto    as inv_locto',
                'xxinv_wrhto     as inv_whto',
                'xxinv_levelto   as inv_levelto',
                'xxinv_binto     as inv_binto',
                'xxinv_siteto    as inv_siteto',
                'xxinv_locto    as inv_locto',
                'xxinv_qtyoh   as inv_qtyoh',
                'xxinv_qty_smp as inv_qtysmp',
                'xxinv_qty_pick as inv_qtypick',
                'id'
            ])
            ->get()
            ->values();
        if ($records->isEmpty()) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        } else {
            return response()->json(
                [
                    'DataWSA' => $records
                ],
                200
            );
        }
    }
    public function samplingApprovalResult(Request $req)
    {
        $id = $req->id;
        $status = $req->status;
        $domain = Domain::first();
        $inpdomain = $domain->domain ?? '';
        DB::beginTransaction();
        try {
            if ($status == 'approve') {
                $xxinvApproval = xxinvDetApproval::where('id', $id)->where('xxinv_status', 'Waiting')->first();
                if ($xxinvApproval) {
                    $xxinvApproval->xxinv_status = 'Approved';
                    $xxinvApproval->save();

                    $user = Auth::user()->name;
                    $item = $xxinvApproval->xxinv_part;
                    $lot = $xxinvApproval->xxinv_lot;
                    $siteto = $xxinvApproval->xxinv_siteto;
                    $locto = $xxinvApproval->xxinv_locto;
                    $whfrom = $xxinvApproval->xxinv_wrhfrom;
                    $levelfrom = $xxinvApproval->xxinv_levelfrom;
                    $binfrom = $xxinvApproval->xxinv_binfrom;
                    $qty = $xxinvApproval->xxinv_qty_pick;

                     $xxinvDet = xxinvDet::where('xxinv_domain', $inpdomain)
                        ->where('xxinv_part', $xxinvApproval->xxinv_part)
                        ->where('xxinv_lot', $xxinvApproval->xxinv_lot)
                        ->where('xxinv_site', $xxinvApproval->xxinv_sitefrom)
                        ->where('xxinv_wrh', $xxinvApproval->xxinv_wrhfrom)
                        ->where('xxinv_level', $xxinvApproval->xxinv_levelfrom)
                        ->where('xxinv_bin', $xxinvApproval->xxinv_binfrom)
                        ->first();
                        $wsaData = (new WSAServices())->wsaConfirmSampling($item, $lot, 'QC-QRT', $xxinvApproval->xxinv_qty_smp, $siteto);
                        
                    if ($wsaData[0] == 'false') {
                        DB::rollback();
                        return response()->json([
                            'Status' => 'Error',
                            'Message' => "No Data Available"
                        ], 422);
                    } 


                    // Transaction History
                    $newTransactionHistory = new TransactionHistory();
                    $newTransactionHistory->tr_nbr = 'Sampling';
                    $newTransactionHistory->tr_order = '';
                    $newTransactionHistory->tr_program = 'Sampling Confirm Module';
                    $newTransactionHistory->tr_activity = 'Confirm Sampling From';
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
                    DB::commit();
                    return response()->json([
                        'Status' => 'Success',
                        'Message' => "Sampling Approval Success for Item : " . $xxinvApproval->xxinv_part
                    ], 200);
                } else {
                    DB::rollBack();
                    log::info('samplingApprovalResult: xxinvDetApproval not found for ID: ' . $id);
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Sampling Approval Failed for Item : " . $xxinvApproval->xxinv_part
                    ], 422);
                }
            } else if ($status == 'reject') {
                $xxinvApproval = xxinvDetApproval::where('id', $id)->where('xxinv_status', 'Waiting')->first();
                if ($xxinvApproval) {
                    $xxinvApproval->xxinv_status = 'Rejected';
                    $xxinvApproval->save();
                    $xxinvDet = xxinvDet::where('xxinv_domain', $inpdomain)
                        ->where('xxinv_part', $xxinvApproval->xxinv_part)
                        ->where('xxinv_lot', $xxinvApproval->xxinv_lot)
                        ->where('xxinv_site', $xxinvApproval->xxinv_sitefrom)
                        ->where('xxinv_wrh', $xxinvApproval->xxinv_wrhfrom)
                        ->where('xxinv_level', $xxinvApproval->xxinv_levelfrom)
                        ->where('xxinv_bin', $xxinvApproval->xxinv_binfrom)
                        ->first();
                    if ($xxinvDet) {
                        $qty = $xxinvApproval->xxinv_qty_pick;
                        $xxinvDet->xxinv_qty_smp = $xxinvDet->xxinv_qty_smp + $qty;
                        // $xxinvDet->xxinv_qty_wrh = $xxinvDet->xxinv_qty_wrh - $qty;
                        $xxinvDet->save();

                        $user = Auth::user()->name;
                        $item = $xxinvApproval->xxinv_part;
                        $lot = $xxinvApproval->xxinv_lot;
                        $siteto = $xxinvApproval->xxinv_siteto;
                        $locto = $xxinvApproval->xxinv_locto;
                        $whfrom = $xxinvApproval->xxinv_wrhfrom;
                        $levelfrom = $xxinvApproval->xxinv_levelfrom;
                        $binfrom = $xxinvApproval->xxinv_binfrom;

                        // Transaction History
                        $newTransactionHistory = new TransactionHistory();
                        $newTransactionHistory->tr_nbr = 'Sampling';
                        $newTransactionHistory->tr_order = '';
                        $newTransactionHistory->tr_program = 'Sampling Confirm Module';
                        $newTransactionHistory->tr_activity = 'Reject Sampling From';
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

                        DB::commit();

                        return response()->json([
                            'Status' => 'Success',
                            'Message' => "Sampling Rejection Success for Item : " . $xxinvApproval->xxinv_part
                        ], 200);
                    } else {
                        DB::rollback();
                        log::info('samplingApprovalResult: xxinvDet not found for rejection, Item: ' . $xxinvApproval->xxinv_part . ', Lot: ' . $xxinvApproval->xxinv_lot);
                        return response()->json([
                            'Status' => 'Error',
                            'Message' => "Sampling Rejection Failed for Item : " . $xxinvApproval->xxinv_part
                        ], 422);
                    }
                } else {
                    DB::rollBack();
                    log::info('samplingApprovalResult: xxinvDetApproval not found for ID: ' . $id);
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Sampling Rejection Failed for Item : " . $xxinvApproval->xxinv_part
                    ], 422);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Sampling Approval Failed for Item : " . $xxinvApproval->xxinv_part . " Error: " . $e->getMessage()
            ], 422);
        }
    }
}
