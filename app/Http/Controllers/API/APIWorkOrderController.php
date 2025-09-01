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

class APIWorkOrderController extends Controller
{
    public function getDataWo(Request $req)
    {
        $data = workOrderMaster::query()->with(['getDetail' => function ($query) {
            $query->orderBy('wod_part', 'asc')
                ->orderBy('wod_site', 'asc');
        }])->where('created_by', $req->user);
        if ($req->search) {
            $data->where('wo_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhere('wo_id', 'LIKE', '%' . $req->search . '%')
                ->orWhere('wo_part', 'LIKE', '%' . $req->search . '%')
                ->orWhere('wo_part_desc', 'LIKE', '%' . $req->search . '%');
        }

        $data = $data->orderBy('id', 'desc')->get();


        return GeneralResources::collection($data);
    }

    public function wsaDataWo(Request $req)
    {

        $woidnbr = '';
        $hasil = (new WSAServices())->wsaGetWOMstr();

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
            return response()->json([
                'DataWSA' => $hasil[1]
            ], 200);
            /*
            try {
                DB::beginTransaction();
                foreach ($listData as $data) {
                    if ($woidnbr != ((string)$data->t_wo_nbr . (string)$data->t_wo_id)) {
                        $woidnbr = (string)$data->t_wo_nbr . (string)$data->t_wo_id;
                        $dataMaster = workOrderMaster::firstOrNew(
                            [
                                'wo_nbr' => (string)$data->t_wo_nbr,
                                'wo_id' => (string)$data->t_wo_id,
                                'created_by' => (string)$req->user
                            ]
                        );
                        $dataMaster->wo_site = (string)$data->t_wo_site;
                        $dataMaster->wo_part = (string)$data->t_wo_part;
                        $dataMaster->wo_part_desc = (string)$data->t_wo_part_desc;
                        $dataMaster->wo_status = (string)$data->t_wo_status ?? '';
                        $dataMaster->wo_qty_ord = (string)$data->t_wo_qty_ord ?? 0;
                        $dataMaster->wo_qty_comp = (string)$data->t_wo_qty_comp ?? 0;
                        $dataMaster->wo_qty_rjct = (string)$data->t_wo_qty_rjct ?? 0;
                        $dataMaster->wo_order_date = (string)$data->t_wo_ord_date;
                        $dataMaster->wo_release_date = (string)$data->t_wo_rel_date;
                        $dataMaster->wo_due_date = (string)$data->t_wo_due_date;
                        $dataMaster->created_by = $req->user;
                        $dataMaster->save();
                    }

                    $checkDetail = workOrderDetail::where('wod_wo_id',$dataMaster->id)->where('wod_part',(string)$data->t_wod_part)->first();
                    if(!$checkDetail){
                        $dataDetail = new workOrderDetail();
                        $dataDetail->wod_wo_id = $dataMaster->id;
                        $dataDetail->wod_nbr = (string)$data->t_wod_nbr;
                        $dataDetail->wod_op = (string)$data->t_wod_op;
                        $dataDetail->wod_part = (string)$data->t_wod_part;
                        $dataDetail->wod_part_desc = (string)$data->t_wod_part_desc;
                        $dataDetail->wod_um = (string)$data->t_wod_um;
                        $dataDetail->wod_site = (string)$data->t_wod_site;
                        $dataDetail->wod_loc = (string)$data->t_wod_loc;
                        $dataDetail->wod_qty_req = (string)$data->t_wod_qty_req ?? 0;
                        $dataDetail->wod_qty_pick = 0;
                        $dataDetail->save();
                    }
                    
                }


                DB::commit();
                return response()->json(['success', 200]);
            } catch (Exception $e) {
                DB::rollBack();
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Unable to Proccess Request: " . $e->getMessage()
                ], 422);
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Unable to Proccess Request"
                ], 422);
            }
                */
        }
    }

