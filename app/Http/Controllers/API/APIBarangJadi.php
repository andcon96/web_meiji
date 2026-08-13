<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PenyerahanBarang;
use App\Models\API\SingleTransfer;
use App\Models\API\TransactionHistory;
use App\Models\API\xxinvDet;
use App\Models\Settings\Item;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use App\Models\Settings\PenyerahanBarangPrefix;
use App\Models\Settings\SingleTransferPrefix;
use App\Services\QxtendServices;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APIBarangJadi extends Controller
{
    public function getTransferBarangJadi(Request $req)
    {
        $trfid = $req->trfid;
        $trfdata = singleTransfer::where('st_trfid', $trfid)->first();
        if (! $trfdata) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
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

    public function getPenerimaanBarangData(Request $req)
    {

        $search = $req->search;
        $trfdata = penyerahanBarang::where('pb_status', 'Open');
        if ($search) {
            $trfdata = $trfdata->where('pb_trfid', 'LIKE', '%'.$search.'%')
                ->orWhere('pb_item', 'LIKE', '%'.$search.'%')
                ->orWhere('pb_lot', 'LIKE', '%'.$search.'%')
                ->get();
        }
        $trfdata = $trfdata->get();

        if (! $trfdata) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
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

    public function receiptItempb(Request $req)
    {
        $trfid = $req->trfid;
        $locto = $req->locto;
        $whto = $req->whto;
        $levelto = $req->levelto;
        $binto = $req->binto;

        $data = penyerahanBarang::where('pb_trfid', $trfid)->first();

        $part = $data->pb_item;
        $qtyoh = $data->pb_qty;
        $sitefrom = $data->pb_site_from;
        $siteto = $data->pb_site_to;
        $locfrom = $data->pb_loc_from;
        // $locto = $data->pb_loc_to;
        $lotfrom = $data->pb_lot;
        $lotto = $data->pb_lot;
        $buildingfrom = $data->pb_wh_from ?? '';
        // $buildingto = $data->pb_wh_to ?? '';
        $levelfrom = $data->pb_level_from ?? '';
        // $levelto = $data->pb_level_to ?? '';
        $binfrom = $data->pb_bin_from ?? '';
        // $binto = $data->pb_bin_to ?? '';

        try {
            $inv = xxinvDet::where('xxinv_part', $part)->where('xxinv_lot', $lotfrom)->where('xxinv_wrh', $buildingfrom)->where('xxinv_level', $levelfrom)->where('xxinv_bin', $binfrom)->first();
            $inv->xxinv_qtyoh = $qtyoh;
            $inv->save();
            $dataupdate = penyerahanBarang::where('pb_trfid', $trfid)->first();
            $dataupdate->pb_status = 'Received';
            $dataupdate->pb_loc_to = $locto;
            $dataupdate->pb_wh_to = $whto;
            $dataupdate->pb_level_to = $levelto;
            $dataupdate->pb_bin_to = $binto;
            $dataupdate->save();

            $user = Auth::user()->name;
            // Transaction History

            $newTransactionHistoryfrom = new TransactionHistory();
            $newTransactionHistoryfrom->tr_nbr = $trfid;
            $newTransactionHistoryfrom->tr_program = 'Barang Jadi Module';
            $newTransactionHistoryfrom->tr_activity = 'Penerimaan Barang Jadi From';
            $newTransactionHistoryfrom->tr_user = $user ?? '';
            $newTransactionHistoryfrom->tr_part = $part ?? '';
            $newTransactionHistoryfrom->tr_uom = '';
            $newTransactionHistoryfrom->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
            $newTransactionHistoryfrom->tr_lot = $lotfrom ?? '';
            $newTransactionHistoryfrom->tr_qty = $qtyoh ?? '';
            $newTransactionHistoryfrom->tr_date = date('Y-m-d H:i:s');
            $newTransactionHistoryfrom->tr_reference = '';
            $newTransactionHistoryfrom->tr_site = $sitefrom ?? '';
            $newTransactionHistoryfrom->tr_location = $locfrom ?? '';
            $newTransactionHistoryfrom->tr_warehouse = $buildingfrom ?? '';
            $newTransactionHistoryfrom->tr_level = $levelfrom ?? '';
            $newTransactionHistoryfrom->tr_bin = $binfrom ?? '';
            $newTransactionHistoryfrom->tr_remark = '';
            $newTransactionHistoryfrom->save();

            $newTransactionHistory = new TransactionHistory();
            $newTransactionHistory->tr_nbr = $trfid;
            $newTransactionHistory->tr_order = '';
            $newTransactionHistory->tr_program = 'Barang Jadi Module';
            $newTransactionHistory->tr_activity = 'Penerimaan Barang Jadi To';
            $newTransactionHistory->tr_user = $user ?? '';
            $newTransactionHistory->tr_part = $part ?? '';
            $newTransactionHistory->tr_uom = '';
            $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
            $newTransactionHistory->tr_lot = $lotto ?? '';
            $newTransactionHistory->tr_qty = $qtyoh ?? '';
            $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
            $newTransactionHistory->tr_reference = '';
            $newTransactionHistory->tr_site = $siteto ?? '';
            $newTransactionHistory->tr_location = $locto ?? '';
            $newTransactionHistory->tr_warehouse = $buildingto ?? '';
            $newTransactionHistory->tr_level = $levelto ?? '';
            $newTransactionHistory->tr_bin = $binto ?? '';
            $newTransactionHistory->tr_remark = '';
            $newTransactionHistory->save();

            DB::commit();

            return response()->json([
                'Status' => 'Success',
                'Message' => 'Receipt Item Successful',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'Status' => 'Error',
                'Message' => 'Receipt Item Failed :'.$e->getMessage(),
            ], 422);
        }

    }

    public function getPicklistDet(Request $req)
    {
        $statusreq = $req->status;
        $status = str_replace('_', ' ', $statusreq);
        $hasil = (new WSAServices())->wsaGetPickDetail($status);

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {
            $listData = $hasil[1];
        }

        foreach ($listData as $key => $value) {

            $wonbrstring = (string) $value->t_wo_nbr;

            if (strlen($wonbrstring) == 0) {
                $wonbrstring = 'manual';

                if ($currentPick != (string) $value->t_pick_nbr) {
                    $wonbrstring = 'manual';
                    $currentWo = '';

                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string) $value->t_pick_nbr;

                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];

                        $wonbr[$currentWo] = [
                            'wonbrnbr' => $wonbrstring,
                            'wopart' => '',
                            'detail' => $detail,
                        ];

                        $master[$currentPick] = [
                            'picknbr' => (string) $value->t_pick_nbr,
                            'site' => (string) $value->t_site,
                            'status' => (string) $value->t_status,
                            'loc' => (string) $value->t_loc,
                            'wonbr' => $wonbr,
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                    }
                } else {
                    $wonbrstring = 'manual';
                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbrnbr' => $currentWo,
                            'wopart' => '',
                            'woid' => '',
                            'detail' => $detail,
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string) $value->t_pick_nbr,
                            'site' => (string) $value->t_site,
                            'status' => (string) $value->t_status,
                            'loc' => (string) $value->t_loc,
                            'wonbr' => $wonbr,
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                    }
                }
            } else {

                if ($currentPick != (string) $value->t_pick_nbr) {

                    $currentWo = '';
                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string) $value->t_pick_nbr;

                    if ($currentWo != (string) $value->t_wo_nbr) {
                        $currentWo = (string) $value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbrnbr' => (string) $value->t_wo_nbr,
                            'wopart' => (string) $value->t_wo_part,
                            'woid' => (string) $value->t_wo_id,
                            'detail' => $detail,
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string) $value->t_pick_nbr,
                            'site' => (string) $value->t_site,
                            'status' => (string) $value->t_status,
                            'loc' => (string) $value->t_loc,
                            'wonbr' => $wonbr,
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                    }
                } else {
                    if ($currentWo != (string) $value->t_wo_nbr) {

                        $currentWo = (string) $value->t_wo_nbr;

                        $wonbr = [];
                        $detail = [];

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];

                        // $wonbr[$currentWo] = [
                        //     'wonbrnbr' => (string)$value->t_wo_nbr,
                        //     'wopart' => (string)$value->t_wo_part,
                        //     'detail' => $detail
                        // ];

                        $master[$currentPick]['wonbr'][$currentWo] = [
                            'wonbrnbr' => (string) $value->t_wo_nbr,
                            'wopart' => (string) $value->t_wo_part,
                            'detail' => $detail,
                        ];
                    } else {

                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                        // dd($master[$currentPick]['wonbr'][$currentWo]['detail'],$hasil[1],$currentWo);
                    }
                }
            }
        }

        return response()->json(
            [
                'DataWSA' => $master,
            ],
            200
        );

        return GeneralResources::collection($data);
    }

    public function wsaSendQtyPick(Request $req)
    {
        $data = $req->all();
        $picknbr = $data['data']['picknbr'];
        $site = $data['data']['site'];
        $loc = $data['data']['loc'];
        $wonbrlist = $data['data']['wonbr'];
        foreach ($wonbrlist as $wo) {
            foreach ($wo['detail'] as $det) {
                if ($wo['wonbrnbr'] == 'manual') {
                    $wonbr = '';
                } else {
                    $wonbr = $wo['wonbrnbr'];
                }

                $wodpart = $det['wodpart'];
                $lot = $det['lot'];
                $wrh = $det['wrh'];
                $level = $det['level'];
                $bin = $det['bin'];
                $qtypick = $det['qtypick'];
                $qxtendsingleitem = (new QxtendServices())->qxTransferSingleItemWo($wodpart, $wonbr, $site, $site, $loc, 'Shopping', $qtypick, $bin, $level, $wrh, $lot);
                if ($qxtendsingleitem == 'false') {
                    Log::channel('Picklist')->info('Transfer Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart);

                    return response()->json([
                        'Status' => 'Error',
                        'Message' => 'Transfer Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart,
                        //'Message'=> $qxtendsingleitem[1];
                    ], 422);
                } else {
                    $hasil = (new WSAServices())->wsaUpdateQtyPick($picknbr, $qtypick, $wonbr, $wodpart, $site, $loc, $lot, $wrh, $level, $bin);
                    if ($hasil == 'false') {
                        Log::channel('Picklist')->info('Update Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart);

                        return response()->json([
                            'Status' => 'Error',
                            'Message' => 'Update Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart,
                        ], 422);
                    }
                }
            }
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => 'Update Qty Pick Success',
        ], 200);
    }

    public function getLocationBarangJadi(Request $req)
    {

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        // $wonbr = $req->wonbr;
        $wonbr = '';
        $item = $req->item;
        $site = $req->site;

        $hasil = (new WSAServices())->wsaGetLocationTransfer($wonbr, $item, $site);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
    }

    public function getSiteBarangJadi(Request $req)
    {

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        // $wonbr = $req->wonbr;
        $wonbr = '';
        $site = $req->site ?? '';
        $item = $req->item ?? '';
        $location = $req->location ?? '';
        $hasil = (new WSAServices())->wsaGetSiteTransfer($site, $item, $location);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
    }

    public function wsaWarehouseBarangJadi(Request $req)
    {
        $wsaData = Cache::remember('wsaWarehouse', 60, function () {
            return (new WSAServices())->wsaGenCode('mji_wrh');
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No Data Available',
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsainvdetBarangJadi(Request $req)
    {
        $loc = $req->location;
        $site = $req->site;
        $wrh = $req->wrh;
        $item = $req->item;

        $wsaData = (new WSAServices())->wsaGetInvDet($site, $loc, $wrh, $item);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No Data Available',
            ], 422);
        } else {
            $listData = $wsaData[1];

            return response()->json(['DataWSA' => $listData], 200);
        }

        // return response()->json($wsaData[1]);
    }

    public function nullConversion($data)
    {
        if ($data == null || strtolower($data) == 'null') {
            return '';
        } else {
            return $data;
        }
    }

    // public function sendBarangJadi(Request $req)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $data = $req->all();
    //         $item = $data['item'];
    //         $sitefrom = $data['sitefrom'];
    //         $siteto = $this->nullConversion($data['siteto']);
    //         $locfrom = $data['locfrom'];
    //         $locto = $this->nullConversion($data['locto']);
    //         $whfrom = $this->nullConversion($data['whfrom']);
    //         $levelfrom = $this->nullConversion($data['levelfrom']);
    //         $binfrom = $this->nullConversion($data['binfrom']);
    //         $qty = $data['qty'];
    //         $wh = $this->nullConversion($data['wh']);
    //         $ref = $this->nullConversion($data['ref']);
    //         $level = $this->nullConversion($data['level']);
    //         $bin = $this->nullConversion($data['bin']);
    //         $lot = $this->nullConversion($data['lot']);
    //         $prefixTable = singleTransferPrefix::first();
    //         $prefix = $prefixTable->stp_prefix;
    //         $runningnbr = $prefixTable->stp_running_nbr;
    //         $nextrunningnbr = (int) $runningnbr + 1;
    //         $newRunningNbr = str_pad($nextrunningnbr, 6, '0', STR_PAD_LEFT);
    //         $newPrefix = $prefix . $newRunningNbr;

    //         $newTransferData = new SingleTransfer();
    //         $newTransferData->st_trfid = $newPrefix;
    //         $newTransferData->st_item = $item;
    //         $newTransferData->st_site_from = $sitefrom;
    //         $newTransferData->st_site_to = $siteto;
    //         $newTransferData->st_loc_from = $locfrom;
    //         $newTransferData->st_loc_to = $locto;
    //         $newTransferData->st_wh_from = $whfrom;
    //         $newTransferData->st_level_from = $levelfrom;
    //         $newTransferData->st_bin_from = $binfrom;
    //         $newTransferData->st_qty = $qty;
    //         $newTransferData->st_wh = $wh;
    //         $newTransferData->st_ref = $ref;
    //         $newTransferData->st_level = $level;
    //         $newTransferData->st_bin = $bin;
    //         $newTransferData->st_lot = $lot;
    //         $newTransferData->st_status = 'Open';
    //         $newTransferData->save();

    //         $prefixTable->stp_running_nbr = $newRunningNbr;
    //         $prefixTable->save();

    //         DB::commit();

    //         return response()->json([
    //             'Status' => 'Success',
    //             'Message' => "Transfer Item Success for Item : " . $item
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::channel('SingleTransfer')->info($e);
    //         return response()->json([
    //             'Status' => 'Error',
    //             'Message' => $e->getMessage()
    //         ], 422);
    //     }

    // }

    public function getWlbBarangJadi(Request $req)
    {
        //$part = $req->part ?? '';
        // $site = $req->site ?? '';
        // $lot = $req->lot ?? '';
        $lot = '';
        $loc = $req->loc ?? '';
        $site = '';
        $part = '';
        $wrh = $req->wrh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';

        $hasil = (new WSAServices())->wsaGetWlb($part, $lot, $site, $loc, $wrh, $level, $bin);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {

            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
    }

    public function getWebLocationDataTransfer(Request $req)
    {
        $warehouse = $req->wh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $site = $req->site ?? '';
        $loc = $req->location ?? '';
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        if (! $location) {
            return collect();
        } // 1
        $locationdetail = LocationDetail::query()->where('ld_location_id', $location->id);
        if ($warehouse != '') {
            $locationdetail->where('ld_building', '=', $warehouse);
        }
        if ($level != '') {
            $locationdetail->where('ld_rak', '=', $level);
        }
        if ($bin != '') {
            $locationdetail->where('ld_bin', '=', $bin);
        }
        // $locationdetail = $locationdetail->select('id')->toArray();
        $locationdetail = $locationdetail->pluck('id')->toArray();

        $itemQuery = Item::with('getItemLocation.getLocationDetail')->where('im_item_part', $item)->select('id')->first();

        if (! $itemQuery) {
            return collect();
        }
        $arrayloc = [];
        $stringloc = '';
        foreach ($locationdetail as $locdetail) {
            $stringloc .= $locdetail.',';
        }
        // dd($stringloc, $itemQuery->id);
        $getAllItemLocation = ItemLocation::with(['getLocationDetail' => function ($query) {
            $query->orderBy('ld_building');
        }])
            ->where('il_item_id', $itemQuery->id)
            ->whereIn('il_ld_id', $locationdetail)
            ->get();
        // foreach($getAllItemLocation as $key => $value){
        //     dump($value->getLocationDetail->ld_rak);
        // }
        // dd('a');

        if (count($getAllItemLocation) == 0) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No Data Available',
            ], 422);
        }
        $hasil = (new WSAServices())->wsaGetWlb($item, $lot, $site, $loc, $warehouse, $level, $bin);
        // if ($hasil[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Data Not Found."
        //     ], 422);
        // }

        if ($hasil[0] == 'true') {
            $wsaData = collect($hasil[1]);

            // Add qty to each locationDetail
            $getAllItemLocation->transform(function ($location) use ($wsaData) {
                // Match based on location detail properties
                $matchingWsa = $wsaData
                    ->where('t_wrh', $location->getLocationDetail->ld_building)
                    ->where('t_level', $location->getLocationDetail->ld_rak)
                    ->where('t_bin', $location->getLocationDetail->ld_bin)
                    ->first();

                // Add qty to getLocationDetail
                $location->getLocationDetail->qty = $matchingWsa['t_qtyoh'] ?? 0;

                return $location;
            });
        }

        return response()->json($getAllItemLocation);
    }

    public function sendBarangJadi(Request $req)
    {
        DB::beginTransaction();

        try {
            $data = $req->all();

            $item = $data['item'];
            $sitefrom = $data['sitefrom'];
            $siteto = $this->nullConversion($data['siteto']);
            $locfrom = $data['locfrom'];
            $remark = $this->nullConversion($data['remark']);
            $locto = $this->nullConversion($data['locto']);
            $whfrom = $this->nullConversion($data['whfrom']);
            $levelfrom = $this->nullConversion($data['levelfrom']);
            $binfrom = $this->nullConversion($data['binfrom']);
            $qty = $data['qty'];
            $wh = $this->nullConversion($data['wh']);
            $ref = $this->nullConversion($data['ref']);
            $level = $this->nullConversion($data['level']);
            $bin = $this->nullConversion($data['bin']);
            $lot = $this->nullConversion($data['lot']);

            /*
            |--------------------------------------------------------------------------
            | Ambil prefix + lock row
            |--------------------------------------------------------------------------
            | lockForUpdate() mencegah 2 request mendapatkan running number
            | yang sama ketika diproses bersamaan.
            */
            $prefixTable = penyerahanBarangPrefix::lockForUpdate()->first();

            if ($prefixTable) {

                $prefix = $prefixTable->pbp_prefix;
                $runningnbr = (int) $prefixTable->pbp_running_nbr;

            } else {

                $prefix = 'PB';
                $runningnbr = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Generate running number berikutnya
            |--------------------------------------------------------------------------
            */
            $nextrunningnbr = $runningnbr + 1;

            $newRunningNbr = str_pad(
                $nextrunningnbr,
                6,
                '0',
                STR_PAD_LEFT
            );

            $newPrefix = $prefix.$newRunningNbr;

            /*
            |--------------------------------------------------------------------------
            | Simpan data penyerahan barang
            |--------------------------------------------------------------------------
            */
            $newPenyerahanBarang = new PenyerahanBarang();

            $newPenyerahanBarang->pb_trfid = $newPrefix;
            $newPenyerahanBarang->pb_item = $item;
            $newPenyerahanBarang->pb_site_from = $sitefrom;
            $newPenyerahanBarang->pb_site_to = $siteto;
            $newPenyerahanBarang->pb_loc_from = $locfrom;
            $newPenyerahanBarang->pb_loc_to = $locto;
            $newPenyerahanBarang->pb_wh_from = $whfrom;
            $newPenyerahanBarang->pb_remark = $remark;
            $newPenyerahanBarang->pb_qty = $qty;
            $newPenyerahanBarang->pb_wh_to = $wh;
            $newPenyerahanBarang->pb_ref = $ref;
            $newPenyerahanBarang->pb_level_from = $levelfrom;
            $newPenyerahanBarang->pb_level_to = $level;
            $newPenyerahanBarang->pb_bin_from = $binfrom;
            $newPenyerahanBarang->pb_bin_to = $bin;
            $newPenyerahanBarang->pb_lot = $lot;
            $newPenyerahanBarang->pb_status = 'Open';

            $newPenyerahanBarang->save();

            /*
            |--------------------------------------------------------------------------
            | Update / insert running number
            |--------------------------------------------------------------------------
            */
            if ($prefixTable) {

                // Record prefix sudah ada → UPDATE
                $prefixTable->pbp_running_nbr = $nextrunningnbr;
                $prefixTable->save();

            } else {

                // Record prefix belum ada → INSERT
                $insertprefix = new penyerahanBarangPrefix();

                $insertprefix->pbp_prefix = $prefix;
                $insertprefix->pbp_running_nbr = $nextrunningnbr;

                $insertprefix->save();
            }

            /*
            |--------------------------------------------------------------------------
            | Commit transaction
            |--------------------------------------------------------------------------
            */
            DB::commit();

            return response()->json([
                'Status' => 'Success',
                'Message' => 'Transfer Item Success for Item : '.$item,
                'trfid' => $newPrefix,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::channel('SingleTransfer')->info($e);

            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getItemXxinvDet(Request $request)
    {
        $items = xxinvDet::orderBy('xxinv_part')
            ->get();

        return response()->json(
            [
                'items' => $items,
            ],
            200,
        );
    }

    public function getPenyerahanBarang(Request $request)
    {
        $items = PenyerahanBarang::get();

        return response()->json(
            [
                'items' => $items,
            ],
            200,
        );
    }

    public function index(Request $request)
    {
        $data = PenyerahanBarang::query()
            ->leftJoin('item_master', 'item_master.im_item_part', '=', 'penyerahan_barang.pb_item')
            ->where('pb_status', 'open')
            ->select(
                'penyerahan_barang.*',
                'item_master.im_item_desc'
            );

        if ($request->search) {
            $search = $request->search;

            $data->where(function ($q) use ($search) {
                $q->where('penyerahan_barang.pb_item', 'LIKE', "%{$search}%")
                    ->orWhere('penyerahan_barang.pb_trfid', 'LIKE', "%{$search}%")
                    ->orWhere('penyerahan_barang.pb_lot', 'LIKE', "%{$search}%");
            });
        }

        $data = $data->orderBy('penyerahan_barang.id', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function update(Request $req)
    {
        DB::beginTransaction();
        try {
            $data = $req->all();

            $trfid = $data['trfid'] ?? null;

            if (! $trfid) {
                DB::rollBack();

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'trfid wajib dikirim untuk update data',
                ], 422);
            }

            $penyerahanBarang = PenyerahanBarang::where('pb_trfid', $trfid)->first();

            if (! $penyerahanBarang) {
                DB::rollBack();

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Data dengan trfid '.$trfid.' tidak ditemukan',
                ], 404);
            }

            $qty = $data['qty'] ?? $penyerahanBarang->pb_qty;
            $remark = $this->nullConversion($data['remark'] ?? null);

            $penyerahanBarang->pb_qty = $qty;
            $penyerahanBarang->pb_remark = $remark;
            $penyerahanBarang->save();

            DB::commit();

            return response()->json([
                'Status' => 'Success',
                'Message' => 'Transfer Item Updated Success for Item : '.$penyerahanBarang->pb_item,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('SingleTransfer')->info($e);

            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage(),
            ], 422);
        }
    }
}
