<?php

namespace App\Http\Controllers\API\ShipperConfirm;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use App\Models\Settings\qxwsa;
use App\Services\ConfirmShipmentServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class APIShipperConfirmController extends Controller
{
    public function index(Request $request)
    {
        $data = PackingReplenishmentApproval::query()->with([
            'getPackingReplenishmentMstr.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster',
            'getCreatedBy:id,name,username'
        ])->where('pra_user_approver', 'LIKE', '%' . Auth::user()->id . '%');

        if ($request->search) {
            $filter = $request->search;
            $data->whereHas('getPackingReplenishmentMstr', function ($q) use ($filter) {
                $q->where('prm_shipper_nbr', 'LIKE', '%' . $filter . '%')
                    ->where('prm_status', 'Shipper Created');
            });
        }

        $data = $data->where('pra_status', 'Waiting for confirmation')
            ->orderBy('created_at', 'desc')
            ->paginate(10);


        return GeneralResources::collection($data);
    }

    public function store(Request $request)
    {
        // Log::channel('confirmShipment')->info(json_encode($request->all()));

        $shipperApproval = $request['shipperPayload'];
        $reason = $request['reason'];
        $activeConnection = qxwsa::first();
        // dd($shipperApproval, $reason);

        $saveData = (new ConfirmShipmentServices())->confirmShipment($shipperApproval, $reason, $activeConnection);

        if ($saveData == false) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Approve Shipment."
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment has been approved',
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }
}
