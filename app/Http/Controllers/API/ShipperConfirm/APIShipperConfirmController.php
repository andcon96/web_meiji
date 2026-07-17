<?php

namespace App\Http\Controllers\API\ShipperConfirm;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ShipperConfirm\ShipperConfirm;
use App\Models\Settings\qxwsa;
use App\Services\ConfirmShipmentServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class APIShipperConfirmController extends Controller
{
    public function index(Request $request)
    {
        $data = ShipperConfirm::query()
            ->with(['getPackingReplenishmentMaster.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', 'getCreatedBy:id,name,username'])
            ->where('sc_user_approver', 'LIKE', '%' . Auth::user()->id . '%');

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {

                $q->whereHas('getPackingReplenishmentMaster', function ($subq) use ($filter) {
                    $subq->where('prm_shipper_nbr', 'LIKE', '%' . $filter . '%')->where('prm_status', 'Shipper Created');
                })


                    ->orWhereHas('getPackingReplenishmentMaster.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', function ($q) use ($filter) {
                        $q->where('ssm_cust_code', 'LIKE', '%' . $filter . '%')->orWhere('ssm_cust_desc', 'LIKE', '%' . $filter . '%');
                    })


                    ->orWhereHas('getPackingReplenishmentMaster.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet', function ($q) use ($filter) {
                        $q->where('ssd_sod_part', 'LIKE', '%' . $filter . '%');
                    });
            });
        }

        $data = $data->where('sc_status', 'Waiting for confirmation')->orderBy('created_at', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function store(Request $request)
    {
        Log::info('REQUEST', $request->all());
        $shipperApproval = $request['shipperPayload'];
        $reason = $request['reason'];
        $activeConnection = qxwsa::first();

        $confirmServices = new ConfirmShipmentServices();
        $saveData = $confirmServices->confirmShipment($request, $shipperApproval, $reason, $activeConnection);

        if ($saveData == false) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Failed To Approve Shipment.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Shipment has been approved',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }
}
