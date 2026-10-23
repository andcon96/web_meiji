<?php

namespace App\Http\Controllers\API\ShipmentSchedule;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleApproval;
use App\Models\Settings\qxwsa;
use App\Services\ShipmentScheduleServices;
use Illuminate\Support\Facades\Auth;

use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APIShipmentScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = ShipmentScheduleMstr::query()
            ->with([
                'getShipmentScheduleDetail.shipmentScheduleLoc'
            ]);

        if ($request->search) {
            $search = $request->search;

            $data->where(function ($q) use ($search) {

                $q->where('ssm_number', 'LIKE', '%' . $search . '%')
                    ->orWhere('ssm_cust_code', 'LIKE', '%' . $search . '%')
                    ->orWhere('ssm_cust_desc', 'LIKE', '%' . $search . '%')
                    ->orWhere('ssm_status', 'LIKE', '%' . $search . '%')

                    ->orWhereHas('getShipmentScheduleDetail', function ($query) use ($search) {
                        $query->where('ssd_sod_nbr', 'LIKE', '%' . $search . '%')
                            ->orWhere('ssd_sod_part', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        $data = $data
            ->orderBy('id', 'desc')
            ->paginate(10);

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
        Log::channel('shipmentSchedule')->info('=== CREATE SHIPMENT SCHEDULE START ===');

        Log::channel('shipmentSchedule')->info('Request:', [
            'all' => $request->all(),
            'approver' => $request->approver,
            'prm_id' => $request->prm_id,
            'ssm_status' => $request->ssm_status,
            'scheduleDetail_count' => is_array($request->scheduleDetail)
                ? count($request->scheduleDetail)
                : 0,
        ]);

        try {
            $approver = $request->approver;
            $idPrm = $request->prm_id;
            $shipmentSchedule = $request->scheduleDetail;
            $status = $request->ssm_status ?? 'Waiting for approval';
            $custCode = $request->ssm_cust_code ?? null;
            $custDesc = $request->ssm_cust_desc ?? null;

            Log::channel('shipmentSchedule')->info('Before saveShipmentSchedule:', [
                'approver' => $approver,
                'idPrm' => $idPrm,
                'status' => $status,
                'custCode' => $custCode,
                'custDesc' => $custDesc,
                'shipmentSchedule' => $shipmentSchedule,
            ]);

            $shipmentScheduleServices = new ShipmentScheduleServices();

            $saveData = $shipmentScheduleServices->saveShipmentSchedule(
                $approver,
                $idPrm,
                $shipmentSchedule,
                $status,
                $custCode,
                $custDesc
            );

            Log::channel('shipmentSchedule')->info('saveShipmentSchedule result:', [
                'result' => $saveData,
            ]);

            if ($saveData == false) {
                Log::channel('shipmentSchedule')->error(
                    'saveShipmentSchedule returned FALSE'
                );

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Failed To Save Shipment Schedule.',
                ], 422);
            }

            Log::channel('shipmentSchedule')->info(
                '=== CREATE SHIPMENT SCHEDULE SUCCESS ==='
            );

            return response()->json([
                'Status' => 'Success',
                'Message' => 'Shipment Schedule Saved Successfully.',
            ], 200);
        } catch (\Exception $err) {
            Log::channel('shipmentSchedule')->error(
                '=== CREATE SHIPMENT SCHEDULE EXCEPTION ==='
            );

            Log::channel('shipmentSchedule')->error('Message:', [
                'message' => $err->getMessage(),
            ]);

            Log::channel('shipmentSchedule')->error('File:', [
                'file' => $err->getFile(),
                'line' => $err->getLine(),
            ]);

            Log::channel('shipmentSchedule')->error('Trace:', [
                'trace' => $err->getTraceAsString(),
            ]);

            return response()->json([
                'Status' => 'Error',
                'Message' => $err->getMessage(),
            ], 422);
        }
    }

    public function approve(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        try {
            $shipmentScheduleServices = new ShipmentScheduleServices();

            $approveData = $shipmentScheduleServices->approveShipment(
                $request->id,
                $request->reason
            );

            if ($approveData === false) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Failed to approve shipment schedule.',
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Shipment schedule has been approved.',
            ], 200);
        } catch (\Exception $err) {
            Log::channel('shipmentSchedule')->error($err);

            return response()->json([
                'status' => 'Error',
                'message' => $err->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'reason' => 'required|string',
        ]);

        try {
            $shipmentScheduleServices = new ShipmentScheduleServices();

            $rejectData = $shipmentScheduleServices->rejectShipment(
                $request->id,
                $request->reason
            );

            if ($rejectData === false) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Failed to reject shipment schedule.',
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Shipment schedule has been rejected.',
            ], 200);
        } catch (\Exception $err) {
            Log::channel('shipmentSchedule')->error($err);

            return response()->json([
                'status' => 'Error',
                'message' => $err->getMessage(),
            ], 422);
        }
    }

    public function getShipmentScheduleApprovalList(Request $request)
    {
        $data = ShipmentScheduleApproval::query()
            ->with([
                'master.getShipmentScheduleDetail.shipmentScheduleLoc',
                'creator:id,name,username',
            ])
            ->where('ssa_status', 'Waiting for confirmation');

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {
                $q->whereHas('master', function ($subq) use ($filter) {
                    $subq->where('ssm_number', 'LIKE', '%' . $filter . '%')
                        ->orWhere('ssm_cust_code', 'LIKE', '%' . $filter . '%')
                        ->orWhere('ssm_cust_desc', 'LIKE', '%' . $filter . '%');
                })
                    ->orWhereHas('master.getShipmentScheduleDetail', function ($subq) use ($filter) {
                        $subq->where('ssd_sod_nbr', 'LIKE', '%' . $filter . '%')
                            ->orWhere('ssd_sod_part', 'LIKE', '%' . $filter . '%');
                    });
            });
        }

        return GeneralResources::collection(
            $data->orderBy('created_at', 'desc')->paginate(10)
        );
    }

    public function edit($id)
    {

        $part = ShipmentScheduleMstr::find($id)?->getShipmentScheduleDetail[0]?->ssd_sod_part;


        $shipmentSchedule = ShipmentScheduleMstr::with([
            'getShipmentScheduleDetail.shipmentScheduleLoc'
        ])->find($id);
        $shipmentSchedule = ShipmentScheduleMstr::with([
            'getShipmentScheduleDetail' => function ($query) use ($part){
                $query->with([
                    'shipmentScheduleLoc' => function ($q) use ($part){
                        $q->join('xxinv_det', function ($join) {
                            $join->on('shipment_schedule_location.ssl_site', '=', 'xxinv_det.xxinv_site')
                                ->on('shipment_schedule_location.ssl_lotserial', '=', 'xxinv_det.xxinv_lot')
                                ->on('shipment_schedule_location.ssl_warehouse', '=', 'xxinv_det.xxinv_wrh')
                                ->on('shipment_schedule_location.ssl_level', '=', 'xxinv_det.xxinv_level')
                                ->on('shipment_schedule_location.ssl_bin', '=', 'xxinv_det.xxinv_bin');
                        })
                            ->where('xxinv_det.xxinv_part', $part)
                            ->select('shipment_schedule_location.*', 'xxinv_det.*','xxinv_det.xxinv_qty_wrh as stock');
                    }
                ]);
            }
        ])->find($id);
        if (!$shipmentSchedule) {
            return response()->json([
                "status"  => "Error",
                "message" => "Failed to fetch shipment schedule data"
            ], 422, ["Content-Type" => "application/json"], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            "status"               => "success",
            "shipmentScheduleData" => $shipmentSchedule,
        ], 200, ["Content-Type" => "application/json"], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'prm_id' => 'required',
            'scheduleDetail' => 'required|array',
            'ssm_status' => 'required|in:Draft,Waiting for approval',
            'approver' => 'nullable',
        ]);

        if (
            $request->ssm_status === 'Waiting for approval' &&
            empty($request->approver)
        ) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Approver wajib dipilih.',
            ], 422);
        }

        try {
            $shipmentScheduleServices = new ShipmentScheduleServices();

            $updateData = $shipmentScheduleServices->updateShipmentSchedule(
                $request->approver,
                $id,
                $request->scheduleDetail,
                $request->ssm_status,
                $request->ssm_cust_code ?? null,
                $request->ssm_cust_desc ?? null
            );

            if ($updateData === false) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Failed to update shipment schedule.',
                ], 422);
            }

            $message = $request->ssm_status === 'Draft'
                ? 'Shipment schedule draft has been saved.'
                : 'Shipment schedule has been submitted for approval.';

            return response()->json([
                'status' => 'success',
                'message' => $message,
            ], 200);
        } catch (\Exception $err) {
            Log::channel('shipmentSchedule')->error($err);

            return response()->json([
                'status' => 'Error',
                'message' => $err->getMessage(),
            ], 422);
        }
    }
    public function deleteDraftShipment(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        try {
            $service = new ShipmentScheduleServices();
            $result = $service->deleteDraftShipment($request->id);

            if (!$result) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Gagal menghapus draft shipment.'
                ], 422);
            }

            return response()->json([
                'status' => 'Success',
                'message' => 'Draft berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
