<?php

namespace App\Http\Controllers\API\PackingReplenishment;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\API\xxinvDet;
use App\Models\Settings\Item;
use App\Models\Settings\qxwsa;
use App\Models\Settings\User;
use App\Services\PackingReplenishmentServices;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class APIPackingReplenishmentController extends Controller
{
    public function index(Request $request)
    {
        $data = PackingReplenishmentMstr::query()->with(['getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', 'getCreatedBy:id,name,username']);

        if ($request->search) {
            $search = $request->search;

            $data->where(function ($q) use ($search) {

                $q->where('prm_shipper_nbr', 'LIKE', '%'.$search.'%')

                    ->orWhereHas('getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', function ($query) use ($search) {
                        $query->where('ssm_cust_code', 'LIKE', '%'.$search.'%')->orWhere('ssm_cust_desc', 'LIKE', '%'.$search.'%');
                    })

                    ->orWhereHas('getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet', function ($query) use ($search) {
                        $query->where('ssd_sod_nbr', 'LIKE', '%'.$search.'%')->orWhere('ssd_sod_part', 'LIKE', '%'.$search.'%');
                    });
            });
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
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'No Shipment Schedule found.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'listShipmentSchedule' => $listShipmentSchedule,
            ],
            200,
        );
    }

    public function listShipmentScheduleWSA(Request $request)
    {
        $shipperNumber = $request->query('shipperNumber') ?? $request->shipperNumber;
        $site = $request->query('site') ?? $request->site;

        $hasil = (new WSAServices())->listShipmentScheduleWSA($shipperNumber, $site);
        [$qdocResult, $dataloop, $qdocMessage] = $hasil;

        if ($qdocResult !== 'true') {
            return response()->json(
                [
                    'success' => false,
                    'message' => $qdocMessage ?: 'Failed to fetch shipment schedule from WSA',
                    'data' => [],
                ],
                422,
            );
        }

        $partNumbers = collect($dataloop)->pluck('t_part')->map(fn ($part) => (string) $part)->unique()->values()->all();

        $items = Item::whereIn('im_item_part', $partNumbers)
            ->get()
            ->keyBy('im_item_part');

        $inventory = xxinvDet::whereIn('xxinv_part', $partNumbers)->get();

        $inventoryGrouped = $inventory->groupBy(function ($row) {
            return trim((string) $row->xxinv_part).'|'.trim((string) $row->xxinv_lot);
        });

        $rows = [];

        foreach ($dataloop as $row) {
            $part = trim((string) $row->t_part);
            $lot = trim((string) $row->t_lot);
            $loc = trim((string) $row->t_loc);
            $siteRow = trim((string) $row->t_site);

            $item = $items->get($part);
            $itemId = $item->id ?? null;

            $stockRows = $inventoryGrouped->get($part.'|'.$lot, collect());

            $locationDetail = [];
            foreach ($stockRows as $stockRow) {
                $locationDetail[] = [
                    'lot_serial' => (string) $stockRow->xxinv_lot,
                    'wrh' => (string) $stockRow->xxinv_wrh,
                    'level' => (string) $stockRow->xxinv_level,
                    'bin' => (string) $stockRow->xxinv_bin,
                    'location' => (string) $stockRow->xxinv_loc,
                    'stock' => (float) ($stockRow->xxinv_qtyoh ?? 0),
                ];
            }

            $rows[] = [
                'domain' => (string) $row->t_domain,
                'site' => $siteRow,
                'part' => $part,
                'desc' => (string) $row->t_desc,
                'um' => (string) $row->t_um,
                'loc' => $loc,
                'line' => (string) $row->t_line,
                'lot' => $lot,
                'qty' => (int) $row->t_qty,
                'item_id' => $itemId,
                'location_detail' => $locationDetail,
            ];
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'Shipment schedule fetched successfully',
                'data' => $rows,
            ],
            200,
        );
    }

    public function store(Request $request)
    {
        Log::channel('packingReplenishment')->info(json_encode($request->all()));
        $request->validate(
            [
                'approver' => 'required',
                'prm_id' => 'required',
                'scheduleDetail' => 'required',
            ],
            [
                'approver.required' => 'Approver wajib dipilih.',
                'prm_id.required' => 'ID Packing Replenishment (prm_id) wajib diisi.',
                'scheduleDetail.required' => 'Detail jadwal pengiriman (scheduleDetail) wajib diisi.',
            ],
        );

        $approver = $request->approver;
        $idPrm = $request->prm_id;
        $packingReplenishments = $request->scheduleDetail;

        $activeConnection = qxwsa::first();

        $packingReplenishmentService = new PackingReplenishmentServices();
        $saveData = $packingReplenishmentService->savePackingReplenishment($approver, $idPrm, $packingReplenishments, $activeConnection);

        if ($saveData == false) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Failed To Save Packing Replenishment.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Packing Replenishment has been created',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approverList()
    {
        $users = user::with('getRole')
            ->whereRelation('getRole', 'role_android_acc', 'like', '%AP03%')
            ->where('is_active', 'Active')
            ->orderBy('username', 'asc')
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

    public function rejectPackingReplenishment(Request $request)
    {
        Log::info('Reject Packing Replenishment Request', [
            'request' => $request->all(),
        ]);

        $packingReplenishment = $request->shipperPayload;
        $reason = $request->reason;
        $shipmentScheduleNumber = $request->shipmentScheduleNumber;

        $packingReplenishmentService = new PackingReplenishmentServices();
        $rejectPackingReplenishment = $packingReplenishmentService->rejectPackingReplenishment($packingReplenishment, $reason, $shipmentScheduleNumber);

        if ($rejectPackingReplenishment == false) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Failed To reject shipment preparation.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Shipment preparation has been rejected',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function approvePackingReplenishment(Request $request)
    {

        $packingReplenishment = $request->shipperPayload;
        $reason = $request->reason;
        $shipmentScheduleNumber = $request->shipmentScheduleNumber;
        $activeConnection = qxwsa::first();

        $packingReplenishmentService = new PackingReplenishmentServices();
        $approvePackingReplenishment = $packingReplenishmentService->approvePackingReplenishment($packingReplenishment, $reason, $shipmentScheduleNumber, $activeConnection);

        if ($approvePackingReplenishment == false) {
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

    public function editPackingReplenishment($id)
    {
        $packingReplenishment = PackingReplenishmentMstr::with([
            'getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster',
        ])->find($id);

        if (! $packingReplenishment) {
            return response()->json(
                [
                    'status' => 'Error',
                    'message' => 'Failed to fetch packing replenishment data',
                ],
                422,
                ['Content-Type' => 'application/json'],
                JSON_UNESCAPED_UNICODE,
            );
        }

        $packingReplenishmentDet = $packingReplenishment->getPackingReplenishmentDet;

        $shipmentScheduleDet = $packingReplenishmentDet[0]->getShipmentScheduleLocation->getShipmentScheduleDet;

        $parts = $packingReplenishmentDet
            ->map(fn ($det) => trim((string) $det->getShipmentScheduleLocation->getShipmentScheduleDet->ssd_sod_part))
            ->unique()
            ->values()
            ->all();

        $lot = $packingReplenishmentDet
            ->map(fn ($det) => trim((string) $det->getShipmentScheduleLocation->getShipmentScheduleDet->ssd_sod_lot))
            ->unique()
            ->values()
            ->all();
        $inventory = xxinvDet::whereIn('xxinv_part', $parts)->where('xxinv_lot', $lot)->get();

        $inventoryGrouped = $inventory->groupBy(function ($row) {
            return trim((string) $row->xxinv_part);
        });

        foreach ($packingReplenishmentDet as $det) {
            $ssl = $det->getShipmentScheduleLocation;
            $part = trim((string) $ssl->getShipmentScheduleDet->ssd_sod_part);

            $stockRows = $inventoryGrouped->get($part, collect());

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

            $ssl->setAttribute('location_detail', $locationDetail);
        }

        return response()->json(
            [
                'status' => 'success',
                'packingReplenishmentData' => $packingReplenishment,
                'shipmentScheduleData' => $shipmentScheduleDet,
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function getPackingReplenishmentApprovalList(Request $request)
    {
        $data = PackingReplenishmentApproval::query()
            ->with(['getPackingReplenishmentMstr.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', 'getCreatedBy:id,name,username'])
            ->where('pra_user_approver', 'LIKE', '%'.Auth::user()->id.'%');

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {

                $q->whereHas('getPackingReplenishmentMstr', function ($subq) use ($filter) {
                    $subq->where('prm_shipper_nbr', 'LIKE', '%'.$filter.'%')->where('prm_status', 'Shipper Created');
                })

                    ->orWhereHas('getPackingReplenishmentMstr.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', function ($q) use ($filter) {
                        $q->where('ssm_cust_code', 'LIKE', '%'.$filter.'%')->orWhere('ssm_cust_desc', 'LIKE', '%'.$filter.'%');
                    })

                    ->orWhereHas('getPackingReplenishmentMstr.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet', function ($q) use ($filter) {
                        $q->where('ssd_sod_part', 'LIKE', '%'.$filter.'%');
                    });
            });
        }

        $data = $data->where('pra_status', 'Waiting for confirmation')->orderBy('created_at', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function getStockWarehouse(Request $request)
    {
        $data = xxinvDet::query()
            ->when($request->filled('part'), function ($q) use ($request) {
                $q->where('xxinv_part', 'LIKE', '%'.$request->part.'%');
            })
            ->when($request->filled('loc'), function ($q) use ($request) {
                $q->where('xxinv_loc', 'LIKE', '%'.$request->loc.'%');
            })
            ->when($request->filled('lot'), function ($q) use ($request) {
                $q->where('xxinv_lot', 'LIKE', '%'.$request->lot.'%');
            })
            ->when($request->filled('site'), function ($q) use ($request) {
                $q->where('xxinv_site', 'LIKE', '%'.$request->site.'%');
            })
            ->first();

        if (! $data) {
            return response()->json(
                [
                    'message' => 'Data tidak ditemukan',
                ],
                404,
            );
        }

        return response()->json([
            'qty' => (float) $data->xxinv_qty_wrh,
        ]);
    }
}