    public function wsaDataInvWo(Request $req)
    {
        $wonbr = WorkOrderMaster::where('created_by', $req->search)->get();
        $currentitem = '';
        $currentid = '';
        $currentqtypick = 0;
        $currentqtydiff = 0;
        $currentqtyreq = 0;
        try {
            DB::beginTransaction();
            foreach ($wonbr as $wonbr) {

                $hasil = (new WSAServices())->wsaGetInvWo($wonbr->wo_nbr);


                if ($hasil[0] == 'false') {

                    continue;
                } else {
                    $listData = $hasil[1];

                    foreach ($listData as $key => $data) {


                        $workOrder = workOrderMaster::where('wo_nbr', (string)$data->t_wo_nbr)
                            ->where('wo_id', (string)$data->t_wo_id)->first();


                        $dataDetail = workOrderDetail::where('wod_wo_id', $workOrder->id)->where('wod_part', (string)$data->t_wod_part)->first();

                        if ($dataDetail->wod_lot == null) {
                            $currentid = $workOrder->id;
                            $currentitem = (string)$data->t_wod_part;
                            $currentqtyreq = $dataDetail->wod_qty_req;
                            $currentqtydiff = $currentqtyreq - $data->t_xxinv_qtyoh;
                            if ($currentqtydiff >= 0) {
                                $currentqtypick = $data->t_xxinv_qtyoh;
                            } else {
                                $currentqtypick =  $currentqtyreq;
                            }

                            $dataDetail->wod_qty_req = $currentqtyreq;
                            $dataDetail->wod_qty_pick = $currentqtypick ?? 0;
                            $dataDetail->wod_qty_oh = (string)$data->t_xxinv_qtyoh ?? 0;
                            $dataDetail->wod_qty_pick_inv = (string)$data->t_xxinv_qtypick ?? 0;
                            $dataDetail->wod_lot = (string)$data->t_xxinv_lot ?? '';
                            $dataDetail->wod_ref = (string)$data->t_xxinv_ref ?? '';
                            $dataDetail->wod_warehouse = (string)$data->t_xxinv_wrh ?? '';
                            $dataDetail->wod_bin = (string)$data->t_xxinv_bin ?? '';
                            $dataDetail->wod_level = (string)$data->t_xxinv_level ?? '';
                            $dataDetail->wod_entry_date = (string)$data->t_xxinv_entry_date ?? '';
                            $dataDetail->wod_exp_date = (string)$data->t_xxinv_exp_date ?? '';
                            $dataDetail->wod_picklist_type = (string)$data->t_picktype ?? '';
                        } else {
                            $currentqtyreq = $dataDetail->wod_qty_req - $currentqtypick;
                            $currentqtydiff = $currentqtyreq - $data->t_xxinv_qtyoh;
                            if ($currentqtydiff >= 0) {
                                $currentqtypick = $data->t_xxinv_qtyoh;
                            } else {
                                $currentqtypick = $currentqtyreq;
                            }

                            $dataDetail = new workOrderDetail();
                            $dataDetail->wod_wo_id = $workOrder->id;
                            $dataDetail->wod_nbr = (string)$data->t_wod_nbr;
                            $dataDetail->wod_op = (string)$data->t_wod_op;
                            $dataDetail->wod_part = (string)$data->t_wod_part;
                            $dataDetail->wod_part_desc = (string)$data->t_wod_part_desc;
                            $dataDetail->wod_um = (string)$data->t_wod_um;
                            $dataDetail->wod_site = (string)$data->t_wod_site;
                            $dataDetail->wod_loc = (string)$data->t_wod_loc;
                            $dataDetail->wod_qty_req = $currentqtyreq ?? 0;
                            $dataDetail->wod_qty_pick = $currentqtypick ?? 0;
                            $dataDetail->wod_qty_oh = (string)$data->t_xxinv_qtyoh ?? 0;
                            $dataDetail->wod_qty_pick_inv = (string)$data->t_xxinv_qtypick ?? 0;
                            $dataDetail->wod_lot = (string)$data->t_xxinv_lot ?? '';
                            $dataDetail->wod_ref = (string)$data->t_xxinv_ref ?? '';
                            $dataDetail->wod_warehouse = (string)$data->t_xxinv_wrh ?? '';
                            $dataDetail->wod_bin = (string)$data->t_xxinv_bin ?? '';
                            $dataDetail->wod_level = (string)$data->t_xxinv_level ?? '';
                            $dataDetail->wod_entry_date = (string)$data->t_xxinv_entry_date ?? '';
                            $dataDetail->wod_exp_date = (string)$data->t_xxinv_exp_date ?? '';
                            $dataDetail->wod_picklist_type = (string)$data->t_picktype ?? '';
                        }


                        $dataDetail->save();
                    }
                }
            }

            DB::commit();

            return response()->json(['success', 200]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request: " . $e->getMessage()
            ], 422);
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request"
            ], 422);
        }
    }

    public function sendDataInvWo(Request $req)
    {
        $dataWo = workOrderMaster::with('getDetail')->where('created_by', $req->user)->get();
        
        try {
            DB::beginTransaction();
            $prefix = prefixWorkOrder::first();

            $currentyear = date('Y');
            $currentmonth = date('m');
            $currentday = date('d');

            if ($prefix->prefix_year_wo != $currentyear) {
                $prefix->prefix_year_wo = $currentyear;
            }
            if ($prefix->prefix_month_wo != $currentmonth) {
                $prefix->prefix_month_wo = $currentyear;
            }
            if ($prefix->prefix_day_wo != $currentday) {
                $prefix->prefix_day_wo = $currentday;
            }
            $prefix->running_nbr_wo = $prefix->running_nbr_wo + 1;
            $prefix->save();

            $picklistnbr = $prefix->prefix_wo . '/' . $prefix->prefix_year_wo . '/' . $prefix->prefix_month_wo . '/' . $prefix->prefix_day_wo . '-' . $prefix->running_nbr_wo;


            $picklist = new picklistMstr();
            $picklist->pl_nbr = $picklistnbr;
            $picklist->pl_status = 'created';
            $picklist->created_by = $req->user;
            $picklist->save();

            foreach ($dataWo as $WO) {

                $picklistWo = new picklistWo();
                $picklistWo->pl_id = $picklist->id;
                $picklistWo->pl_wo_nbr = $WO->wo_nbr;
                $picklistWo->pl_wo_id = $WO->wo_id;
                $picklistWo->pl_wo_site = $WO->wo_site;
                $picklistWo->pl_wo_status = $WO->wo_status;
                $picklistWo->pl_wo_part = $WO->wo_part;
                $picklistWo->pl_wo_part_desc = $WO->wo_part_desc;
                $picklistWo->pl_wo_qty_ord = $WO->wo_qty_ord;
                $picklistWo->pl_wo_qty_comp = $WO->wo_qty_comp;
                $picklistWo->pl_wo_qty_rjct = $WO->wo_qty_rjct;
                $picklistWo->pl_wo_order_date = $WO->wo_order_date;
                $picklistWo->pl_wo_release_date = $WO->wo_release_date;
                $picklistWo->pl_wo_due_date = $WO->wo_due_date;
                $picklistWo->pl_wo_um   =  $WO->wo_um;
                $picklistWo->created_by = $req->user;
                $picklistWo->save();

                foreach ($WO->getDetail as $data) {
                    //qxtend transfer single item
                    $result = (new QxtendServices())->qxTransferSingleItemWo(
                        $data->wod_part,
                        $WO->wo_nbr,
                        $data->wod_site,
                        $data->wod_site,
                        $data->wod_loc,
                        'WO-PICK',
                        $data->wod_qty_oh,
                        $data->wod_bin,
                        $data->wod_level,
                        $data->wod_warehouse,
                        $data->wod_lot

                    );
                    if ($result[0] === false) {
                        DB::rollBack();
                        
                        return response()->json([
                            'Status' => 'Error',
                            'Message' => "Transfer Item failed : " . $data->wod_part . " Not Found."
                        ], 422);
                    }
                }

                    $resultBill = (new QxtendServices())->qxWorkOrderBill(
                        $WO->wo_nbr,
                        $WO->wo_id,
                        $req->user
                       
                    );
                    if ($resultBill[0] == 'false') {
                        DB::rollBack();
                        return response()->json([
                            'Status' => 'Error',
                            'Message' => "Work Order Bill : " . $req->search . " Failed."
                        ], 422);
                    }
                    

                    $picklistWoDet = new picklistWoDet();
                    $picklistWoDet->pl_wod_wo_id = $picklistWo->id;
                    $picklistWoDet->pl_wod_nbr = $data->wod_nbr;
                    $picklistWoDet->pl_wod_op = $data->wod_op;
                    $picklistWoDet->pl_wod_part = $data->wod_part;
                    $picklistWoDet->pl_wod_part_desc = $data->wod_part_desc;
                    $picklistWoDet->pl_wod_um = $data->wod_um;
                    $picklistWoDet->pl_wod_site = $data->wod_site;
                    $picklistWoDet->pl_wod_loc = $data->wod_loc;
                    $picklistWoDet->pl_wod_lot = $data->wod_lot;
                    $picklistWoDet->pl_wod_ref = $data->wod_ref;
                    $picklistWoDet->pl_wod_warehouse = $data->wod_warehouse;
                    $picklistWoDet->pl_wod_bin = $data->wod_bin;
                    $picklistWoDet->pl_wod_level = $data->wod_level;
                    $picklistWoDet->pl_wod_qty_req = $data->wod_qty_req;
                    $picklistWoDet->pl_wod_qty_pick = $data->wod_qty_pick;
                    $picklistWoDet->pl_wod_qty_oh = $data->wod_qty_oh;
                    $picklistWoDet->pl_wod_qty_pick_inv = $data->wod_qty_pick_inv;
                    $picklistWoDet->pl_wod_entry_date = $data->wod_entry_date;
                    $picklistWoDet->pl_wod_exp_date = $data->wod_exp_date;
                    $picklistWoDet->pl_wod_picklist_type = $data->wod_picklist_type;
                    $picklistWoDet->wod_pl_id = $picklist->id;
                    $picklistWoDet->save();

                    $picklistHist = new PicklistHistory();

                    $picklistHist->pl_nbr = $picklistnbr;
                    $picklistHist->pl_status = 'created';
                    $picklistHist->created_by = $req->user;

                    $picklistHist->pl_wo_nbr = $WO->wo_nbr;
                    $picklistHist->pl_wo_id = $WO->wo_id;
                    $picklistHist->pl_wo_site = $WO->wo_site;
                    $picklistHist->pl_wo_status = $WO->wo_status;
                    $picklistHist->pl_wo_part = $WO->wo_part;
                    $picklistHist->pl_wo_part_desc = $WO->wo_part_desc;
                    $picklistHist->pl_wo_qty_ord = $WO->wo_qty_ord;
                    $picklistHist->pl_wo_qty_comp = $WO->wo_qty_comp;
                    $picklistHist->pl_wo_qty_rjct = $WO->wo_qty_rjct;
                    $picklistHist->pl_wo_order_date = $WO->wo_order_date;
                    $picklistHist->pl_wo_release_date = $WO->wo_release_date;
                    $picklistHist->pl_wo_due_date = $WO->wo_due_date;
                    $picklistHist->created_by = $req->user;

                    $picklistHist->pl_wod_nbr = $data->wod_nbr;
                    $picklistHist->pl_wod_op = $data->wod_op;
                    $picklistHist->pl_wod_part = $data->wod_part;
                    $picklistHist->pl_wod_part_desc = $data->wod_part_desc;
                    $picklistHist->pl_wod_um = $data->wod_um;
                    $picklistHist->pl_wod_site = $data->wod_site;
                    $picklistHist->pl_wod_loc = $data->wod_loc;
                    $picklistHist->pl_wod_lot = $data->wod_lot;
                    $picklistHist->pl_wod_ref = $data->wod_ref;
                    $picklistHist->pl_wod_warehouse = $data->wod_warehouse;
                    $picklistHist->pl_wod_bin = $data->wod_bin;
                    $picklistHist->pl_wod_level = $data->wod_level;
                    $picklistHist->pl_wod_qty_req = $data->wod_qty_req;
                    $picklistHist->pl_wod_qty_pick = $data->wod_qty_pick;
                    $picklistHist->pl_wod_qty_oh = $data->wod_qty_oh;
                    $picklistHist->pl_wod_qty_pick_inv = $data->wod_qty_pick_inv;
                    $picklistHist->pl_wod_entry_date = $data->wod_entry_date;
                    $picklistHist->pl_wod_exp_date = $data->wod_exp_date;
                    $picklistHist->pl_wod_picklist_type = $data->wod_picklist_type;
                    $picklistHist->save();
                
            }

            foreach ($dataWo as $wo) {
                $wo->getDetail()->delete(); // delete related details
                $wo->delete();              // delete master
            }
            DB::commit();
            return response()->json(['success', 200]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request: " . $e->getMessage()
            ], 422);
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request"
            ], 422);
        }
    }

    public function deleteDataWo(Request $req)
    {
        $dataWo = workOrderMaster::with('getDetail')->where('id', $req->search)->where('created_by', $req->user)->first();
        if (!$dataWo) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        }
        try {
            DB::beginTransaction();
            $dataWo->getDetail()->delete();
            $dataWo->delete();
            DB::commit();
            return response()->json(['success', 200]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request: " . $e->getMessage()
            ], 422);
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request"
            ], 422);
        }
    }

    public function saveQtyWo(Request $req)
    {
        $woDetail = workOrderDetail::where('id', $req->id)->first();

        if (!$woDetail) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        }
        try {
            DB::beginTransaction();
            $woDetail->wod_qty_pick = $req->qtypick;
            $woDetail->save();

            DB::commit();
            return response()->json(['success', 200]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request: " . $e->getMessage()
            ], 422);
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request"
            ], 422);
        }
    }

    public function getDataPicklist(Request $req)
    {
        $data = picklistMstr::query()->with(['getWo', 'getWo.getDetail'])->where('created_by', $req->user);
        if ($req->search) {
            $data->where('pl_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhere('pl_wo_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhere('pl_wo_part', 'LIKE', '%' . $req->search . '%')
                ->orWhere('pl_wo_part_desc', 'LIKE', '%' . $req->search . '%');
        }

        $data = $data->orderBy('id', 'desc')->get();


        return GeneralResources::collection($data);
    }
    public function getDataPicklistDetail(Request $req)
    {
        $data = picklistMstr::query()->with(['getWo', 'getWo.getDetail'])->where('id', $req->id);
        if ($req->search) {
            $data->where('pl_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhere('pl_wo_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhere('pl_wo_part', 'LIKE', '%' . $req->search . '%')
                ->orWhere('pl_wo_part_desc', 'LIKE', '%' . $req->search . '%');
        }

        $data = $data->orderBy('id', 'desc')->get();


        return GeneralResources::collection($data);
    }

    public function insertDataWoMstr(Request $req)
    {

        $woidnbr = '';

        $hasil = (new WSAServices())->wsaGetWODetail($req->search);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
            
            try {
                DB::beginTransaction();
                foreach ($listData as $data) {
                    if ($woidnbr != ((string)$data->t_wo_nbr . (string)$data->t_wo_id)) {
                        $woidnbr = (string)$data->t_wo_nbr . (string)$data->t_wo_id;
                        $dataMaster = workOrderMaster::firstOrNew(
                            [
                                'wo_nbr' => (string)$data->t_wo_nbr,
                                'wo_id' => (string)$data->t_wo_id,
                                'created_by' => (string)$req->user
                            ]
                        );
                        $dataMaster->wo_site = (string)$data->t_wo_site;
                        $dataMaster->wo_part = (string)$data->t_wo_part;
                        $dataMaster->wo_part_desc = (string)$data->t_wo_part_desc;
                        $dataMaster->wo_status = (string)$data->t_wo_status ?? '';
                        $dataMaster->wo_qty_ord = (string)$data->t_wo_qty_ord ?? 0;
                        $dataMaster->wo_qty_comp = (string)$data->t_wo_qty_comp ?? 0;
                        $dataMaster->wo_qty_rjct = (string)$data->t_wo_qty_rjct ?? 0;
                        $dataMaster->wo_order_date = (string)$data->t_wo_ord_date;
                        $dataMaster->wo_release_date = (string)$data->t_wo_rel_date;
                        $dataMaster->wo_due_date = (string)$data->t_wo_due_date;
                        $dataMaster->wo_um = (string)$data->t_wo_um ?? '';
                        $dataMaster->created_by = $req->user;
                        $dataMaster->save();
                    }
                    /*
                    $checkDetail = workOrderDetail::where('wod_wo_id', $dataMaster->id)->where('wod_part', (string)$data->t_wod_part)->first();
                    if (!$checkDetail) {
                        $dataDetail = new workOrderDetail();
                        $dataDetail->wod_wo_id = $dataMaster->id;
                        $dataDetail->wod_nbr = (string)$data->t_wod_nbr;
                        $dataDetail->wod_op = (string)$data->t_wod_op;
                        $dataDetail->wod_part = (string)$data->t_wod_part;
                        $dataDetail->wod_part_desc = (string)$data->t_wod_part_desc;
                        $dataDetail->wod_um = (string)$data->t_wod_um;
                        $dataDetail->wod_site = (string)$data->t_wod_site;
                        $dataDetail->wod_loc = (string)$data->t_wod_loc;
                        $dataDetail->wod_qty_req = (string)$data->t_wod_qty_req ?? 0;
                        $dataDetail->wod_qty_pick = 0;
                        $dataDetail->save();
                    }
                    */
                }


                DB::commit();
                return response()->json(['success', 200]);
            } catch (Exception $e) {
                DB::rollBack();
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Unable to Proccess Request: " . $e->getMessage()
                ], 422);
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Unable to Proccess Request"
                ], 422);
            }
        }
    }

    public function insertDataWoDetail(Request $req)
    {

        $woidnbr = '';
        $workOrder = workOrderMaster::where('created_by', $req->user)->get();
        foreach ($workOrder as $dataMaster) {
            $hasil = (new WSAServices())->wsaGetWODetail($dataMaster->wo_nbr);
            if ($hasil[0] == 'false') {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Work Order : " . $req->search . " Not Found."
                ], 422);
            } else {
                $listData = $hasil[1];
                try {
                    DB::beginTransaction();
                    foreach ($listData as $data){
                        $deleteDetail = workOrderDetail::where('wod_wo_id', $dataMaster->id)->where('wod_part', (string)$data->t_wod_part)->delete();
                        $checkDetail = workOrderDetail::where('wod_wo_id', $dataMaster->id)->where('wod_part', (string)$data->t_wod_part)->first();
                        if (!$checkDetail) {
                            $dataDetail = new workOrderDetail();
                            $dataDetail->wod_wo_id = $dataMaster->id;
                            $dataDetail->wod_nbr = (string)$data->t_wod_nbr;
                            $dataDetail->wod_op = (string)$data->t_wod_op;
                            $dataDetail->wod_part = (string)$data->t_wod_part;
                            $dataDetail->wod_part_desc = (string)$data->t_wod_part_desc;
                            $dataDetail->wod_um = (string)$data->t_wod_um;
                            $dataDetail->wod_site = (string)$data->t_wod_site;
                            $dataDetail->wod_loc = (string)$data->t_wod_loc;
                            $dataDetail->wod_qty_req = (string)$data->t_wod_qty_req ?? 0;
                            $dataDetail->wod_qty_pick = 0;
                            
                            $dataDetail->save();
                        }
                    }
                   
                    DB::commit();
                    return response()->json(['success', 200]);
                } catch (Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Unable to Proccess Request: " . $e->getMessage()
                    ], 422);
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => "Unable to Proccess Request"
                    ], 422);
                }
            }
        }
    }

        public function getDataItemWo(Request $req)
    {

        
        $hasil = (new WSAServices())->wsaGetItemMstrWo();

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        } else {
            $listData = $hasil[1];
            $dataWO = [];
            foreach ($listData as $listDatas) {
                

                $dataItem[] = [

                    
                    'part' => (string)$listDatas->t_part,
                    //'partdesc' => (string)$listDatas->t_part_desc,
                    //'site' => (string)$listDatas->t_site,
                    //'um' => (string)$listDatas->t_um ,
                    //'loc' => (string)$listDatas->t_loc,
                ];
            }
            return response()->json([
                'DataWSA' => $hasil[1]
            ], 200);
           
        }
    }
}
