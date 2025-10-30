<?php

namespace App\Http\Controllers\API\OtherShipmentSchedule;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleMstr;
use Illuminate\Http\Request;
use App\Models\Settings\Item;
use App\Services\WSAServices;
use App\Models\Settings\qxwsa;
use Illuminate\Support\Facades\Log;
use App\Services\OtherShipmentScheduleServices;

class APIOtherShipmentScheduleController extends Controller
{
    public function index(Request $req)
    {
        $data = OtherShipmentScheduleMstr::query();

        if ($req->search) {
            $data->where(function ($query) use ($req) {
                $query
                    ->where("ossm_number", "LIKE", "%" . $req->search . "%")
                    ->orWhere("ossm_cust_code", "LIKE", "%" . $req->search . "%")
                    ->orWhere("ossm_cust_desc", "LIKE", "%" . $req->search . "%")
                    ->orWhere("ossm_status", "LIKE", "%" . $req->search . "%");
            });
        }

        $data = $data->orderBy("ossm_number", "desc")->paginate(10);

        return GeneralResources::collection($data);
    }

    public function getItemOSS(Request $request)
    {
        $items = Item::with(["getLoadedBy:id,name", "getUpdatedBy:id,name"])
            ->orderBy("im_item_part")
            ->get();

        return response()->json(
            [
                "items" => $items,
            ],
            200,
        );
    }

    public function getLocationByPart(Request $request)
    {
        $item = $request->search;
        // return response()->json(['data' => $searchData, 200, ['Content-Type' => 'application/json']], JSON_UNESCAPED_UNICODE);

        Log::channel("otherShipmentSchedule")->info(json_encode($item));

        // make sure we always get both values
        $site = "";
        $itemCode = $item;
        $lot = "";

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
        // Log::channel("otherShipmentSchedule")->info(json_encode($request->all()));

        $customerCode = $request->customer_id;
        $customerDesc = $request->customer_desc;
        $items = $request->items;

        $otherShipmentScheduleServices = new OtherShipmentScheduleServices();
        $saveData = $otherShipmentScheduleServices->saveOtherShipmentSchedule($customerCode, $customerDesc, $items);

        if ($saveData == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To Save Other Shipment Schedule.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Other Shipment schedule has been created",
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
        $otherShipmentScheduleMstr = OtherShipmentScheduleMstr::with([
            "getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation",
        ])->find($id);

        if (!$otherShipmentScheduleMstr) {
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

        $otherShipmentScheduleServices = new OtherShipmentScheduleServices();
        $deleteData = $otherShipmentScheduleServices->deleteOtherShipmentSchedule($otherShipmentScheduleMstr);

        if ($deleteData == false) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Failed to delete other shipment schedule",
                ],
                422,
                ["Content-Type" => "application/json"],
                JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Other shipment schedule has been deleted",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function edit($id)
    {
        $otherShipmentSchedule = OtherShipmentScheduleMstr::with(["getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation"])->find(
            $id,
        );

        if (!$otherShipmentSchedule) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Failed to fetch other shipment schedule data",
                ],
                422,
                ["Content-Type" => "application/json"],
                JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "otherShipmentScheduleData" => $otherShipmentSchedule,
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function update(Request $request, $id)
    {
        Log::channel("otherShipmentSchedule")->info(json_encode($request->all()));

        $idOtherShipmentScheduleMstr = $id;
        $items = $request->items;

        $otherShipmentScheduleServices = new OtherShipmentScheduleServices();
        $updateData = $otherShipmentScheduleServices->updateOtherShipmentSchedule($idOtherShipmentScheduleMstr, $items);
        if ($updateData == false) {
            return response()->json(
                [
                    "Status" => "Error",
                    "Message" => "Failed To Update Other Shipment Schedule.",
                ],
                422,
            );
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Other Shipment schedule has been updated",
            ],
            200,
            ["Content-Type" => "application/json"],
            JSON_UNESCAPED_UNICODE,
        );
    }
}
