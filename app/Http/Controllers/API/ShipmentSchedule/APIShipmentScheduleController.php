<?php

namespace App\Http\Controllers\API\ShipmentSchedule;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\Settings\qxwsa;
use App\Services\ShipmentScheduleServices;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APIShipmentScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $data = ShipmentScheduleMstr::withCount("packingReplenishmentDet")->with(["packingReplenishmentDet"]);

        if ($req->search) {
            $data->where(function ($query) use ($req) {
                $query
                    ->where("ssm_number", "LIKE", "%" . $req->search . "%")
                    ->orWhere("ssm_cust_code", "LIKE", "%" . $req->search . "%")
                    ->orWhere("ssm_cust_desc", "LIKE", "%" . $req->search . "%")
                    ->orWhere("ssm_status", "LIKE", "%" . $req->search . "%");
            });
        }

        $data = $data->orderBy("ssm_number", "desc")->paginate(10);

        return GeneralResources::collection($data);
    }

    public function wsaCustomer()
    {
        $activeConnection = qxwsa::first();
        $wsaServices = new WSAServices();
        $customerData = $wsaServices->wsaCustomer($activeConnection);

        if ($customerData[0] == "false") {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "No customer data found.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "customerData" => $customerData[1],
            ],
            200,
        );
    }

    public function wsaSalesOrder(Request $request)
    {
        $customer = $request->search;

        $activeConnection = qxwsa::first();
        $wsaServices = new WSAServices();
        $salesOrderData = $wsaServices->wsaSalesOrder($customer, $activeConnection);

        if ($salesOrderData[0] == "false") {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "No sales order data found.",
                ],
                422,
            );
        }

        $tempData = [];

        foreach ($salesOrderData[1] as $data) {
            if ((string) $data->t_so_open_qty > 0) {
                // Ambil qty pick
                $pickedQty = 0;
                $salesOrderDetail = ShipmentScheduleDet::where("ssd_sod_site", (string) $data->t_so_site)
                    ->where("ssd_sod_nbr", (string) $data->t_so_nbr)
                    ->where("ssd_sod_line", (string) $data->t_so_line)
                    ->first();

                if ($salesOrderDetail) {
                    $pickedQty = $salesOrderDetail->ssd_sod_qty_pick;
                }

                array_push($tempData, [
                    "t_so_nbr" => (string) $data->t_so_nbr,
                    "t_so_site" => (string) $data->t_so_site,
                    "t_so_ship" => (string) $data->t_so_ship,
                    "t_so_line" => (string) $data->t_so_line,
                    "t_so_part" => (string) $data->t_so_part,
                    "t_so_part_desc" => (string) $data->t_so_part_desc,
                    "t_so_um" => (string) $data->t_so_um,
                    "t_so_ord_qty" => (string) $data->t_so_ord_qty,
                    "t_so_open_qty" => (string) $data->t_so_open_qty,
                    "t_so_pick_qty" => (string) $pickedQty,
                    "t_so_serial" => (string) $data->t_so_serial,
                ]);
            }
        }

        return response()->json(
            [
                "salesOrderData" => $tempData,
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function wsaInventoryDetail(Request $request)
    {
        $searchData = $request->search;
        // return response()->json(['data' => $searchData, 200, ['Content-Type' => 'application/json']], JSON_UNESCAPED_UNICODE);

        // split by "|"
        $parts = explode("|", $searchData);
        Log::channel("shipmentSchedule")->info(json_encode($parts));

        // make sure we always get both values
        $site = $parts[0] ?? "";
        $itemCode = $parts[1] ?? "";
        $lot = $parts[2] == null || $parts[2] == "null" ? "" : $parts[2];

        $activeConnection = qxwsa::first();
        $wsaServices = new WSAServices();
        $wsaInventory = $wsaServices->wsaInventoryDetail($site, $itemCode, $lot, $activeConnection);

        if ($wsaInventory[0] == "false") {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "No inventory data found.",
                ],
                422,
            );
        }

        $tempData = [];

        foreach ($wsaInventory[1] as $data) {
            array_push($tempData, [
                "t_inv_part" => (string) $data->t_inv_part,
                "t_inv_loc" => (string) $data->t_inv_loc,
                "t_inv_lot" => (string) $data->t_inv_lot,
                "t_inv_bin" => (string) $data->t_inv_bin,
                "t_inv_level" => (string) $data->t_inv_level,
                "t_inv_site" => (string) $data->t_inv_site,
                "t_inv_wrh" => (string) $data->t_inv_wrh,
                "t_inv_qtyoh" => (string) $data->t_inv_qtyoh,
                "t_inv_uom" => (string) $data->t_inv_uom,
            ]);
        }

        return response()->json(
            [
                "inventoryData" => $tempData,
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function store(Request $request)
    {
        // Log::channel('shipmentSchedule')->info(json_encode($request->all()));

        $customerCode = $request->customer_id;
        $customerName = $request->customer_desc;
        $salesOrders = $request->sales_orders;

        $shipmentScheduleServices = new ShipmentScheduleServices();
    $saveData = $shipmentScheduleServices->saveShipmentSchedule($customerCode, $customerName, $salesOrders);

        if ($saveData == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To Save Shipment Schedule.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Shipment schedule has been created",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function delete(Request $request)
    {
        $id = $request->id;

        // Ambil data master, loop ke detail, loop ke lokasi, sebelum hapus masukin ke history, terakhir delete
        $shipmentScheduleMstr = ShipmentScheduleMstr::with(["getShipmentScheduleDetail.getShipmentScheduleLocation"])->find($id);

        if (!$shipmentScheduleMstr) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Data not found",
                ],
                422,
                ["Content-Type" => "application/json"],
                JSON_UNESCAPED_UNICODE,
            );
        }

        $shipmentScheduleServices = new ShipmentScheduleServices();
        $deleteData = $shipmentScheduleServices->deleteShipmentSchedule($shipmentScheduleMstr);

        if ($deleteData == false) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Failed to delete shipment schedule",
                ],
                422,
                ["Content-Type" => "application/json"],
                JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Shipment schedule has been deleted",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function edit($id)
    {
        $shipmentSchedule = ShipmentScheduleMstr::with(["getShipmentScheduleDetail.getShipmentScheduleLocation"])->find($id);

        if (!$shipmentSchedule) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Failed to fetch shipment schedule data",
                ],
                422,
                ["Content-Type" => "application/json"],
                JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "shipmentScheduleData" => $shipmentSchedule,
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function update(Request $request, $id)
    {
        // Log::channel("shipmentSchedule")->info(json_encode($request->all()));

        $idShipmentScheduleMstr = $id;
        $salesOrders = $request->sales_orders;

        $shipmentScheduleServices = new ShipmentScheduleServices();
        $updateData = $shipmentScheduleServices->updateShipmentSchedule($idShipmentScheduleMstr, $salesOrders);

        if ($updateData == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To Update Shipment Schedule.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Shipment schedule has been created",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }
    //   public function deleteDraft(Request $req)
    // {

    //     DB::beginTransaction();
    //     try {
    //         $id = $req->id;
    //         $data ShipmentScheduleMstr::with(['ShipmentScheduleDet'])
    //         $data = ReceiptDetail::with([
    //             'getMaster',
    //             'getPurchaseOrderDetail.getMaster',
    //             'getPallet',
    //             'getAttachment',
    //             'getDokumen',
    //             'getKemasan',
    //             'getKendaraan',
    //             'getPenanda',

    //             'getUserSeenBy',
    //             'getApprovalTemp',
    //             'getApprovalHist'
    //         ])->findOrFail($id);
    //         $master = ReceiptMaster::with('getPurchaseOrderMaster.getDetail')->findOrFail($data->rd_rm_id);
    //         $getPurchaseOrderDetail = $data->getPurchaseOrderDetail;
    //         $getPallet = $data->getPallet;
    //         foreach ($getPallet as $plt) {


    //             $newTransactionHistory = new TransactionHistory();
    //             $newTransactionHistory->tr_nbr = $data->getMaster->rm_rn_number;
    //             $newTransactionHistory->tr_order = $getPurchaseOrderDetail->getMaster->po_nbr;
    //             $newTransactionHistory->tr_program = 'PO Approval Module';
    //             $newTransactionHistory->tr_activity = 'Delete Receipt';
    //             $newTransactionHistory->tr_user =  Auth::user()->username ?? '';
    //             // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
    //             $newTransactionHistory->tr_part = $getPurchaseOrderDetail->pod_part ?? '';
    //             $newTransactionHistory->tr_uom = $data->rd_pt_um ?? '';
    //             $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
    //             $newTransactionHistory->tr_lot = $data->rd_batch ?? '';
    //             $newTransactionHistory->tr_qty = $data->rd_qty_terima ?? '';
    //             $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
    //             $newTransactionHistory->tr_reference = $data->rd_kode_cetak ?? '';
    //             $newTransactionHistory->tr_site = $data->rd_site_penyimpanan ?? '';
    //             $newTransactionHistory->tr_location = $data->rd_location_penyimpanan ?? '';
    //             $newTransactionHistory->tr_warehouse = $data->rd_building_penyimpanan ?? '';
    //             $newTransactionHistory->tr_level = $plt->rdp_level_penyimpanan ?? '';
    //             $newTransactionHistory->tr_bin = $plt->rdp_bin_penyimpanan ?? '';
    //             $newTransactionHistory->tr_remark = '';
    //             $newTransactionHistory->save();
    //         }

    //         $allDetails = ReceiptDetail::where('rd_rm_id', $master->id)->get();

    //         foreach ($allDetails as $detail) {
    //             $poDetail = PurchaseOrderDetail::find($detail->rd_pod_det_id);
    //             $poDetail->pod_qty_rcpt = $poDetail->pod_qty_rcpt - $data->rd_qty_terima;
    //             $poDetail->save();
    //             $detail->getAttachment()->delete();
    //             $detail->getDokumen()->delete();
    //             $detail->getKemasan()->delete();
    //             $detail->getKendaraan()->delete();
    //             $detail->getPenanda()->delete();
    //             $detail->getPallet()->delete();
    //             $detail->getUserSeenBy()->delete();
    //             $detail->getApprovalTemp()->delete();
    //             $detail->getApprovalHist()->delete();
    //             $detail->delete(); // delete this detail after all its children
    //         }

    //         $master->delete(); // delete master only after all details are gone
    //         // // Delete all related records using query builder (more efficient)
    //         // $data->getAttachment()->delete();
    //         // $data->getDokumen()->delete();
    //         // $data->getKemasan()->delete();
    //         // $data->getKendaraan()->delete();
    //         // $data->getPenanda()->delete();
    //         // $data->getPallet()->delete();
    //         // $data->getUserSeenBy()->delete();
    //         // $data->getApprovalTemp()->delete();
    //         // $data->getApprovalHist()->delete();


    //         // // Delete the main record
    //         // $data->delete();
    //         // $master->delete();

    //         DB::commit();

    //         return response()->json([
    //             'Status' => 'Success',
    //             'Message' => "Data deleted successfully"
    //         ], 200);
    //     } catch (Exception $err) {
    //         DB::rollback();
    //         Log::error($err);
    //         return response()->json([
    //             'Status' => 'Error',
    //             'Message' => "Failed to delete data"
    //         ], 422);
    //     }
    // }

}
