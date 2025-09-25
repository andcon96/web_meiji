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

class APIPicklistShopping extends Controller
{
    public function getPicklistMstr(Request $req)
    {


        $hasil = (new WSAServices())->wsaGetPickNbr();


        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
        }

        foreach ($listData as $key => $value) {


            $wonbrstring = (string)$value->t_wo_nbr;

            if (strlen($wonbrstring) == 0) {
                $wonbrstring = 'manual';

                if ($currentPick != (string)$value->t_pick_nbr) {
                    $wonbrstring = 'manual';
                    $currentWo = '';

                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string)$value->t_pick_nbr;

                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;


                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];

                        $wonbr[$currentWo] = [
                            'wonbr' => $wonbrstring,
                            'wopart' => '',
                            'detail' => $detail
                        ];

                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                } else {
                    $wonbrstring = 'manual';
                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => $currentWo,
                            'wopart' => '',
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                }
            } else {

                if ($currentPick != (string)$value->t_pick_nbr) {

                    $currentWo = '';
                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string)$value->t_pick_nbr;

                    if ($currentWo != (string)$value->t_wo_nbr) {
                        $currentWo = (string)$value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                } else {
                    if ($currentWo != (string)$value->t_wo_nbr) {
                        $currentWo = (string)$value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                }
            }
        }

        return response()->json(
            [
                'DataWSA' => $master
            ],
            200
        );



        return GeneralResources::collection($data);
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
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
        }

        foreach ($listData as $key => $value) {


            $wonbrstring = (string)$value->t_wo_nbr;

            if (strlen($wonbrstring) == 0) {
                $wonbrstring = 'manual';

                if ($currentPick != (string)$value->t_pick_nbr) {
                    $wonbrstring = 'manual';
                    $currentWo = '';

                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string)$value->t_pick_nbr;

                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;


                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];

                        $wonbr[$currentWo] = [
                            'wonbr' => $wonbrstring,
                            'wopart' => '',
                            'detail' => $detail
                        ];

                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                } else {
                    $wonbrstring = 'manual';
                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => $currentWo,
                            'wopart' => '',
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                }
            } else {
                if ($currentPick != (string)$value->t_pick_nbr) {

                    $currentWo = '';
                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string)$value->t_pick_nbr;

                    if ($currentWo != (string)$value->t_wo_nbr) {
                        $currentWo = (string)$value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                } else {
                    if ($currentWo != (string)$value->t_wo_nbr) {
                        $currentWo = (string)$value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                }
            }
        }

        return response()->json(
            [
                'DataWSA' => $master
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
                if ($wo['wonbr'] == 'manual') {
                    $wonbr = '';
                } else {
                    $wonbr = $wo['wonbr'];
                }

                $wodpart = $det['wodpart'];
                $lot = $det['lot'];
                $wrh = $det['wrh'];
                $level = $det['level'];
                $bin = $det['bin'];
                $qtypick = $det['qtypick'];
                $qxtendsingleitem = (new QxtendServices())->qxTransferSingleItemWo($wodpart, $wonbr, $site, $site, $loc, 'Shopping', $qtypick, $bin, $level, $wrh, $lot);
                if ($qxtendsingleitem == 'false') {
                       Log::channel('Picklist')->info("Transfer Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart);
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Transfer Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart
                        //'Message'=> $qxtendsingleitem[1];
                    ], 422);
                } else {
                    $hasil = (new WSAServices())->wsaUpdateQtyPick($picknbr, $qtypick, $wonbr, $wodpart, $site, $loc, $lot, $wrh, $level, $bin);
                    if ($hasil == 'false') {
                        Log::channel('Picklist')->info("Update Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart);
                        return response()->json([
                            'Status' => 'Error',
                            'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart
                        ], 422);
                    }
                }
            }
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => "Update Qty Pick Success"
        ], 200);
    }
    public function wsaUpdateStatusPick(Request $req)
    {
        //$data = $req->all();
        $picknbr = $req->query('picknbr');
        $status = $req->query('status');

        $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr
            ], 422);
        } else {
        }


        return response()->json([
            'Status' => 'Success',
            'Message' => "Update Qty Pick Success"
        ], 200);
    }

    public function getPicklistDetAppr(Request $req)
    {
        $hasil = (new WSAServices())->wsaGetPickDetail("waiting for Approval");

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Picklist : " . $req->search . " Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
        }
        foreach ($listData as $key => $value) {
            if ($currentPick != (string)$value->t_pick_nbr) {
                $detail = [];
                $wonbr = [];
                $currentWo = '';
                $currentPick = (string)$value->t_pick_nbr;
                if ($currentWo != (string)$value->t_wo_nbr) {
                    $currentWo = (string)$value->t_wo_nbr;

                    $detail[] = [
                        'wodpart' => (string)$value->t_wod_part,
                        'qtyreq' => (string)$value->t_qty_req,
                        'qtypick' => (string)$value->t_qty_pick,
                        'lot' => (string)$value->t_lot,
                        'id' => (string)$value->t_wo_id,
                        'wrh' => (string)$value->t_wrh,
                        'level' => (string)$value->t_level,
                        'bin' => (string)$value->t_bin,
                        'dd' => (string)$value->t_duedate,
                        'od' => (string)$value->t_orddate,
                        'rd' => (string)$value->t_reldate,
                    ];
                    $wonbr[$currentWo] = [
                        'wonbr' => (string)$value->t_wo_nbr,
                        'wopart' => (string)$value->t_wo_part,
                        'detail' => $detail
                    ];
                    $master[$currentPick] = [
                        'picknbr' => (string)$value->t_pick_nbr,
                        'site' => (string)$value->t_site,
                        'status' => (string)$value->t_status,
                        'loc' => (string)$value->t_loc,
                        'wonbr' => $wonbr
                    ];
                } else {
                    $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                        'wodpart' => (string)$value->t_wod_part,
                        'qtyreq' => (string)$value->t_qty_req,
                        'qtypick' => (string)$value->t_qty_pick,
                        'lot' => (string)$value->t_lot,
                        'id' => (string)$value->t_wo_id,
                        'wrh' => (string)$value->t_wrh,
                        'level' => (string)$value->t_level,
                        'bin' => (string)$value->t_bin,
                        'dd' => (string)$value->t_duedate,
                        'od' => (string)$value->t_orddate,
                        'rd' => (string)$value->t_reldate,
                    ];
                }
            } else {
                if ($currentWo != (string)$value->t_wo_nbr) {
                    $currentWo = (string)$value->t_wo_nbr;

                    $detail[] = [
                        'wodpart' => (string)$value->t_wod_part,
                        'qtyreq' => (string)$value->t_qty_req,
                        'qtypick' => (string)$value->t_qty_pick,
                        'lot' => (string)$value->t_lot,
                        'id' => (string)$value->t_wo_id,
                        'wrh' => (string)$value->t_wrh,
                        'level' => (string)$value->t_level,
                        'bin' => (string)$value->t_bin,
                        'dd' => (string)$value->t_duedate,
                        'od' => (string)$value->t_orddate,
                        'rd' => (string)$value->t_reldate,
                    ];
                    $wonbr[$currentWo] = [
                        'wonbr' => (string)$value->t_wo_nbr,
                        'wopart' => (string)$value->t_wo_part,
                        'detail' => $detail
                    ];
                    $master[$currentPick] = [
                        'picknbr' => (string)$value->t_pick_nbr,
                        'site' => (string)$value->t_site,
                        'status' => (string)$value->t_status,
                        'loc' => (string)$value->t_loc,
                        'wonbr' => $wonbr
                    ];
                } else {
                    $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                        'wodpart' => (string)$value->t_wod_part,
                        'qtyreq' => (string)$value->t_qty_req,
                        'qtypick' => (string)$value->t_qty_pick,
                        'lot' => (string)$value->t_lot,
                        'id' => (string)$value->t_wo_id,
                        'wrh' => (string)$value->t_wrh,
                        'level' => (string)$value->t_level,
                        'bin' => (string)$value->t_bin,
                        'dd' => (string)$value->t_duedate,
                        'od' => (string)$value->t_orddate,
                        'rd' => (string)$value->t_reldate,
                    ];
                }
                /*
                 $master[$currentPick]['detail'][] = [
                    'wonbr' => (string)$value->t_wo_nbr,
                    'wopart' => (string)$value->t_wo_part,
                    'wodpart' => (string)$value->t_wod_part,
                    'qtyreq' => (string)$value->t_qty_req,
                    'qtypick' => (string)$value->t_qty_pick,
                    'lot' => (string)$value->t_lot,
                    'id' => (string)$value->t_wo_id,
                    'wrh' => (string)$value->t_wrh,
                    'level' => (string)$value->t_level,
                    'bin' => (string)$value->t_bin,
                    'dd' => (string)$value->t_duedate,
                    'od' => (string)$value->t_orddate,
                    'rd' => (string)$value->t_reldate,

                ];
                */
            }
        }
        return response()->json(
            [
                'DataWSA' => $master
            ],
            200
        );



        return GeneralResources::collection($data);
    }

    public function getLocationTo(Request $req)
    {
        $data = [];

        $hasil = (new WSAServices())->wsaGetLocationPick($req->site);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
            foreach ($listData as $key => $value) {
                $data[] = (string)$value->t_loc;
            }
        }

        return response()->json(
            [
                'DataWSA' => $data
            ],
            200
        );
    }
    public function submitPicklistTransfer(Request $req)
    {
        $locto = $req->loc;
        $picknbr = $req->picknbr;
        $status = $req->status;

        $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr
            ], 422);
        } else {
            try {
                DB::beginTransaction();
                $hasil = PicklistLocationTo::firstOrNew(
                    ['picklist_number' => $picknbr],

                );
                $hasil->location_to = $locto;

                $hasil->save();
                DB::commit();
                return response()->json(
                    'success',
                    200
                );
            } catch (\Exception $e) {
                DB::rollBack();
                Log::channel('Picklist')->info($e);
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Picklist Transfer Input Error"
                ], 422);
            }
        }
    }

    public function submitPicklistReceipt(Request $req)
    {

        $data = $req->data;
        $picknbr = $req->picknbr;
        $status = $req->status;

        $picknbr = $data['picknbr'];
        $site = $data['site'];

        $loc = $data['loc'];

        if ($status == 'Receipt') {
            $pickloctodata = PicklistLocationTo::where('picklist_number', $picknbr)->first();
            if ($pickloctodata == null) {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Location To for Picklist : " . $picknbr . " Not Found. Please do Transfer Process First."
                ], 422);
            }
            $picklocto = $pickloctodata->location_to;

            $wonbr = $data['wonbr'];
            foreach ($wonbr as $wo) {
                foreach ($wo['detail'] as $det) {
                    if ($wo['wonbr'] == 'manual') {
                        $wonbr = '';
                    } else {
                        $wonbr = $wo['wonbr'];
                    }
                    $wodpart = $det['wodpart'];
                    $lot = $det['lot'];
                    $wrh = $det['wrh'];
                    $level = $det['level'];
                    $bin = $det['bin'];
                    $qtypick = $det['qtypick'];
                    $qxtendsingleitem = (new QxtendServices())->qxTransferSingleItemWo($wodpart, $wonbr, $site, $site, $loc, $picklocto, $qtypick, '', '', '', $lot);
                    if ($qxtendsingleitem == 'false') {
                        return response()->json([
                            'Status' => 'Error',
                            'Message' => "Transfer Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart
                        ], 422);
                    } /*else {
                    $hasil = (new WSAServices())->wsaUpdateQtyPick($picknbr, $qtypick, $wonbr, $wodpart, $site, $loc, $lot, $wrh, $level, $bin);
                    if ($hasil == 'false') {
                        return response()->json([
                            'Status' => 'Error',
                            'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart
                        ], 422);
                    }
                }*/
                }
            }

            $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status);
            if ($hasil[0] == 'false') {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Receipt Picklist Failed for Picklist : " . $picknbr
                ], 422);
            } else {

                return response()->json(
                    'success',
                    200
                );
            }
        } else if ($status == 'Deny') {

            //return to previous status
            $status = 'Approve';
            $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status);
            if ($hasil[0] == 'false') {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Deny Picklist Failed for Picklist : " . $picknbr
                ], 422);
            } else {
                return response()->json(
                    'success',
                    200
                );
            }
        }
    }
     public function getLocationData(Request $req)
    {

        

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        $wonbr = $req->wonbr;
        $item = $req->item;
        
        $hasil = (new WSAServices())->wsaGetLocationTransfer($wonbr,$item);
        
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
            return response()->json(['DataWSA' => $listData],200);
        }
     

        foreach ($listData as $key => $value) {


            $wonbrstring = (string)$value->t_wo_nbr;

            if (strlen($wonbrstring) == 0) {
                $wonbrstring = 'manual';

                if ($currentPick != (string)$value->t_pick_nbr) {
                    $wonbrstring = 'manual';
                    $currentWo = '';

                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string)$value->t_pick_nbr;

                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;


                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];

                        $wonbr[$currentWo] = [
                            'wonbr' => $wonbrstring,
                            'wopart' => '',
                            'detail' => $detail
                        ];

                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                } else {
                    $wonbrstring = 'manual';
                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => $currentWo,
                            'wopart' => '',
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                }
            } else {

                if ($currentPick != (string)$value->t_pick_nbr) {

                    $currentWo = '';
                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string)$value->t_pick_nbr;

                    if ($currentWo != (string)$value->t_wo_nbr) {
                        $currentWo = (string)$value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                } else {
                    if ($currentWo != (string)$value->t_wo_nbr) {
                        $currentWo = (string)$value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,
                            'loc' => (string)$value->t_loc,
                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                        ];
                    }
                }
            }
        }

        return response()->json(
            [
                'DataWSA' => $master
            ],
            200
        );



        return GeneralResources::collection($data);
    }

    public function wsaWarehouse(Request $req)
    {
        $wsaData = Cache::remember('wsaWarehouse', 60, function () {
            return (new WSAServices())->wsaGenCode('mji_wrh');
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsainvdet(Request $req)
    {
        $loc = $req->location;
        $site = $req->site;
        $wrh = $req->wrh;
    
        $wsaData = (new WSAServices())->wsaGetInvDet($site,  $loc, $wrh);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }
        public function nullConversion($data){
        if ($data == null || strtolower($data) == 'null'){
            return '';
        } else {
            return $data;
        }
    }

    public function sendTransferItem(Request $req)
    {
        $data = $req->all();
        $item = $data['item'];
        $sitefrom = $data['sitefrom'];
        $siteto = $data['siteto'];
        $locfrom = $data['locfrom'];
        $locto = $data['locto'];
        $qty = $data['qty'];
        $wh = $this->nullConversion($data['wh']);
        $ref = $this->nullConversion($data['ref']);
        $level = $this->nullConversion($data['level']);
        $bin = $this->nullConversion($data['bin']);
        $lot = $this->nullConversion($data['lot']) ;
        $hasil = (new QxtendServices())->qxTransferSingleItemTransfer($item, $qty, $sitefrom, $siteto, $locfrom, $locto, $lot, '', '', $wh, '', $level, '', $bin);
        if ($hasil == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Transfer Item Failed for Item : " . $item
            ], 422);
        } else {
            return response()->json([
                'Status' => 'Success',
                'Message' => "Transfer Item Success for Item : " . $item
            ], 200);
        }
        return response()->json([
            $item,$sitefrom,$siteto,$locfrom,$locto,$qty,$wh,$ref,$level,$bin,$lot
        ]);

    }


}
