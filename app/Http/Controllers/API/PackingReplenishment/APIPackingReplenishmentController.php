<?php

namespace App\Http\Controllers\API\PackingReplenishment;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\API\xxinvDet;
use App\Models\Settings\Item;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\LocationDetail;
use App\Models\Settings\qxwsa;
use App\Models\Settings\Role;
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
                // cari customer
                $q->where('prm_shipper_nbr', 'LIKE', '%' . $search . '%')

                    // cari customer
                    ->orWhereHas('getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', function ($query) use ($search) {
                        $query->where('ssm_cust_code', 'LIKE', '%' . $search . '%')->orWhere('ssm_cust_desc', 'LIKE', '%' . $search . '%');
                    })

                    // cari SO + item code
                    ->orWhereHas('getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet', function ($query) use ($search) {
                        $query->where('ssd_sod_nbr', 'LIKE', '%' . $search . '%')->orWhere('ssd_sod_part', 'LIKE', '%' . $search . '%');
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
        // dd($hasil );
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

        $partNumbers = collect($dataloop)->pluck('t_part')->map(fn($part) => (string) $part)->unique()->values()->all();

        $items = Item::whereIn('im_item_part', $partNumbers)
            ->with(['getItemLocation.getLocationDetail'])
            ->get()
            ->keyBy('im_item_part');

        $inventory = xxinvDet::whereIn('xxinv_part', $partNumbers)->get();

        $rows = [];

        foreach ($dataloop as $row) {
            $part = trim((string) $row->t_part);
            $lot = trim((string) $row->t_lot);
            $loc = trim((string) $row->t_loc);
            $site = trim((string) $row->t_site);

            $item = $items->get($part);

            $itemId = $item->id ?? null;
            $locationDetail = [];

            if ($item) {
                $matchedLocations = $item->getItemLocation->filter(function ($itemLocation) use ($lot) {
                    $ld = $itemLocation->getLocationDetail;

                    return $ld && trim((string) $ld->ld_lot_serial) === $lot;
                });

                foreach ($matchedLocations as $itemLocation) {
                    $ld = $itemLocation->getLocationDetail;

                    $level = strtoupper(trim($ld->ld_rak));
                    $stockRow = $inventory->first(function ($inv) use ($part, $lot, $loc, $site, $level, $ld) {

                        $cleanInvLevel = preg_replace('/[^A-Za-z0-9]/', '', $inv->xxinv_level);
                        $cleanLocalLevel = preg_replace('/[^A-Za-z0-9]/', '', $level);

                        return trim($inv->xxinv_part) == $part &&
                            trim((string) $inv->xxinv_lot) === $lot &&
                            trim($inv->xxinv_loc) == $loc &&
                            trim($inv->xxinv_site) == $site &&
                            strtoupper($cleanInvLevel) == strtoupper($cleanLocalLevel) &&
                            trim($inv->xxinv_bin) == trim($ld->ld_bin);
                    });

                    $locationDetail[] = [
                        'lot_serial' => $ld->ld_lot_serial,
                        'level' => $ld->ld_rak,
                        'bin' => $ld->ld_bin,
                        'location' => $ld->ld_building,
                        'stock' => (float) ($stockRow->xxinv_qty_wrh ?? 0),
                    ];
                }
            }

            $rows[] = [
                'domain' => (string) $row->t_domain,
                'site' => $site,
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
        // $role = Role::where("role_code", "SH")->first();
        // $users = User::where("role_id", $role->id)
        //     ->where("is_active", "Active")
        //     ->get(["id", "name"]);

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
        // Log::channel("packingReplenishment")->info(json_encode($request->all()));
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
        $packingReplenishment = PackingReplenishmentMstr::with(['getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster'])->find($id);

        if (!$packingReplenishment) {
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

        $shipmentScheduleDet = $packingReplenishment->getPackingReplenishmentDet[0]->getShipmentScheduleLocation->getShipmentScheduleDet;

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
            ->where('pra_user_approver', 'LIKE', '%' . Auth::user()->id . '%');

        if ($request->search) {
            $filter = $request->search;

            $data->where(function ($q) use ($filter) {
                // Cari shipper number
                $q->whereHas('getPackingReplenishmentMstr', function ($subq) use ($filter) {
                    $subq->where('prm_shipper_nbr', 'LIKE', '%' . $filter . '%')->where('prm_status', 'Shipper Created');
                })

                    // Cari customer
                    ->orWhereHas('getPackingReplenishmentMstr.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster', function ($q) use ($filter) {
                        $q->where('ssm_cust_code', 'LIKE', '%' . $filter . '%')->orWhere('ssm_cust_desc', 'LIKE', '%' . $filter . '%');
                    })

                    // cari SO + item code
                    ->orWhereHas('getPackingReplenishmentMstr.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet', function ($q) use ($filter) {
                        $q->where('ssd_sod_part', 'LIKE', '%' . $filter . '%');
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
                $q->where('xxinv_part', 'LIKE', '%' . $request->part . '%');
            })
            ->when($request->filled('loc'), function ($q) use ($request) {
                $q->where('xxinv_loc', 'LIKE', '%' . $request->loc . '%');
            })
            ->when($request->filled('lot'), function ($q) use ($request) {
                $q->where('xxinv_lot', 'LIKE', '%' . $request->lot . '%');
            })
            ->when($request->filled('site'), function ($q) use ($request) {
                $q->where('xxinv_site', 'LIKE', '%' . $request->site . '%');
            })
            ->first();

        if (!$data) {
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
