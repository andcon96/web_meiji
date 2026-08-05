<?php

namespace App\Http\Controllers\API\OtherTransactionConfirm;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\OtherTransactionConfirm\OtherTransactionConfirm;
use App\Models\Settings\qxwsa;
use App\Services\ConfirmOtherTransactionServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class APIOtherTransactionConfirmController extends Controller
{
    public function index(Request $request)
    {
        $data = OtherTransactionConfirm::query()
            ->with([
                'getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster',
                'getCreatedBy:id,name,username',
            ])
            ->where('otc_user_approver', 'LIKE', '%'.Auth::user()->id.'%');

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {

                $q->whereHas('getOtherShipmentPreparationMstr', function ($subq) use ($filter) {
                    $subq->where('ospm_number', 'LIKE', '%'.$filter.'%')->where('ospm_status', 'Shipper Created');
                })

                    ->orWhereHas('getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster', function ($q) use ($filter) {
                        $q->where('ossm_cust_code', 'LIKE', '%'.$filter.'%')->orWhere('ossm_cust_desc', 'LIKE', '%'.$filter.'%');
                    })

                    ->orWhereHas('getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet', function ($q) use ($filter) {
                        $q->where('ossd_part', 'LIKE', '%'.$filter.'%');
                    });
            });
        }

        $data = $data->where('otc_status', 'Waiting for confirmation')->orderBy('created_at', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function store(Request $request)
    {
        Log::info('REQUEST', $request->all());
        $otcApproval = $request['otcPayload'];
        $reason = $request['reason'];
        $activeConnection = qxwsa::first();

        $confirmServices = new ConfirmOtherTransactionServices();
        $saveData = $confirmServices->confirmOtherTransaction($request, $otcApproval, $reason, $activeConnection);

        //       $saveData = $confirmServices->confirmOtherTransaction(
        //     $request,
        //     $otcApproval,
        //     $reason,
        //     $activeConnection
        // );

        if ($saveData !== true) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed To Confirm Other Transaction.',
                    'qad_message' => $saveData['message'] ?? 'Unknown QAD error.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Other Transaction has been confirmed',
            ],
            200,
        );
    }

    public function rejectOtherTransaction(Request $request)
    {
        Log::info('REQUEST', $request->all());
        $otcApproval = $request['otcPayload'];
        $reason = $request['reason'];
        $activeConnection = qxwsa::first();

        $confirmServices = new ConfirmOtherTransactionServices();
        $saveData = $confirmServices->rejectOtherTransaction($request, $otcApproval, $reason, $activeConnection);

        //       $saveData = $confirmServices->confirmOtherTransaction(
        //     $request,
        //     $otcApproval,
        //     $reason,
        //     $activeConnection
        // );

        if ($saveData !== true) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Failed To Confirm Other Transaction.',
                    'qad_message' => $saveData['message'] ?? 'Unknown QAD error.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Other Transaction has been confirmed',
            ],
            200,
        );
    }
}
