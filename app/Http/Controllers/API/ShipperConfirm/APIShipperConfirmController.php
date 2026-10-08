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
            ->with([
                'getShipmentScheduleMaster.getShipmentScheduleDetail.shipmentScheduleLoc',
                'getCreatedBy:id,name,username',
            ])
            ->where('sc_user_approver', 'LIKE', '%' . Auth::user()->id . '%');

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {
                // Shipment number / customer
                $q->whereHas('getShipmentScheduleMaster', function ($subq) use ($filter) {
                    $subq->where(function ($w) use ($filter) {
                        $w->where('ssm_number', 'LIKE', '%' . $filter . '%')
                            ->orWhere('ssm_cust_code', 'LIKE', '%' . $filter . '%')
                            ->orWhere('ssm_cust_desc', 'LIKE', '%' . $filter . '%');
                    });
                })
                    // Item
                    ->orWhereHas('getShipmentScheduleMaster.getShipmentScheduleDetail', function ($subq) use ($filter) {
                        $subq->where('ssd_sod_part', 'LIKE', '%' . $filter . '%');
                    });
            });
        }

        $data = $data->where('sc_status', 'Waiting for confirmation')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return GeneralResources::collection($data);
    }

    public function store(Request $request)
    {
        Log::info('REQUEST', $request->all());

        $shipperApproval = $request['shipperPayload'];
        $reason = $request['reason'];
        $activeConnection = qxwsa::first();

        $confirmServices = new ConfirmShipmentServices();

        $saveData = $confirmServices->confirmShipment(
            $request,
            $shipperApproval,
            $reason,
            $activeConnection
        );

        if ($saveData !== true) {
            return response()->json([
                'Status' => 'error',
                'Message' => $saveData['message'] ?? 'Failed to confirm shipment.',
            ], 422);
        }

        return response()->json([
            'Status' => 'success',
            'Message' => 'Shipment has been approved',
        ], 200);
    }

    public function rejectShipment(Request $request)
    {
        Log::info('REQUEST', $request->all());

        $shipperApproval = $request['shipperPayload'];
        $reason = $request['reason'];
        $activeConnection = qxwsa::first();

        $confirmServices = new ConfirmShipmentServices();

        $saveData = $confirmServices->rejectShipment(
            $request,
            $shipperApproval,
            $reason,
            $activeConnection
        );

        if ($saveData !== true) {
            return response()->json([
                'Status' => 'error',
                'Message' => $saveData['message'] ?? 'Failed to reject shipment.',
            ], 422);
        }

        return response()->json([
            'Status' => 'success',
            'Message' => 'Shipment has been rejected',
        ], 200);
    }
}
