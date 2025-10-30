<?php

namespace App\Http\Controllers\API\OtherShipmentPreparation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationMstr;
use App\Http\Resources\GeneralResources;
use App\Models\Settings\qxwsa;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleMstr;
use App\Services\OtherShipmentPreparationServices;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Settings\Role;
use App\Models\Settings\User;

class APIOtherShipmentPreparationController extends Controller
{
    public function index(Request $request)
    {
        $data = OtherShipmentPreparationMstr::query()->with([
            "getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster",
            "getCreatedBy:id,name,username",
        ]);

        if ($request->search) {
            $search = $request->search;

            $data->where(function ($q) use ($search) {
                // cari customer
                $q->where("ospm_number", "LIKE", "%" . $search . "%")

                    // cari customer
                    ->orWhereHas(
                        "getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster",
                        function ($query) use ($search) {
                            $query
                                ->where("ossm_cust_code", "LIKE", "%" . $search . "%")
                                ->orWhere("ossm_cust_desc", "LIKE", "%" . $search . "%");
                        },
                    )

                    // cari SO + item code
                    ->orWhereHas("getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet", function (
                        $query,
                    ) use ($search) {
                        $query->where("ossd_part", "LIKE", "%" . $search . "%");
                    });
            });
        }

        $data = $data->orderBy("id", "desc")->paginate(10);

        return GeneralResources::collection($data);
    }

    public function listOtherShipmentSchedule()
    {
        $listOtherShipmentSchedule = OtherShipmentScheduleMstr::whereDoesntHave(
            "getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation.getOtherShipmentPreparationDet",
        )
            ->with(["getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation"])
            ->orderBy("ossm_number", "desc")
            ->get();

        if ($listOtherShipmentSchedule->count() == 0) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "No Other Shipment Schedule found.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "listOtherShipmentSchedule" => $listOtherShipmentSchedule,
            ],
            200,
        );
    }

    public function store(Request $request)
    {
        // Log::channel("otherShipmentPreparation")->info(json_encode($request->all()));

        $approver = $request->approver;
        $idOssm = $request->ossm_id;
        $otherShipmentPreparation = $request->otherScheduleDetail;

        $activeConnection = qxwsa::first();

        $otherShipmentPreparationService = new OtherShipmentPreparationServices();
        $saveData = $otherShipmentPreparationService->saveOtherShipmentPreparation(
            $approver,
            $idOssm,
            $otherShipmentPreparation,
            $activeConnection,
        );

        if ($saveData == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To Save Other Shipment Preparation.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Other Shipment Preparation has been created",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approverListShipmentPreparation()
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

    public function rejectShipmentPreparation(Request $request)
    {
        // Log::channel("otherShipmentPreparation")->info(json_encode($request->all()));
        $otherShipmentPreparation = $request->shipperPayload;
        $reason = $request->reason;
        $otherShipmentScheduleNumber = $request->shipmentScheduleNumber;
        $otherShipmentPreparationServices = new OtherShipmentPreparationServices();
        $rejectOtherShipmentPreparation = $otherShipmentPreparationServices->rejectOtherShipmentPreparation(
            $otherShipmentPreparation,
            $reason,
            $otherShipmentScheduleNumber,
        );

        if ($rejectOtherShipmentPreparation == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To reject other shipment preparation.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Other Shipment preparation has been rejected",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approveShipmentPreparation(Request $request)
    {
        Log::channel("otherShipmentPreparation")->info(json_encode($request->all()));

        $shipmentPreparation = $request->shipperPayload;
        $reason = $request->reason;
        $otherShipmentScheduleNumber = $request->shipmentScheduleNumber;
        $activeConnection = qxwsa::first();

        $otherShipmentPreparationServices = new OtherShipmentPreparationServices();
        $approveShipmentPreparation = $otherShipmentPreparationServices->approveOtherShipmentPreparation(
            $shipmentPreparation,
            $reason,
            $otherShipmentScheduleNumber,
            $activeConnection,
        );

        if ($approveShipmentPreparation == false) {
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

    public function editShipmentPreparation($id)
    {
        $shipmentPreparation = OtherShipmentPreparationMstr::with([
            "getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster",
        ])->find($id);

        if (!$shipmentPreparation) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Failed to fetch other shipment preparation data",
                ],
                422,
                ["Content-Type" => "application/json"],
                JSON_UNESCAPED_UNICODE,
            );
        }

        $otherShipmentScheduleDet =
            $shipmentPreparation->getOtherShipmentPreparationDet[0]->getOtherShipmentScheduleLocation->getOtherShipmentScheduleDet;

        return response()->json(
            [
                "status" => "success",
                "otherShipmentPreparationData" => $shipmentPreparation,
                "otherShipmentScheduleData" => $otherShipmentScheduleDet,
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function getOtherShipmentPreparationApprovalList(Request $request)
    {
        $data = OtherShipmentPreparationApproval::query()
            ->with([
                "getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster",
                "getCreatedBy:id,name,username",
            ])
            ->where("ospa_user_approver", "LIKE", "%" . Auth::user()->id . "%");

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {
                // Cari shipper number
                $q->whereHas("getOtherShipmentPreparationMstr", function ($subq) use ($filter) {
                    $subq->where("ospm_number", "LIKE", "%" . $filter . "%")->where("ospm_status", "Shipper Created");
                })

                    // Cari customer
                    ->orWhereHas(
                        "getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster",
                        function ($q) use ($filter) {
                            $q->where("ossm_cust_code", "LIKE", "%" . $filter . "%")->orWhere(
                                "ossm_cust_desc",
                                "LIKE",
                                "%" . $filter . "%",
                            );
                        },
                    )

                    // cari SO + item code
                    ->orWhereHas(
                        "getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster",
                        function ($q) use ($filter) {
                            $q->where("ossd_part", "LIKE", "%" . $filter . "%");
                        },
                    );
            });
        }

        $data = $data->where("ospa_status", "Waiting for confirmation")->orderBy("created_at", "desc")->paginate(10);

        return GeneralResources::collection($data);
    }
}
