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
        $idOssm = $request->ossmId;
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

    public function approverListShipmentPreparation() {}

    public function rejectShipmentPreparation() {}

    public function approveShipmentPreparation() {}

    public function editShipmentPreparation() {}

    public function getShipmentPreparationApprovalList(Request $request)
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
