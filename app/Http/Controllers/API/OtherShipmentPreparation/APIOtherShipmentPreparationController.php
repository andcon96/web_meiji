<?php

namespace App\Http\Controllers\API\OtherShipmentPreparation;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationApproval;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationMstr;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleMstr;
use App\Models\API\xxinvDet;
use App\Models\Settings\qxwsa;
use App\Models\Settings\Role;
use App\Models\Settings\User;
use App\Services\OtherShipmentPreparationServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class APIOtherShipmentPreparationController extends Controller
{
    public function index(Request $request)
    {
        $data = OtherShipmentPreparationMstr::query()->with(['getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster', 'getCreatedBy:id,name,username']);

        if ($request->search) {
            $search = $request->search;

            $data->where(function ($q) use ($search) {

                $q->where('ospm_number', 'LIKE', '%'.$search.'%')

                    ->orWhereHas('getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster', function ($query) use ($search) {
                        $query->where('ossm_cust_code', 'LIKE', '%'.$search.'%')->orWhere('ossm_cust_desc', 'LIKE', '%'.$search.'%');
                    })

                    ->orWhereHas('getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet', function ($query) use ($search) {
                        $query->where('ossd_part', 'LIKE', '%'.$search.'%');
                    });
            });
        }

        $data = $data->orderBy('id', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function listOtherShipmentSchedule()
    {
        $listOtherShipmentSchedule = OtherShipmentScheduleMstr::whereDoesntHave('getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation.getOtherShipmentPreparationDet')
            ->with(['getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation'])
            ->orderBy('ossm_number', 'desc')
            ->get();

        if ($listOtherShipmentSchedule->count() == 0) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'No Other Shipment Schedule found.',
                ],
                422,
            );
        }

        $partNumbers = $listOtherShipmentSchedule->flatMap(fn ($mstr) => $mstr->getOtherShipmentScheduleDetail)->pluck('ossd_part')->map(fn ($part) => (string) $part)->unique()->values()->all();

        $inventory = xxinvDet::whereIn('xxinv_part', $partNumbers)->get();

        foreach ($listOtherShipmentSchedule as $mstr) {
            foreach ($mstr->getOtherShipmentScheduleDetail as $detail) {
                $part = trim((string) $detail->ossd_part);

                foreach ($detail->getOtherShipmentScheduleLocation as $location) {
                    $lot = trim((string) $location->ossl_lotserial);
                    $loc = trim((string) $location->ossl_location);

                    $level = strtoupper(trim((string) $location->ossl_level));
                    $bin = trim((string) $location->ossl_bin);

                    $stockRow = $inventory->first(function ($inv) use ($part, $lot, $loc, $level, $bin) {
                        $cleanInvLevel = preg_replace('/[^A-Za-z0-9]/', '', $inv->xxinv_level);
                        $cleanLocalLevel = preg_replace('/[^A-Za-z0-9]/', '', $level);

                        return trim($inv->xxinv_part) == $part && trim((string) $inv->xxinv_lot) === $lot && trim($inv->xxinv_loc) == $loc && strtoupper($cleanInvLevel) == strtoupper($cleanLocalLevel) && trim($inv->xxinv_bin) == $bin;
                    });

                    $location->setAttribute('stock', (float) ($stockRow->xxinv_qtyoh ?? 0));
                }
            }
        }

        return response()->json(
            [
                'listOtherShipmentSchedule' => $listOtherShipmentSchedule,
            ],
            200,
        );
    }

    public function store(Request $request)
    {

        $approver = $request->approver;
        $idOssm = $request->ossm_id;
        $otherShipmentPreparation = $request->otherScheduleDetail;

        $activeConnection = qxwsa::first();

        $otherShipmentPreparationService = new OtherShipmentPreparationServices();
        $saveData = $otherShipmentPreparationService->saveOtherShipmentPreparation($approver, $idOssm, $otherShipmentPreparation, $activeConnection);

        if ($saveData == false) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Failed To Save Other Shipment Preparation.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Other Shipment Preparation has been created',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approverListShipmentPreparation()
    {
        $role = Role::where('role_code', 'SH')->first();
        $users = User::where('role_id', $role->id)
            ->where('is_active', 'Active')
            ->get(['id', 'name']);

        if ($users->count() == 0) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'No users found.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'users' => $users,
            ],
            200,
        );
    }

    public function rejectShipmentPreparation(Request $request)
    {

        $otherShipmentPreparation = $request->shipperPayload;
        $reason = $request->reason;
        $otherShipmentScheduleNumber = $request->shipmentScheduleNumber;
        $otherShipmentPreparationServices = new OtherShipmentPreparationServices();
        $rejectOtherShipmentPreparation = $otherShipmentPreparationServices->rejectOtherShipmentPreparation($otherShipmentPreparation, $reason, $otherShipmentScheduleNumber);

        if ($rejectOtherShipmentPreparation == false) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Failed To reject other shipment preparation.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Other Shipment preparation has been rejected',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approveShipmentPreparation(Request $request)
    {
        Log::channel('otherShipmentPreparation')->info(json_encode($request->all()));

        $shipmentPreparation = $request->shipperPayload;
        $reason = $request->reason;
        $otherShipmentScheduleNumber = $request->shipmentScheduleNumber;
        $activeConnection = qxwsa::first();

        $otherShipmentPreparationServices = new OtherShipmentPreparationServices();
        $approveShipmentPreparation = $otherShipmentPreparationServices->approveOtherShipmentPreparation($shipmentPreparation, $reason, $otherShipmentScheduleNumber, $activeConnection);

        if ($approveShipmentPreparation == false) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Failed to approve shipment preparation.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Shipment preparation has been approved',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function editShipmentPreparation($id)
    {
        $shipmentPreparation = OtherShipmentPreparationMstr::with([
            'getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster',
        ])->find($id);

        if (! $shipmentPreparation) {
            return response()->json(
                [
                    'status' => 'Error',
                    'message' => 'Failed to fetch other shipment preparation data',
                ],
                422,
                ['Content-Type' => 'application/json'],
                JSON_UNESCAPED_UNICODE,
            );
        }

        $shipmentPreparationDet = $shipmentPreparation->getOtherShipmentPreparationDet;

        $otherShipmentScheduleDet = $shipmentPreparationDet[0]
            ->getOtherShipmentScheduleLocation
            ->getOtherShipmentScheduleDet;

        $parts = $shipmentPreparationDet
            ->map(fn ($det) => trim((string) $det->getOtherShipmentScheduleLocation
                ->getOtherShipmentScheduleDet->ossd_part))
            ->unique()
            ->values()
            ->all();

        $lots = $shipmentPreparationDet
            ->map(fn ($det) => trim((string) $det->getOtherShipmentScheduleLocation
                ->ossl_lotserial))
            ->unique()
            ->values()
            ->all();

        $inventory = xxinvDet::whereIn('xxinv_part', $parts)
            ->whereIn('xxinv_lot', $lots)
            ->get();

        $inventoryGrouped = $inventory->groupBy(function ($row) {
            return trim((string) $row->xxinv_part).'|'.trim((string) $row->xxinv_lot);
        });

        foreach ($shipmentPreparationDet as $det) {

            $location = $det->getOtherShipmentScheduleLocation;

            $part = trim((string) $location->getOtherShipmentScheduleDet->ossd_part);
            $lot = trim((string) $location->ossl_lotserial);

            $stockRows = $inventoryGrouped->get($part.'|'.$lot, collect());

            $locationDetail = $stockRows->map(function ($stockRow) {
                return [
                    'lot_serial' => (string) $stockRow->xxinv_lot,
                    'wrh' => (string) $stockRow->xxinv_wrh,
                    'level' => (string) $stockRow->xxinv_level,
                    'bin' => (string) $stockRow->xxinv_bin,
                    'location' => (string) $stockRow->xxinv_loc,
                    'stock' => (float) ($stockRow->xxinv_qtyoh ?? 0),
                ];
            })->values();

            $location->setAttribute('location_detail', $locationDetail);
        }

        return response()->json(
            [
                'status' => 'success',
                'otherShipmentPreparationData' => $shipmentPreparation,
                'otherShipmentScheduleData' => $otherShipmentScheduleDet,
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function getOtherShipmentPreparationApprovalList(Request $request)
    {
        $data = OtherShipmentPreparationApproval::query()
            ->with(['getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster', 'getCreatedBy:id,name,username'])
            ->where('ospa_user_approver', 'LIKE', '%'.Auth::user()->id.'%');

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {

                $q->whereHas('getOtherShipmentPreparationMstr', function ($subq) use ($filter) {
                    $subq->where('ospm_number', 'LIKE', '%'.$filter.'%')->where('ospm_status', 'Shipper Created');
                })

                    ->orWhereHas('getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster', function ($q) use ($filter) {
                        $q->where('ossm_cust_code', 'LIKE', '%'.$filter.'%')->orWhere('ossm_cust_desc', 'LIKE', '%'.$filter.'%');
                    })

                    ->orWhereHas('getOtherShipmentPreparationMstr.getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster', function ($q) use ($filter) {
                        $q->where('ossd_part', 'LIKE', '%'.$filter.'%');
                    });
            });
        }

        $data = $data->where('ospa_status', 'Waiting for confirmation')->orderBy('created_at', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }
}
