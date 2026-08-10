<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\LocationDetail;
use App\Models\Settings\User;
use App\Models\API\workOrderMaster;
use App\Models\API\workOrderDetail;
use App\Models\API\picklistMstr;
use App\Models\API\picklistWo;
use App\Models\API\picklistWoDet;
use App\Models\API\PicklistShopping;
use App\Models\API\TransactionHistory;
use App\Models\API\xxinvDet;
use App\Models\API\xxinvDetApproval;
use App\Models\Settings\Domain;
use App\Models\API\prefixWorkOrder;
use App\Models\API\picklistHistory;
use App\Models\API\picklistLocationTo;
use App\Services\WSAServices;
use App\Services\QxtendServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\ReceiptServices;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

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
                            'qtytopick' => (string)$value->t_qty_topick,

                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
                        ];

                        $wonbr[$currentWo] = [
                            'wonbrnbr' => $wonbrstring,
                            'wopart' => '',
                            'woid' => (string)$value->t_wo_id,
                            'detail' => $detail
                        ];

                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,

                            'wonbr' => $wonbr,
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,

                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbrnbr' => $currentWo,
                            'wopart' => '',
                            'woid' => (string)$value->t_wo_id,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,

                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,

                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbrnbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'woid' => (string)$value->t_wo_id,
                            'detail' => $detail
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string)$value->t_pick_nbr,
                            'site' => (string)$value->t_site,
                            'status' => (string)$value->t_status,

                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,

                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,

                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
                        ];
                    }
                } else {
                    if ($currentWo != (string)$value->t_wo_nbr) {

                        $currentWo = (string)$value->t_wo_nbr;

                        $wonbr = [];
                        $detail = [];

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,

                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
                        ];

                        // $wonbr[$currentWo] = [
                        //     'wonbrnbr' => (string)$value->t_wo_nbr,
                        //     'wopart' => (string)$value->t_wo_part,
                        //     'detail' => $detail
                        // ];

                        $master[$currentPick]['wonbr'][$currentWo] = [
                            'wonbrnbr' => (string)$value->t_wo_nbr,
                            'wopart' => (string)$value->t_wo_part,
                            'woid' => (string)$value->t_wo_id,
                            'detail' => $detail
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,

                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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

    public function getPicklistBrowse(Request $req)
    {

        $statusreq = $req->status;
        $wonbr = $req->wonbr ?? '';
        $site = $req->site ?? '';
        $lot = $req->lot ?? '';
        // $user = $req->player;
        $status = str_replace('_', ' ', $statusreq);
        // $hasil = (new WSAServices())->wsaGetPickDetail($status);
        // $hasil = (new WSAServices())->wsaGetPickDetail($status, $wonbr, $site, $lot);
        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        // if ($hasil[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Data Not Found."
        //     ], 422);
        // } else {
        //     $listData = $hasil[1];
        // }
        $checkpicklistshopping = PicklistShopping::query();
        // where('ps_status', 'shopping')
        // ->where('ps_approver', $req->player)
        $checkpicklistshopping
            ->when($wonbr != '', fn($query) => $query->where('ps_number', $wonbr));
        $picklistshopping = $checkpicklistshopping->get();

        // dd($checkpicklistshopping);
        if ($picklistshopping) {
            foreach ($picklistshopping as $check) {
                $statusps = $check->ps_status;
                $wonbrps = $check->ps_number;
                $siteps = $check->ps_site ?? '2100';
                $lotps = $check->ps_wo_lot;
                // dd($status,$wonbrps,$siteps,$lotps);
                $item = (string)$check->ps_part ?? '';
                $site = '2100';
                $lot = (string)$check->ps_lot ?? '';
                $warehouse = (string)$check->ps_warehouse ?? '';
                $level = (string)$check->ps_level ?? '';
                $bin = (string)$check->ps_bin ?? '';
                // dd($lot);
                // dump($warehouse,$level,$bin);
                $xxinvdet = xxinvDet::where('xxinv_part', $item)
                    ->where('xxinv_site', $site)
                    ->where('xxinv_lot', $lot)
                    ->where('xxinv_wrh', $warehouse)
                    ->where('xxinv_level', $level)
                    ->where('xxinv_bin', $bin)
                    ->first();

                // dd($xxinvdet);

                // dd($wonbrps,$status,$siteps,$lotps);
                $status = '';
                $hasil = (new WSAServices())->wsaGetPickDetail($status, $wonbrps, $siteps, $lotps);
                if ($hasil[0] == 'false') {
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Data Not Found."
                    ], 422);
                } else {
                    $listData = $hasil[1];
                    // dd($hasil);


                    foreach ($listData as $key => $value) {

                        $currentWo = '';

                        if ($xxinvdet) {
                            if (
                                $xxinvdet->xxinv_loc == $value->t_loc
                                && $xxinvdet->xxinv_lot == $value->t_lot
                                && $xxinvdet->xxinv_part == $value->t_comp
                            ) {
                                // dd((string)$value->t_nbr);

                                if ($currentWo != (string)$value->t_nbr) {
                                    $currentWo = (string)$value->t_nbr;

                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                        'qtyloc' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                    ];


                                    // dd('b');
                                    $detail[] = [
                                        'wodpart' => (string)$value->t_comp ?? '',
                                        'qtyreq' => (string)$value->t_qty_req ?? '',
                                        'qtypick' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                        // (string)$value->t_qty ?? '',
                                        'qtytopick' => (string)$value->t_qty ?? '',
                                        'qtykemasan' => (string)$value->t_qty_kem ?? '',
                                        'lot' => (string)$value->t_lot ?? '',
                                        'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                        'dd' => (string)$value->t_duedate ?? '',
                                        'od' => (string)$value->t_orddate ?? '',
                                        'rd' => (string)$value->t_reldate ?? '',
                                        'ref' => (string)$value->t_ref ?? '',
                                        'um' => (string)$value->t_um ?? '',
                                        'qtyoh' => (string)$value->t_qty_oh ?? '',
                                        'qtytopickkemasan' => (string)$value->t_qty_kem ?? '',
                                        'edfuc' => (string)$value->t_ed_fuc ?? '',
                                        'qtyshp' => (string)$value->t_qty_shp ?? '',
                                        'qtywip' => (string)$value->t_qty_wip ?? '',
                                        'loc' => (string)$value->t_loc ?? '',
                                        'locationlist' => $locationlist
                                    ];
                                    // dd('a');
                                    $wonbr[$currentWo] = [
                                        'wonbrnbr' => (string)$value->t_nbr,
                                        'wopart' => (string)$value->t_part,
                                        'site' => (string)$value->t_site ?? '',
                                        'woid' => (string)$value->t_id,
                                        'detail' => $detail
                                    ];
                                } else {
                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                        'qtyloc' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                    ];
                                    $wonbr[$currentWo]['detail'][] = [
                                        'wodpart' => (string)$value->t_comp,
                                        'qtyreq' => (string)$value->t_qty_req,
                                        'qtypick' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                        // (string)$value->t_qty ?? '',

                                        'qtytopick' => (string)$value->t_qty,
                                        'qtykemasan' => (string)$value->t_qty_kem,
                                        'lot' => (string)$value->t_lot,
                                        'id' => (string)$value->t_id,
                                        'wrh' => (string)$xxinvdet->xxinv_wrh,
                                        'level' => (string)$xxinvdet->xxinv_level,
                                        'bin' => (string)$xxinvdet->xxinv_bin,
                                        'dd' => (string)$value->t_duedate,
                                        'od' => (string)$value->t_orddate,
                                        'rd' => (string)$value->t_reldate,
                                        'ref' => (string)$value->t_ref,
                                        'um' => (string)$value->t_um,
                                        'qtyoh' => (string)$value->t_qty_oh,
                                        'qtytopickkemasan' => (string)$value->t_qty_kem,
                                        'edfuc' => (string)$value->t_ed_fuc,
                                        'qtyshp' => (string)$value->t_qty_shp,
                                        'qtywip' => (string)$value->t_qty_wip,
                                        'loc' => (string)$value->t_loc,
                                        'locationlist' => $locationlist
                                    ];
                                }
                            }
                        }
                    }
                }
            }


            return response()->json(
                [
                    'DataWSA' => $wonbr
                ],
                200
            );
        } else {

            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        }



        return GeneralResources::collection($data);
    }
    public function getPicklistDet(Request $req)
    {

        $statusreq = $req->status;
        $wonbrreq = $req->wonbr ?? '';
        $site = $req->site ?? '';
        $lot = $req->lot ?? '';
        // $user = $req->player;
        $status = str_replace('_', ' ', $statusreq);
        // $hasil = (new WSAServices())->wsaGetPickDetail($status);
        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $currentpart = [];
        $wonbr = [];
        $locationlist  = [];
        $wonbrstring = '';

        $hasil = (new WSAServices())->wsaGetPickDetail($status, $wonbrreq, $site, $lot);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
        }
        foreach ($listData as $key => $value) {
            $item = (string)$value->t_comp ?? '';
            $site = (string)$value->t_site == '' || (string)$value->t_site == null ? '2100' : (string)$value->t_site;
            $lot = (string)$value->t_lot ?? '';

            // // ppicklist shopping
            // $picklist = picklistShopping::query();
            // $picklist->where('ps_approver', Auth::user()->username);
            // $picklist->where('ps_number', (string)$value->t_nbr);
            // $picklist->where('ps_wo_lot', (string)$value->t_id);
            // $picklist->where('ps_part', (string)$value->t_comp);
            // $picklist->where('ps_lot', (string)$value->t_lot);
            // $picklist->where('ps_status', '=', $status);

            // if ($status == 'PICK') {
            //     $picklist->orwhere('ps_status', '=', 'shopping');
            // }

            // $picklistRecord  = $picklist->first();
            // if (!$picklistRecord) {



            //new function
            $wonbrstring = (string)$value->t_wo_nbr;

            // dd($xxinvdet, $item, $site, $lot);
            // dd((string)$value->t_comp);
            // if ($currentPick != (string)$value->t_pick_nbr) {
            // dd($xxinvdet);
            $currentWo = '';


            // $picklistshopping = picklistShopping::where('ps_number', (string)$value->t_nbr)
            //     ->when($status != '', fn($query) => $query->where('ps_status', $status))
            //     // ->where('ps_part', $item)
            //     // ->where('ps_lot', $lot)
            //     ->first();
            // if (!$picklistshopping) {


            if ($currentWo != (string)$value->t_nbr) {
                $currentWo = (string)$value->t_nbr;
                if ($currentpart != $item) {
                    $currentpart = $item;
                    $xxinvdet = xxinvDet::where('xxinv_part', $item)
                        // ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
                        // ->when($site !== '', fn($query) => $query->where('xxinv_site', $site))
                        // ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
                        ->where('xxinv_site', $site)
                        ->where('xxinv_lot', $lot)
                        ->get();
                    if (count($xxinvdet) > 0) {
                        if ($status != 'PICK') {

                            $picklist = picklistShopping::query();
                            $picklist->where('ps_approver', Auth::user()->username);
                            $picklist->where('ps_number', (string)$value->t_nbr);
                            $picklist->where('ps_wo_lot', (string)$value->t_id);
                            $picklist->where('ps_part', (string)$value->t_comp);
                            $picklist->where('ps_lot', (string)$value->t_lot);
                            $picklist->where('ps_status', '=', $status);
                            $picklistRecord  = $picklist->first();

                            if ($picklistRecord) {
                                foreach ($xxinvdet as $xxinvdet) {
                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                    ];
                                }

                                // dd('b');
                                $detail[] = [
                                    'wodpart' => (string)$value->t_comp ?? '',
                                    'qtyreq' => (string)$value->t_qty_req ?? '',
                                    'qtypick' => (string)$value->t_qty ?? '',
                                    'qtytopick' => (string)$value->t_qty ?? '',
                                    'qtykemasan' => (string)$value->t_qty_kem ?? '',
                                    'lot' => (string)$value->t_lot ?? '',
                                    'id' => (string)$value->t_id ?? '',
                                    // 'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                    // 'level' => (string)$xxinvdet->xxinv_level ?? '',
                                    // 'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                    'dd' => (string)$value->t_duedate ?? '',
                                    'od' => (string)$value->t_orddate ?? '',
                                    'rd' => (string)$value->t_reldate ?? '',
                                    'ref' => (string)$value->t_ref ?? '',
                                    'um' => (string)$value->t_um ?? '',
                                    'qtyoh' => (string)$value->t_qty_oh ?? '',
                                    'qtytopickkemasan' => (string)$value->t_qty_kem ?? '',
                                    'edfuc' => (string)$value->t_ed_fuc ?? '',
                                    'qtyshp' => (string)$value->t_qty_shp ?? '',
                                    'qtywip' => (string)$value->t_qty_wip ?? '',
                                    'loc' => (string)$value->t_loc ?? '',
                                    'oldloc' => (string)$value->t_loc ?? '',
                                    'locationlist' => $locationlist
                                ];

                                // dd('a');
                                $wonbr[$currentWo] = [
                                    'wonbrnbr' => (string)$value->t_nbr,
                                    'wopart' => (string)$value->t_part,
                                    'site' => (string)$value->t_site ?? '',
                                    'woid' => (string)$value->t_id,
                                    'detail' => $detail
                                ];
                            }
                        } else {
                            $picklist = picklistShopping::query();
                            $picklist->where('ps_approver', Auth::user()->username);
                            $picklist->where('ps_number', (string)$value->t_nbr);
                            $picklist->where('ps_wo_lot', (string)$value->t_id);
                            $picklist->where('ps_part', (string)$value->t_comp);
                            $picklist->where('ps_lot', (string)$value->t_lot);
                            
                            $picklistRecord  = $picklist->first();
                            if (!$picklistRecord) {
                                foreach ($xxinvdet as $xxinvdet) {
                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                    ];
                                }

                                // dd('b');
                                $detail[] = [
                                    'wodpart' => (string)$value->t_comp ?? '',
                                    'qtyreq' => (string)$value->t_qty_req ?? '',
                                    'qtypick' => (string)$value->t_qty ?? '',
                                    'qtytopick' => (string)$value->t_qty ?? '',
                                    'qtykemasan' => (string)$value->t_qty_kem ?? '',
                                    'lot' => (string)$value->t_lot ?? '',
                                    'id' => (string)$value->t_id ?? '',
                                    // 'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                    // 'level' => (string)$xxinvdet->xxinv_level ?? '',
                                    // 'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                    'dd' => (string)$value->t_duedate ?? '',
                                    'od' => (string)$value->t_orddate ?? '',
                                    'rd' => (string)$value->t_reldate ?? '',
                                    'ref' => (string)$value->t_ref ?? '',
                                    'um' => (string)$value->t_um ?? '',
                                    'qtyoh' => (string)$value->t_qty_oh ?? '',
                                    'qtytopickkemasan' => (string)$value->t_qty_kem ?? '',
                                    'edfuc' => (string)$value->t_ed_fuc ?? '',
                                    'qtyshp' => (string)$value->t_qty_shp ?? '',
                                    'qtywip' => (string)$value->t_qty_wip ?? '',
                                    'loc' => (string)$value->t_loc ?? '',
                                    'oldloc' => (string)$value->t_loc ?? '',
                                    'locationlist' => $locationlist
                                ];

                                // dd('a');
                                $wonbr[$currentWo] = [
                                    'wonbrnbr' => (string)$value->t_nbr,
                                    'wopart' => (string)$value->t_part,
                                    'site' => (string)$value->t_site ?? '',
                                    'woid' => (string)$value->t_id,
                                    'detail' => $detail
                                ];
                            }
                        }
                    }
                }



                // $master[$currentPick] = [
                //     'picknbr' => (string)$value->t_pick_nbr,
                //     'site' => (string)$value->t_site,
                //     'status' => (string)$value->t_status,

                //     'wonbr' => $wonbr
                // ];
                // dd($wonbr);

            } else {
                // $xxinvdet = xxinvDet::where('xxinv_part', $item)
                //     // ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
                //     // ->when($site !== '', fn($query) => $query->where('xxinv_site', $site))
                //     // ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
                //     ->where('xxinv_site', $site)
                //     ->where('xxinv_lot', $lot)
                //     ->get();
                // if ($xxinvdet) {
                //     foreach ($xxinvdet as $xxinvdet) {
                //         $locationlist[] =  [
                //             'id' => (string)$value->t_id ?? '',
                //             'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                //             'level' => (string)$xxinvdet->xxinv_level ?? '',
                //         ];
                //     }
                // }

                if ($currentpart != $item) {
                    $currentpart = $item;
                    $xxinvdet = xxinvDet::where('xxinv_part', $item)
                        // ->when($item !== '', fn($query) => $query->where('xxinv_part', $item))
                        // ->when($site !== '', fn($query) => $query->where('xxinv_site', $site))
                        // ->when($lot !== '', fn($query) => $query->where('xxinv_lot', $lot))
                        ->where('xxinv_site', $site)
                        ->where('xxinv_lot', $lot)
                        ->get();
                    if (count($xxinvdet) > 0) {
                        if ($status != 'PICK') {
                            $picklist = picklistShopping::query();
                            $picklist->where('ps_approver', Auth::user()->username);
                            $picklist->where('ps_number', (string)$value->t_nbr);
                            $picklist->where('ps_wo_lot', (string)$value->t_id);
                            $picklist->where('ps_part', (string)$value->t_comp);
                            $picklist->where('ps_lot', (string)$value->t_lot);
                            $picklist->where('ps_status', '=', $status);
                            $picklistRecord  = $picklist->first();

                            if ($picklistRecord) {
                                foreach ($xxinvdet as $xxinvdet) {
                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                    ];
                                }
                                $wonbr[$currentWo]['detail'][] = [
                                    'wodpart' => (string)$value->t_comp,
                                    'qtyreq' => (string)$value->t_qty_req,
                                    'qtypick' => (string)$value->t_qty,
                                    'qtytopick' => (string)$value->t_qty,
                                    'qtykemasan' => (string)$value->t_qty_kem,
                                    'lot' => (string)$value->t_lot,
                                    'id' => (string)$value->t_id,
                                    // 'wrh' => (string)$xxinvdet->xxinv_wrh,
                                    // 'level' => (string)$xxinvdet->xxinv_level,
                                    // 'bin' => (string)$xxinvdet->xxinv_bin,
                                    'dd' => (string)$value->t_duedate,
                                    'od' => (string)$value->t_orddate,
                                    'rd' => (string)$value->t_reldate,
                                    'ref' => (string)$value->t_ref,
                                    'um' => (string)$value->t_um,
                                    'qtyoh' => (string)$value->t_qty_oh,
                                    'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                                    'edfuc' => (string)$value->t_ed_fuc,
                                    'qtyshp' => (string)$value->t_qty_shp,
                                    'qtywip' => (string)$value->t_qty_wip,
                                    'loc' => (string)$value->t_loc,
                                ];
                            }
                        } else {
                            $picklist = picklistShopping::query();
                            $picklist->where('ps_approver', Auth::user()->username);
                            $picklist->where('ps_number', (string)$value->t_nbr);
                            $picklist->where('ps_wo_lot', (string)$value->t_id);
                            $picklist->where('ps_part', (string)$value->t_comp);
                            $picklist->where('ps_lot', (string)$value->t_lot);
                           
                            $picklistRecord  = $picklist->first();
                            if (!$picklistRecord) {
                                foreach ($xxinvdet as $xxinvdet) {
                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                    ];
                                }
                                $wonbr[$currentWo]['detail'][] = [
                                    'wodpart' => (string)$value->t_comp,
                                    'qtyreq' => (string)$value->t_qty_req,
                                    'qtypick' => (string)$value->t_qty,
                                    'qtytopick' => (string)$value->t_qty,
                                    'qtykemasan' => (string)$value->t_qty_kem,
                                    'lot' => (string)$value->t_lot,
                                    'id' => (string)$value->t_id,
                                    // 'wrh' => (string)$xxinvdet->xxinv_wrh,
                                    // 'level' => (string)$xxinvdet->xxinv_level,
                                    // 'bin' => (string)$xxinvdet->xxinv_bin,
                                    'dd' => (string)$value->t_duedate,
                                    'od' => (string)$value->t_orddate,
                                    'rd' => (string)$value->t_reldate,
                                    'ref' => (string)$value->t_ref,
                                    'um' => (string)$value->t_um,
                                    'qtyoh' => (string)$value->t_qty_oh,
                                    'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                                    'edfuc' => (string)$value->t_ed_fuc,
                                    'qtyshp' => (string)$value->t_qty_shp,
                                    'qtywip' => (string)$value->t_qty_wip,
                                    'loc' => (string)$value->t_loc,
                                ];
                            }
                        }
                    }
                }
            }
            // }
        }



        return response()->json(
            [
                'DataWSA' => $wonbr
            ],
            200
        );



        return GeneralResources::collection($data);
    }
    public function wsaSendQtyPick(Request $req)
    {
        $data = $req->all();

        log::info($data);

        $countupdated = 0;
        // log::info($data);
        DB::beginTransaction();
        try {
            $approver = $data['approver'];
            // $picknbr = $data['data']['picknbr'];
            $site = $data['site'] ?? '2100';
            // $loc = $data['data']['loc'];
            $wonbrlist = $data;
            foreach ($wonbrlist as $key => $wo) {

                if ($key == 'data') {
                    // log::info($wo
                    // log::info($wo['detail']);
                    // log::info('test');
                    foreach ($wo['detail'] as  $det) {
                        foreach ($det['locationlist'] as $locdet) {

                            if (isset($locdet['qtyloc']) && $locdet['qtyloc'] != 0) {

                                if ($wo['wonbrnbr'] == 'manual') {
                                    $wonbr = '';
                                } else {
                                    $wonbr = $wo['wonbrnbr'];
                                }
                                $wolot = $wo['woid'];
                                $wodpart = $det['wodpart'];
                                $lot = $det['lot'];
                                $wrh = $locdet['wrh'];
                                $level = $locdet['level'];
                                $bin = $locdet['bin'];
                                $qty = $locdet['qtyloc'];
                                log::info($wrh . ' ' . $level . ' ' . $bin . ' ' . $qty);
                                $qtypick = $det['qtypick'];
                                $loc = $det['loc'];
                                $oldloc = $det['oldloc'];

                                $domain = Domain::first();
                                $domainCode = $domain->domain ?? '';

                                $xxinvdet = xxinvDet::where('xxinv_domain', $domainCode)
                                    ->where('xxinv_part', $wodpart)
                                    ->where('xxinv_site', $site)
                                    ->where('xxinv_loc', $oldloc)
                                    ->where('xxinv_lot', $lot)
                                    ->where('xxinv_wrh', $wrh)
                                    ->where('xxinv_level', $level)
                                    ->where('xxinv_bin', $bin)
                                    ->first();
                                log::info($domainCode . ' ' . $wodpart . ' ' . $site . ' ' . $oldloc . ' ' . $loc . ' ' . $lot . ' ' . $wrh . ' ' . $level . ' ' . $bin);
                                log::info($xxinvdet);
                                if ($xxinvdet) {
                                    $xxinvdet->xxinv_qty_shp = $xxinvdet->xxinv_qty_shp + $qty;
                                    $xxinvdet->xxinv_qty_wrh = $xxinvdet->xxinv_qty_wrh - $qty;
                                    $xxinvdet->xxinv_qty_shp = $qty;
                                    $xxinvdet->xxinv_loc = $loc;
                                    $xxinvdet->save();

                                    $countupdated++;

                                    $checkpicklistshopping = PicklistShopping::where('ps_number', $wonbr)
                                        ->where('ps_part', $wodpart)
                                        ->where('ps_lot', $lot)
                                        ->where('ps_status', 'shopping')
                                        ->where('ps_warehouse', $wrh)
                                        ->where('ps_level', $level)
                                        ->where('ps_bin', $bin)
                                        ->first();
                                    if (!$checkpicklistshopping) {
                                        $shopping = new PicklistShopping();
                                        $shopping->ps_number = $wonbr;
                                        $shopping->ps_approver = $approver;
                                        $shopping->ps_part = $wodpart;
                                        $shopping->ps_lot = $lot;
                                        $shopping->ps_wo_lot = $wolot;
                                        $shopping->ps_warehouse = $wrh;
                                        $shopping->ps_level = $level;
                                        $shopping->ps_bin = $bin;
                                        $shopping->ps_status = "shopping";
                                        $shopping->save();
                                    }

                                    $newTransactionHistory = new TransactionHistory();
                                    $newTransactionHistory->tr_nbr = $wonbr;
                                    $newTransactionHistory->tr_order = $wonbr;
                                    $newTransactionHistory->tr_program = 'Picklist Module';
                                    $newTransactionHistory->tr_activity = 'Shopping';
                                    $newTransactionHistory->tr_user =  $approver ?? '';
                                    // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                                    $newTransactionHistory->tr_part = $wodpart ?? '';
                                    $newTransactionHistory->tr_uom =  '';
                                    $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                                    $newTransactionHistory->tr_lot =  $lot ?? '';
                                    $newTransactionHistory->tr_qty =  $qtypick ?? '';
                                    $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                                    $newTransactionHistory->tr_reference =  '';
                                    $newTransactionHistory->tr_site =  $site ?? '';
                                    $newTransactionHistory->tr_location = $loc ?? '';
                                    $newTransactionHistory->tr_warehouse =  $wrh ?? '';
                                    $newTransactionHistory->tr_level = $level ?? '';
                                    $newTransactionHistory->tr_bin =  $bin ?? '';
                                    $newTransactionHistory->tr_remark = '';
                                    $newTransactionHistory->save();
                                } else {
                                    Log::channel('Picklist')->info("Update Qty Pick Failed for Picklist WO : " . $wonbr . " Part : " . $wodpart);
                                    return response()->json([
                                        'Status' => 'Error',
                                        'Message' => "Update Qty Pick Failed for Picklist WO : " . $wonbr . " Part : " . $wodpart
                                    ], 422);
                                }
                            }
                            // else {
                            //     Log::channel('Picklist')->info("Update Qty Pick Failed for Picklist WO : " . $wonbr . " Part : " . $wodpart);
                            //     return response()->json([
                            //         'Status' => 'Error',
                            //         'Message' => "Update Qty Pick Failed for Picklist WO : " . $wonbr . " Part : " . $wodpart
                            //     ], 422);
                            // }
                        }
                    }
                }
            }

            DB::commit();





            // $hasil = (new WSAServices())->wsaUpdateQtyPick($picknbr, $qtypick, $wonbr, $wodpart, $site, $loc, $lot, $wrh, $level, $bin);
            // if ($hasil == 'false') {
            //     Log::channel('Picklist')->info("Update Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart);
            //     return response()->json([
            //         'Status' => 'Error',
            //         'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart
            //     ], 422);
            // }

            // }






            return response()->json([
                'Status' => 'Success',
                'Message' => "Update Qty Pick Success"
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            log::info($e->getMessage());
            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage()
            ], 422);
        }
    }
    public function wsaUpdateStatusPick(Request $req)
    {
        //$data = $req->all();
        // $picknbr = $req->input('picknbr');
        DB::beginTransaction();
        try {
            $id = $req->input('id');
            $status = $req->input('status');

            // $status = $req->input('status');
            $data = $req->input('data');
            $approver = $req->input('approver') ?? '';
            $lot = $data['lot'] ?? '';
            $part = $data['wodpart'] ?? '';
            $qty = $data['qtyshp'] ?? '';
            $site = $data['site'] ?? '';
            $loc = $data['loc'] ?? '';
            // $wrh = $data['wrh'] ?? '';
            // $level = $data['level'] ?? '';
            // $bin = $data['bin'] ?? '';
            $wonbr = $req->input('wonbr');
            $wrh = $req->input('warehouse') ?? '';
            $level = $req->input('level') ?? '';
            $bin = $req->input('bin') ?? '';
            log::info($wonbr . ' ' . $id . ' ' . $part . ' ' . $lot . ' ' . $wrh . ' ' . $level . ' ' . $bin . ' ' . $status);
            // dd('stop');
            // $locto = $data['loc'];
            $picklist = picklistShopping::where('ps_number', $wonbr)
                ->where('ps_wo_lot', $id)
                ->where('ps_part', $part)
                ->where('ps_lot', $lot)
                ->where('ps_warehouse', $wrh)
                ->where('ps_level', $level)
                ->where('ps_bin', $bin)->first();

            // dump($picklist);
            log::info($picklist);
            if ($picklist) {
                log::info('Picklist found: ' . $picklist->ps_number);
                $picklist->ps_status = $status;
                $picklist->save();

                $newTransactionHistory = new TransactionHistory();
                $newTransactionHistory->tr_nbr = $wonbr;
                $newTransactionHistory->tr_order = $wonbr;
                $newTransactionHistory->tr_program = 'Picklist Module';
                $newTransactionHistory->tr_activity = 'Approval';
                $newTransactionHistory->tr_user =  $approver ?? '';
                // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                $newTransactionHistory->tr_part = $part ?? '';
                $newTransactionHistory->tr_uom =  '';
                $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                $newTransactionHistory->tr_lot =  $lot ?? '';
                $newTransactionHistory->tr_qty =  $qty ?? '';
                $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                $newTransactionHistory->tr_reference =  '';
                $newTransactionHistory->tr_site =  $site ?? '';
                $newTransactionHistory->tr_location = $loc ?? '';
                $newTransactionHistory->tr_warehouse =  $wrh ?? '';
                $newTransactionHistory->tr_level = $level ?? '';
                $newTransactionHistory->tr_bin =  $bin ?? '';
                $newTransactionHistory->tr_remark = '';
                $newTransactionHistory->save();

                DB::commit();
                return response()->json([
                    'Status' => 'Success',
                    'Message' => "Update Status Pick Success for Picklist Item: " . $part
                ], 200);
            } else {
                // log::info('Picklist not found for: ' . $wonbr . ', Part: ' . $part . ', Lot: ' . $lot);
                // log::info($wonbr . ' ' . $id . ' ' . $part . ' ' . $lot . ' ' . $wrh . ' ' . $level . ' ' . $bin);
                // // $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status, $qty, $part, $lot);

                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Update Qty Pick Failed for Picklist Item: " . $part
                ], 422);
            }
        } catch (Exception $e) {
            DB::rollBack();
            log::info($e->getMessage());
            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage()
            ], 422);
        }
    }

    // public function getPicklistDetAppr(Request $req)

    // {
    //     $statusreq = $req->status;
    //     $user = $req->player;
    //     $status = str_replace('_', ' ', $statusreq);
    //     $wonbr = $req->wonbr;
    //     $site = $req->site;
    //     $lot = $req->lot;
    //     // $user = $req->player;

    //     // $hasil = (new WSAServices())->wsaGetPickDetail($status);
    //     $hasil = (new WSAServices())->wsaGetPickDetail($status, $wonbr, $site, $lot);
    //     // $hasil = (new WSAServices())->wsaGetPickDetail($status);

    //     $currentPick = '';
    //     $currentWo = '';
    //     $detail = [];
    //     $master = [];
    //     $wonbr = [];
    //     $wonbrstring = '';
    //     if ($hasil[0] == 'false') {
    //         return response()->json([
    //             'Status' => 'Error',
    //             'Message' => "Data Not Found."
    //         ], 422);
    //     } else {
    //         $listData = $hasil[1];
    //     }

    //     foreach ($listData as $key => $value) {


    //         $wonbrstring = (string)$value->t_wo_nbr;

    //         if (strlen($wonbrstring) == 0) {
    //             $wonbrstring = 'manual';
    //             $checkpicklistshopping = PicklistShopping::where('ps_number', (string)$value->t_pick_nbr)
    //                 ->where('ps_part', (string)$value->t_wod_part)
    //                 ->where('ps_lot', (string)$value->t_lot)
    //                 ->where('ps_status', 'shopping')
    //                 ->where('ps_approver', $req->player)
    //                 ->first();
    //             if ($checkpicklistshopping) {
    //                 if ($currentPick != (string)$value->t_pick_nbr) {
    //                     $wonbrstring = 'manual';
    //                     $currentWo = '';

    //                     $detail = [];
    //                     $wonbr = [];
    //                     $currentPick = (string)$value->t_pick_nbr;

    //                     if ($currentWo != $wonbrstring) {
    //                         $currentWo = $wonbrstring;


    //                         $detail[] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,


    //                         ];

    //                         $wonbr[$currentWo] = [
    //                             'wonbrnbr' => $wonbrstring,
    //                             'wopart' => '',
    //                             'detail' => $detail
    //                         ];

    //                         $master[$currentPick] = [
    //                             'picknbr' => (string)$value->t_pick_nbr,
    //                             'site' => (string)$value->t_site,
    //                             'status' => (string)$value->t_status,

    //                             'wonbr' => $wonbr,
    //                         ];
    //                     } else {
    //                         $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,
    //                             'loc' => (string)$value->t_loc

    //                         ];
    //                     }
    //                 } else {
    //                     $wonbrstring = 'manual';
    //                     if ($currentWo != $wonbrstring) {
    //                         $currentWo = $wonbrstring;

    //                         $detail[] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,
    //                             'loc' => (string)$value->t_loc

    //                         ];
    //                         $wonbr[$currentWo] = [
    //                             'wonbrnbr' => $currentWo,
    //                             'wopart' => '',
    //                             'woid' => '',
    //                             'detail' => $detail
    //                         ];
    //                         $master[$currentPick] = [
    //                             'picknbr' => (string)$value->t_pick_nbr,
    //                             'site' => (string)$value->t_site,
    //                             'status' => (string)$value->t_status,

    //                             'wonbr' => $wonbr
    //                         ];
    //                     } else {
    //                         $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,
    //                             'loc' => (string)$value->t_loc

    //                         ];
    //                     }
    //                 }
    //             }
    //         } else {
    //             // $checkpicklistshopping = PicklistShopping::where('ps_number', (string)$value->t_pick_nbr)
    //             //     ->where('ps_status', 'shopping')
    //             //     ->where('ps_approver', $req->player)
    //             //     ->first();
    //             $checkpicklistshopping = PicklistShopping::where('ps_number', (string)$value->t_pick_nbr)
    //                 ->where('ps_part', (string)$value->t_wod_part)
    //                 ->where('ps_lot', (string)$value->t_lot)
    //                 ->where('ps_status', 'shopping')
    //                 ->where('ps_approver', $req->player)
    //                 ->first();
    //             if ($checkpicklistshopping) {
    //                 if ($currentPick != (string)$value->t_pick_nbr) {

    //                     $currentWo = '';
    //                     $detail = [];
    //                     $wonbr = [];
    //                     $currentPick = (string)$value->t_pick_nbr;

    //                     if ($currentWo != (string)$value->t_wo_nbr) {
    //                         $currentWo = (string)$value->t_wo_nbr;

    //                         $detail[] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,
    //                             'loc' => (string)$value->t_loc

    //                         ];
    //                         $wonbr[$currentWo] = [
    //                             'wonbrnbr' => (string)$value->t_wo_nbr,
    //                             'wopart' => (string)$value->t_wo_part,
    //                             'woid' => (string)$value->t_wo_id,
    //                             'detail' => $detail
    //                         ];
    //                         $master[$currentPick] = [
    //                             'picknbr' => (string)$value->t_pick_nbr,
    //                             'site' => (string)$value->t_site,
    //                             'status' => (string)$value->t_status,

    //                             'wonbr' => $wonbr
    //                         ];
    //                     } else {
    //                         $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,
    //                             'loc' => (string)$value->t_loc

    //                         ];
    //                     }
    //                 } else {
    //                     if ($currentWo != (string)$value->t_wo_nbr) {

    //                         $currentWo = (string)$value->t_wo_nbr;

    //                         $wonbr = [];
    //                         $detail = [];

    //                         $detail[] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,
    //                             'loc' => (string)$value->t_loc

    //                         ];

    //                         // $wonbr[$currentWo] = [
    //                         //     'wonbrnbr' => (string)$value->t_wo_nbr,
    //                         //     'wopart' => (string)$value->t_wo_part,
    //                         //     'detail' => $detail
    //                         // ];

    //                         $master[$currentPick]['wonbr'][$currentWo] = [
    //                             'wonbrnbr' => (string)$value->t_wo_nbr,
    //                             'wopart' => (string)$value->t_wo_part,
    //                             'detail' => $detail
    //                         ];
    //                     } else {

    //                         $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
    //                             'wodpart' => (string)$value->t_wod_part,
    //                             'qtyreq' => (string)$value->t_qty_req,
    //                             'qtypick' => (string)$value->t_qty_pick,
    //                             'qtytopick' => (string)$value->t_qty_topick,
    //                             'qtykemasan' => (string)$value->t_qty_kemasan,
    //                             'lot' => (string)$value->t_lot,
    //                             'id' => (string)$value->t_wo_id,
    //                             'wrh' => (string)$value->t_wrh,
    //                             'level' => (string)$value->t_level,
    //                             'bin' => (string)$value->t_bin,
    //                             'dd' => (string)$value->t_duedate,
    //                             'od' => (string)$value->t_orddate,
    //                             'rd' => (string)$value->t_reldate,
    //                             'ref' => (string)$value->t_ref,
    //                             'um' => (string)$value->t_um,
    //                             'qtyoh' => (string)$value->t_qty_oh,
    //                             'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
    //                             'edfuc' => (string)$value->t_ed_fuc,
    //                             'qtyshp' => (string)$value->t_qty_shp,
    //                             'qtywip' => (string)$value->t_qty_wip,
    //                             'loc' => (string)$value->t_loc
    //                         ];
    //                         // dd($master[$currentPick]['wonbr'][$currentWo]['detail'],$hasil[1],$currentWo);
    //                     }
    //                 }
    //             }
    //         }
    //     }


    //     return response()->json(
    //         [
    //             'DataWSA' => $master
    //         ],
    //         200
    //     );



    //     return GeneralResources::collection($data);
    // }

    public function getPicklistDetAppr(Request $req)
    {

        $statusreq = $req->status;
        $wonbr = $req->wonbr ?? '';
        $site = $req->site ?? '';
        $lot = $req->lot ?? '';
        // $user = $req->player;
        $status = str_replace('_', ' ', $statusreq);
        // $hasil = (new WSAServices())->wsaGetPickDetail($status);
        // $hasil = (new WSAServices())->wsaGetPickDetail($status, $wonbr, $site, $lot);
        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        // if ($hasil[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Data Not Found."
        //     ], 422);
        // } else {
        //     $listData = $hasil[1];
        // }
        $checkpicklistshopping = PicklistShopping::where('ps_status', 'shopping')
            ->where('ps_approver', $req->player)
            ->when($wonbr != '', fn($query) => $query->where('ps_number', $wonbr))
            ->get();
        // dd($checkpicklistshopping);
        if ($checkpicklistshopping) {
            foreach ($checkpicklistshopping as $check) {
                $statusps = $check->ps_status;
                $wonbrps = $check->ps_number;
                $siteps = $check->ps_site ?? '2100';
                $lotps = $check->ps_wo_lot;
                // dd($status,$wonbrps,$siteps,$lotps);
                $item = (string)$check->ps_part ?? '';
                $site = '2100';
                $lot = (string)$check->ps_lot ?? '';
                // dd($lot);

                $xxinvdet = xxinvDet::where('xxinv_part', $item)
                    ->where('xxinv_site', $site)
                    ->where('xxinv_lot', $lot)
                    ->first();
                // dd($xxinvdet);

                // dd($wonbrps,$status,$siteps,$lotps);
                $status = '';
                $hasil = (new WSAServices())->wsaGetPickDetail($status, $wonbrps, $siteps, $lotps);
                if ($hasil[0] == 'false') {
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Data Not Found."
                    ], 422);
                } else {
                    $listData = $hasil[1];
                    // dd($hasil);


                    foreach ($listData as $key => $value) {

                        $currentWo = '';

                        if ($xxinvdet) {
                            if (
                                $xxinvdet->xxinv_loc == $value->t_loc
                                && $xxinvdet->xxinv_lot == $value->t_lot
                                && $xxinvdet->xxinv_part == $value->t_comp
                            ) {
                                // dd((string)$value->t_nbr);

                                if ($currentWo != (string)$value->t_nbr) {
                                    $currentWo = (string)$value->t_nbr;
                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                        'qtyloc' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                        'status' => (string)$check->ps_status ?? '',
                                    ];

                                    // dd('b');
                                    $detail[] = [
                                        'wodpart' => (string)$value->t_comp ?? '',
                                        'qtyreq' => (string)$value->t_qty_req ?? '',
                                        'qtypick' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                        // (string)$value->t_qty ?? '',
                                        'qtytopick' => (string)$value->t_qty ?? '',
                                        'qtykemasan' => (string)$value->t_qty_kem ?? '',
                                        'lot' => (string)$value->t_lot ?? '',
                                        'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                        'dd' => (string)$value->t_duedate ?? '',
                                        'od' => (string)$value->t_orddate ?? '',
                                        'rd' => (string)$value->t_reldate ?? '',
                                        'ref' => (string)$value->t_ref ?? '',
                                        'um' => (string)$value->t_um ?? '',
                                        'qtyoh' => (string)$value->t_qty_oh ?? '',
                                        'qtytopickkemasan' => (string)$value->t_qty_kem ?? '',
                                        'edfuc' => (string)$value->t_ed_fuc ?? '',
                                        'qtyshp' => (string)$value->t_qty_shp ?? '',
                                        'qtywip' => (string)$value->t_qty_wip ?? '',
                                        'loc' => (string)$value->t_loc ?? '',
                                        'locationlist' => $locationlist
                                    ];
                                    // dd('a');
                                    $wonbr[$currentWo] = [
                                        'wonbrnbr' => (string)$value->t_nbr,
                                        'wopart' => (string)$value->t_part,
                                        'site' => (string)$value->t_site ?? '',
                                        'woid' => (string)$value->t_id,
                                        'detail' => $detail
                                    ];
                                } else {
                                    $locationlist[] =  [
                                        // 'id' => (string)$value->t_id ?? '',
                                        'wrh' => (string)$xxinvdet->xxinv_wrh ?? '',
                                        'level' => (string)$xxinvdet->xxinv_level ?? '',
                                        'bin' => (string)$xxinvdet->xxinv_bin ?? '',
                                        'qtyloc' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                    ];

                                    $wonbr[$currentWo]['detail'][] = [
                                        'wodpart' => (string)$value->t_comp,
                                        'qtyreq' => (string)$value->t_qty_req,
                                        'qtypick' => (string)$xxinvdet->xxinv_qty_shp ?? '0',
                                        // (string)$value->t_qty ?? '',

                                        'qtytopick' => (string)$value->t_qty,
                                        'qtykemasan' => (string)$value->t_qty_kem,
                                        'lot' => (string)$value->t_lot,
                                        'id' => (string)$value->t_id,
                                        'wrh' => (string)$xxinvdet->xxinv_wrh,
                                        'level' => (string)$xxinvdet->xxinv_level,
                                        'bin' => (string)$xxinvdet->xxinv_bin,
                                        'dd' => (string)$value->t_duedate,
                                        'od' => (string)$value->t_orddate,
                                        'rd' => (string)$value->t_reldate,
                                        'ref' => (string)$value->t_ref,
                                        'um' => (string)$value->t_um,
                                        'qtyoh' => (string)$value->t_qty_oh,
                                        'qtytopickkemasan' => (string)$value->t_qty_kem,
                                        'edfuc' => (string)$value->t_ed_fuc,
                                        'qtyshp' => (string)$value->t_qty_shp,
                                        'qtywip' => (string)$value->t_qty_wip,
                                        'loc' => (string)$value->t_loc,
                                        'locationlist' => $locationlist
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            if (count($wonbr) > 0) {


                return response()->json(
                    [
                        'DataWSA' => $wonbr
                    ],
                    200
                );
            } else {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Data Not Found."
                ], 422);
            }
        } else {

            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        }



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
        // $locto = $req->loc;
        // $picknbr = $req->picknbr;
        // $status = $req->status;
        $picknbr = $req->input('picknbr');
        $status = $req->input('status');
        $data = $req->input('data');
        $locto = $req->input('loc');
        $user = $req->input('username');
        $lot = $data['lot'];
        $part = $data['wodpart'];
        $qty = $data['qtysmp'] ?? 0;
        $site = $data['site'] ?? '';
        $loc = $data['loc'] ?? '';
        $wrh = $data['wrh'] ?? '';
        $level = $data['level'] ?? '';
        $bin = $data['bin'] ?? '';
        $wonbr = $req->input('wonbr');
        // $locto = $req->query('loc');


        $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status, $qty, $part, $lot);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Update Qty Pick Failed for Picklist : " . $picknbr
            ], 422);
        } else {
            $newTransactionHistory = new TransactionHistory();
            $newTransactionHistory->tr_nbr = $picknbr;
            $newTransactionHistory->tr_order = $wonbr;
            $newTransactionHistory->tr_program = 'Picklist Module';
            $newTransactionHistory->tr_activity = 'Transfer';
            $newTransactionHistory->tr_user =  $user ?? '';
            // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
            $newTransactionHistory->tr_part = $part ?? '';
            $newTransactionHistory->tr_uom =  '';
            $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
            $newTransactionHistory->tr_lot =  $lot ?? '';
            $newTransactionHistory->tr_qty =  $qty ?? '';
            $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
            $newTransactionHistory->tr_reference =  '';
            $newTransactionHistory->tr_site =  $site ?? '';
            $newTransactionHistory->tr_location = $loc ?? '';
            $newTransactionHistory->tr_warehouse =  $wrh ?? '';
            $newTransactionHistory->tr_level = $level ?? '';
            $newTransactionHistory->tr_bin =  $bin ?? '';
            $newTransactionHistory->tr_remark = '';
            $newTransactionHistory->save();
            DB::commit();
            return response()->json(
                'success',
                200
            );
        }
    }

    public function submitPicklistReceipt(Request $req)
    {

        // $data = $req->data;
        // $picknbr = $req->picknbr;
        // $status = $req->status;



        $picknbr = $req->input('picknbr');
        $status = $req->input('status');
        $data = $req->input('data');
        $lot = $data['lot'];
        $part = $data['wodpart'];
        // dd($req->all());
        $qty = $data['qtywip'];
        $user = $req->input('username');
        $site = $data['site'] ?? '';
        $loc = $data['loc'] ?? '';
        $wrh = $data['wrh'] ?? '';
        $level = $data['level'] ?? '';
        $bin = $data['bin'] ?? '';
        // $locto = $data['loc'];
        if ($status == 'Receipt') {
            // $pickloctodata = PicklistLocationTo::where('picklist_number', $picknbr)->first();
            // if ($pickloctodata == null) {
            //     return response()->json([
            //         'Status' => 'Error',
            //         'Message' => "Location To for Picklist : " . $picknbr . " Not Found. Please do Transfer Process First."
            //     ], 422);
            // }
            // $picklocto = $pickloctodata->location_to;

            $wonbr = $req->input('wonbr');
            // foreach ($wonbr as $wo) {
            // foreach ($wo['detail'] as $det) {
            // if ($wo['wonbrnbr'] == 'manual') {
            //     $wonbr = '';
            // } else {
            //     $wonbr = $wo['wonbrnbr'];
            // }
            $wodpart = $data['wodpart'];
            $lot = $data['lot'];
            $wrh = $data['wrh'];
            $level = $data['level'];
            $bin = $data['bin'];
            $qtypick = $data['qtywip'];
            $site = $req->input('site');

            // $loc = $req->input('loc');
            // dd($req->all(),$status,$pickloctodata,$picklocto);
            // $qxtendsingleitem = (new QxtendServices())->qxTransferSingleItemWo($wodpart, $wonbr, $site, $site, $loc, $picklocto, $qtypick, '', '', '', $lot);
            // if ($qxtendsingleitem == 'false') {
            //     return response()->json([
            //         'Status' => 'Error',
            //         'Message' => "Transfer Qty Pick Failed for Picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $wodpart
            //     ], 422);
            // }
            // }
            // }

            $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $status, $qty, $part, $lot);
            if ($hasil[0] == 'false') {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Receipt Picklist Failed for Picklist : " . $picknbr
                ], 422);
            } else {
                $newTransactionHistory = new TransactionHistory();
                $newTransactionHistory->tr_nbr = $picknbr;
                $newTransactionHistory->tr_order = $wonbr;
                $newTransactionHistory->tr_program = 'Picklist Module';
                $newTransactionHistory->tr_activity = 'Receipt';
                $newTransactionHistory->tr_user =  $user ?? '';
                // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                $newTransactionHistory->tr_part = $part ?? '';
                $newTransactionHistory->tr_uom =  '';
                $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                $newTransactionHistory->tr_lot =  $lot ?? '';
                $newTransactionHistory->tr_qty =  $qty ?? '';
                $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                $newTransactionHistory->tr_reference =  '';
                $newTransactionHistory->tr_site =  $site ?? '';
                $newTransactionHistory->tr_location = $loc ?? '';
                $newTransactionHistory->tr_warehouse =  $wrh ?? '';
                $newTransactionHistory->tr_level = $level ?? '';
                $newTransactionHistory->tr_bin =  $bin ?? '';
                $newTransactionHistory->tr_remark = '';
                $newTransactionHistory->save();
                return response()->json(
                    'success',
                    200
                );
            }
        } else if ($status == 'Deny') {
            $statusnew = 'PICK';
            //return to previous status
            // $status = 'Approve';
            $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, $statusnew, $qty, $part, $lot);
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
        $site = $req->site ?? '';

        $hasil = (new WSAServices())->wsaGetLocationTransfer($wonbr, $item, $site);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Data Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
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
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
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

                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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

                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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

                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
                        ];
                    }
                } else {
                    if ($currentWo != (string)$value->t_wo_nbr) {
                        $currentWo = (string)$value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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

                            'wonbr' => $wonbr
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string)$value->t_wod_part,
                            'qtyreq' => (string)$value->t_qty_req,
                            'qtypick' => (string)$value->t_qty_pick,
                            'qtytopick' => (string)$value->t_qty_topick,
                            'qtykemasan' => (string)$value->t_qty_kemasan,
                            'lot' => (string)$value->t_lot,
                            'id' => (string)$value->t_wo_id,
                            'wrh' => (string)$value->t_wrh,
                            'level' => (string)$value->t_level,
                            'bin' => (string)$value->t_bin,
                            'dd' => (string)$value->t_duedate,
                            'od' => (string)$value->t_orddate,
                            'rd' => (string)$value->t_reldate,
                            'ref' => (string)$value->t_ref,
                            'um' => (string)$value->t_um,
                            'qtyoh' => (string)$value->t_qty_oh,
                            'qtytopickkemasan' => (string)$value->t_qty_topick_kem,
                            'edfuc' => (string)$value->t_ed_fuc,
                            'qtyshp' => (string)$value->t_qty_shp,
                            'qtywip' => (string)$value->t_qty_wip,
                            'loc' => (string)$value->t_loc,
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
        $item = $req->item ?? '';

        $wsaData = (new WSAServices())->wsaGetInvDet($site,  $loc, $wrh, $item);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }
    public function nullConversion($data)
    {
        if ($data == null || strtolower($data) == 'null') {
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
        $lot = $this->nullConversion($data['lot']);
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
            $item,
            $sitefrom,
            $siteto,
            $locfrom,
            $locto,
            $qty,
            $wh,
            $ref,
            $level,
            $bin,
            $lot
        ]);
    }

    public function issueWorkOrder(Request $req)
    {
        // log::info($req->all());
        $data = $req->data[0];
        $dataall = $req->all();



        $datawo = $data['wonbr'];
        //  $detail = $datawo['detail'];
        foreach ($datawo as $dw) {
            $datadetail = $dw['detail'];




            $picknbr = $req->picknbr ?? '';
            $part = $req->part ?? '';
            $lotserial = $req->lot ?? '';
            $qty = $req->qtypick ?? '';
            $site = $data['site'] ?? '';
            $wonbr = $data['wonbrnbr'] ?? '';
            $lot = $data['woid'] ?? '';
            $effdate = Carbon::today()->format('Y-m-d');
            $location = $dw['loc'] ?? '';
            $user = $req->approver ?? '';


            $qxtendWoIssue = (new QxtendServices())->qxWorkOrderComponentIssue($wonbr, $location, $lot, $effdate, $part, $qty, $site, $lotserial);
            // $effdata =
            if ($qxtendWoIssue[0] == false) {
                Log::channel('Picklist')->info("Wo issue failed for picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $part);

                return response()->json([
                    'Status' => 'Error',

                    'Message' => $qxtendWoIssue[1] ?? 'Unknown error occurred'
                ], 422);
                //'Message' => "Wo issue failed for picklist : " . $picknbr . " WO : " . $wonbr. " Part : " . $part
            } else {

                $hasil = (new WSAServices())->wsaUpdateStatusPick($picknbr, 'Issued', $qty, $part, $lot);


                if ($hasil[0] == 'false') {
                    Log::channel('Picklist')->info("Update status wo issue failed for picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $part);
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Update status wo issue failed for picklist : " . $picknbr . " WO : " . $wonbr . " Part : " . $part
                    ], 422);
                } else {
                    $newTransactionHistory = new TransactionHistory();
                    $newTransactionHistory->tr_nbr = $picknbr;
                    $newTransactionHistory->tr_order = $wonbr;
                    $newTransactionHistory->tr_program = 'Picklist Module';
                    $newTransactionHistory->tr_activity = 'WO Issue';
                    $newTransactionHistory->tr_user =  $user ?? '';
                    // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                    $newTransactionHistory->tr_part = $part ?? '';
                    $newTransactionHistory->tr_uom =  '';
                    $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                    $newTransactionHistory->tr_lot =  $lot ?? '';
                    $newTransactionHistory->tr_qty =  $qty ?? '';
                    $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                    $newTransactionHistory->tr_reference =  '';
                    $newTransactionHistory->tr_site =  $site ?? '';
                    $newTransactionHistory->tr_location = $loc ?? '';
                    $newTransactionHistory->tr_warehouse =  $wrh ?? '';
                    $newTransactionHistory->tr_level = $level ?? '';
                    $newTransactionHistory->tr_bin =  $bin ?? '';
                    $newTransactionHistory->tr_remark = '';
                    $newTransactionHistory->save();
                }
            }
        }
        return response()->json(
            'success',
            200
        );
    }
    public function getApproverList(Request $req)
    {
        $user = User::with('getRole')->select('username')->whereRelation('getRole', 'role_android_acc', 'like', '%AP02%')->orderBy('username', 'asc')->get();
        return response()->json(
            [
                'DataWSA' => $user
            ],
            200
        );
    }
}
