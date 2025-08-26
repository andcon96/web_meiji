<?php

namespace App\Http\Controllers\API\PackingReplenishment;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\Settings\qxwsa;
use App\Services\PackingReplenishmentServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class APIPackingReplenishmentController extends Controller
{
    public function index(Request $request)
    {
        $data = PackingReplenishmentMstr::query()->with(['getCreatedBy:id,name,username']);

        if ($request->search) {
            $data->where('prm_shipper_nbr', 'LIKE', '%' . $request->search . '%');
        }

        $data = $data->orderBy('id', 'desc')->paginate(10);


        return GeneralResources::collection($data);
    }

    public function listShipmentSchedule()
    {
        $listShipmentSchedule = ShipmentScheduleMstr::whereDoesntHave('getShipmentScheduleDetail.getShipmentScheduleLocation.getPackingReplenishmentDet')
            ->with(['getShipmentScheduleDetail.getShipmentScheduleLocation'])
            ->orderBy('ssm_number', 'desc')
            ->get();


        if ($listShipmentSchedule->count() == 0) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Shipment Schedule found."
            ], 422);
        }

        return response()->json([
            'listShipmentSchedule' => $listShipmentSchedule,
        ], 200);
    }

    public function store(Request $request)
    {
        // Log::channel('packingReplenishment')->info(json_encode($request->all()));

        $packingReplenishments = $request->scheduleDetail;

        $activeConnection = qxwsa::first();

        $saveData = (new PackingReplenishmentServices())->savePackingReplenishment($packingReplenishments, $activeConnection);

        if ($saveData == false) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Save Packing Replenishment."
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Packing Replenishment has been created',
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }
}
