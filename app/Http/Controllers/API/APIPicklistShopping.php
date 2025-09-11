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
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
        }
        return response()->json([
            'DataWSA' => $listData
        ], 200);


        return GeneralResources::collection($data);
    }

    public function getPicklistDet(Request $req)
    {
        $hasil = (new WSAServices())->wsaGetPickDetail("PICK");
        
        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
        }
        foreach ($listData as $key => $value) {
            if ($currentPick != (string)$value->t_pick_nbr) {
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
    public function wsaSendQtyPick(Request $req)
    {
        $data = $req->all();
        $picknbr = $data['data']['picknbr'];
        $site = $data['data']['site'];
        $loc = $data['data']['loc'];
        $wonbrlist = $data['data']['wonbr'];
        foreach ($wonbrlist as $wo) {
            foreach ($wo['detail'] as $det) {
                $wonbr = $wo['wonbr'];
                $wodpart = $det['wodpart'];
                $lot = $det['lot'];
                $wrh = $det['wrh'];
                $level = $det['level'];
                $bin = $det['bin'];
                $qtypick = $det['qtypick'];
                $hasil = (new WSAServices())->wsaUpdateQtyPick($picknbr, $qtypick, $wonbr, $wodpart, $site, $loc, $lot, $wrh, $level, $bin);
                if ($hasil == 'false') {
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart
                    ], 422);
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
        $data = $req->all();
        $picknbr = $req->query('picknbr');
        $status = $req->query('status');

        $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr
            ], 422);
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
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
        }
        foreach ($listData as $key => $value) {
            if ($currentPick != (string)$value->t_pick_nbr) {
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
}
