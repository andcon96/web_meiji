<?php

namespace App\Http\Controllers\API\PackingReplenishment;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\Settings\qxwsa;
use App\Models\Settings\Role;
use App\Models\Settings\User;
use App\Services\PackingReplenishmentServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class APIPackingReplenishmentController extends Controller
{
    public function index(Request $request)
    {
        $data = PackingReplenishmentMstr::query()->with([
            "getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster",
            "getCreatedBy:id,name,username",
        ]);

        if ($request->search) {
            $search = $request->search;

            $data->where(function ($q) use ($search) {
                // cari customer
                $q->where("prm_shipper_nbr", "LIKE", "%" . $search . "%")

                    // cari customer
                    ->orWhereHas(
                        "getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster",
                        function ($query) use ($search) {
                            $query
                                ->where("ssm_cust_code", "LIKE", "%" . $search . "%")
                                ->orWhere("ssm_cust_desc", "LIKE", "%" . $search . "%");
                        },
                    )

                    // cari SO + item code
                    ->orWhereHas("getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet", function ($query) use (
                        $search,
                    ) {
                        $query->where("ssd_sod_nbr", "LIKE", "%" . $search . "%")->orWhere("ssd_sod_part", "LIKE", "%" . $search . "%");
                    });
            });
        }

        $data = $data->orderBy("id", "desc")->paginate(10);

        return GeneralResources::collection($data);
    }

    public function listShipmentSchedule()
    {
        $listShipmentSchedule = ShipmentScheduleMstr::whereDoesntHave(
            "getShipmentScheduleDetail.getShipmentScheduleLocation.getPackingReplenishmentDet",
        )
            ->with(["getShipmentScheduleDetail.getShipmentScheduleLocation"])
            ->orderBy("ssm_number", "desc")
            ->get();

        if ($listShipmentSchedule->count() == 0) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "No Shipment Schedule found.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "listShipmentSchedule" => $listShipmentSchedule,
            ],
            200,
        );
    }

    public function store(Request $request)
    {
        // Log::channel("packingReplenishment")->info(json_encode($request->all()));

        $approver = $request->approver;
        $idPrm = $request->prm_id;
        $packingReplenishments = $request->scheduleDetail;

        $activeConnection = qxwsa::first();

        $packingReplenishmentService = new PackingReplenishmentServices();
        $saveData = $packingReplenishmentService->savePackingReplenishment($approver, $idPrm, $packingReplenishments, $activeConnection);

        if ($saveData == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To Save Packing Replenishment.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Packing Replenishment has been created",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approverList()
    {
        $role = Role::where("role_code", "SH")->first();
        $users = User::where("role_id", $role->id)
            ->where("is_active", "Active")
            ->get(["id", "name"]);

        if ($users->count() == 0) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "No users found.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "users" => $users,
            ],
            200,
        );
    }

    public function rejectPackingReplenishment(Request $request)
    {
        // Log::channel("packingReplenishment")->info(json_encode($request->all()));
        $packingReplenishment = $request->shipperPayload;
        $reason = $request->reason;
        $shipmentScheduleNumber = $request->shipmentScheduleNumber;
        $packingReplenishmentService = new PackingReplenishmentServices();
        $rejectPackingReplenishment = $packingReplenishmentService->rejectPackingReplenishment(
            $packingReplenishment,
            $reason,
            $shipmentScheduleNumber,
        );

        if ($rejectPackingReplenishment == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To reject shipment preparation.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Shipment preparation has been rejected",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approvePackingReplenishment(Request $request)
    {
        // Log::channel("packingReplenishment")->info(json_encode($request->all()));
        $packingReplenishment = $request->shipperPayload;
        $reason = $request->reason;
        $shipmentScheduleNumber = $request->shipmentScheduleNumber;
        $activeConnection = qxwsa::first();

        $packingReplenishmentService = new PackingReplenishmentServices();
        $approvePackingReplenishment = $packingReplenishmentService->approvePackingReplenishment(
            $packingReplenishment,
            $reason,
            $shipmentScheduleNumber,
            $activeConnection,
        );

        if ($approvePackingReplenishment == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed to approve shipment preparation.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Shipment preparation has been approved",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function editPackingReplenishment($id)
    {
        $packingReplenishment = PackingReplenishmentMstr::with([
            "getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster",
        ])->find($id);

        if (!$packingReplenishment) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Failed to fetch packing replenishment data",
                ],
                422,
                ["Content-Type" => "application/json"],
                JSON_UNESCAPED_UNICODE,
            );
        }

        $shipmentScheduleDet = $packingReplenishment->getPackingReplenishmentDet[0]->getShipmentScheduleLocation->getShipmentScheduleDet;

        return response()->json(
            [
                "status" => "success",
                "packingReplenishmentData" => $packingReplenishment,
                "shipmentScheduleData" => $shipmentScheduleDet,
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }
}
