<?php

namespace App\Http\Controllers\API\OtherShipmentSchedule;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleMstr;
use App\Models\API\xxinvDet;
use App\Models\Settings\Item;
use App\Services\OtherShipmentScheduleServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class APIOtherShipmentScheduleController extends Controller
{
    public function index(Request $req)
    {
        $data = OtherShipmentScheduleMstr::query();

        if ($req->search) {
            $data->where(function ($query) use ($req) {
                $query
                    ->where('ossm_number', 'LIKE', '%'.$req->search.'%')
                    ->orWhere('ossm_cust_code', 'LIKE', '%'.$req->search.'%')
                    ->orWhere('ossm_cust_desc', 'LIKE', '%'.$req->search.'%')
                    ->orWhere('ossm_status', 'LIKE', '%'.$req->search.'%');
            });
        }

        $data = $data->orderBy('ossm_number', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function getItemOSS(Request $request)
    {
        $items = Item::with(['getLoadedBy:id,name', 'getUpdatedBy:id,name'])->where()
            ->orderBy('im_item_part')
            ->get();

        return response()->json(
            [
                'items' => $items,
            ],
            200,
        );
    }

    public function getItemOT(Request $request)
    {
        $items = xxinvDet::where('xxinv_det.xxinv_site', $request->site)
            ->join('item_master', 'item_master.im_item_part', '=', 'xxinv_det.xxinv_part')
            ->select(
                'xxinv_det.xxinv_part',
                'xxinv_det.xxinv_site',
                'item_master.im_item_desc',
                'item_master.im_item_um'
            )
            ->groupBy(
                'xxinv_det.xxinv_part',
                'xxinv_det.xxinv_site',
                'item_master.im_item_desc',
                'item_master.im_item_um'
            )
            ->get();

        return response()->json([
            'items' => $items,
        ], 200);
    }

    public function getSiteOT(Request $request)
    {
        $sites = xxinvDet::query()
            ->select('xxinv_site')
            ->distinct()
            ->orderBy('xxinv_site')
            ->pluck('xxinv_site');

        return response()->json([
            'site' => $sites,
        ], 200);
    }

    public function getLocationByPart(Request $request)
    {
       $items = xxinvDet::where('xxinv_part', $request->search)
    ->where('xxinv_qtyoh', '>=', 0)
    ->where('xxinv_loc', '!=', 'QC-QRT')
    ->where('xxinv_loc', '!=', 'WH-QRT')
    ->get();

        $inventoryData = $items->map(function ($item) {
            return [
                't_inv_part' => $item->xxinv_part,
                't_inv_loc' => $item->xxinv_loc,
                't_inv_lot' => $item->xxinv_lot,
                't_inv_bin' => $item->xxinv_bin,
                't_inv_level' => $item->xxinv_level,
                't_inv_site' => $item->xxinv_site,
                't_inv_wrh' => $item->xxinv_wrh,
                't_inv_qtyoh' => $item->xxinv_qtyoh,
                't_inv_uom' => 'null', // atau ambil dari tabel Item jika diperlukan
            ];
        });

        return response()->json([
            'inventoryData' => $inventoryData,
        ]);
    }
public function store(Request $request)
{
    // Validasi mandatory field dari request API
    $request->validate([
        'otherTransactionNumber' => 'required|string',
        'items' => 'required|array|min:1',
    ], [
        'otherTransactionNumber.required' => 'Other Transaction Number is required.',
        'items.required' => 'Items are required.',
    ]);

    $otherTransactionNumber = $request->otherTransactionNumber;
    $customerCode = $request->customer_id;
    $customerDesc = $request->customer_desc;
    $items = $request->items;

    $otherShipmentScheduleServices = new OtherShipmentScheduleServices();
    $saveData = $otherShipmentScheduleServices->saveOtherShipmentSchedule($otherTransactionNumber, $customerCode, $customerDesc, $items);

    if ($saveData == false) {
        return response()->json(
            [
                'Status' => 'Error',
                'Message' => 'Failed To Save Other Shipment Schedule.',
            ],
            422,
        );
    }

    return response()->json(
        [
            'status' => 'success',
            'message' => 'Other Shipment schedule has been created',
        ],
        200,
        ['Content-Type' => 'application/json'],
        JSON_UNESCAPED_UNICODE,
    );
}

    public function delete(Request $request)
    {
        $id = $request->id;

        // Ambil data master, loop ke detail, loop ke lokasi, sebelum hapus masukin ke history, terakhir delete
        $otherShipmentScheduleMstr = OtherShipmentScheduleMstr::with(['getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation'])->find($id);

        if (! $otherShipmentScheduleMstr) {
            return response()->json(
                [
                    'status' => 'Error',
                    'message' => 'Data not found',
                ],
                422,
                ['Content-Type' => 'application/json'],
                JSON_UNESCAPED_UNICODE,
            );
        }

        $otherShipmentScheduleServices = new OtherShipmentScheduleServices();
        $deleteData = $otherShipmentScheduleServices->deleteOtherShipmentSchedule($otherShipmentScheduleMstr);

        if ($deleteData == false) {
            return response()->json(
                [
                    'status' => 'Error',
                    'message' => 'Failed to delete other shipment schedule',
                ],
                422,
                ['Content-Type' => 'application/json'],
                JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Other shipment schedule has been deleted',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function edit($id)
    {
        $otherShipmentSchedule = OtherShipmentScheduleMstr::with(['getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation'])->find($id);

        if (! $otherShipmentSchedule) {
            return response()->json(
                [
                    'status' => 'Error',
                    'message' => 'Failed to fetch other shipment schedule data',
                ],
                422,
                ['Content-Type' => 'application/json'],
                JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'otherShipmentScheduleData' => $otherShipmentSchedule,
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function update(Request $request, $id)
    {
        Log::channel('otherShipmentSchedule')->info(json_encode($request->all()));

        $idOtherShipmentScheduleMstr = $id;
        $items = $request->items;

        $otherShipmentScheduleServices = new OtherShipmentScheduleServices();
        $updateData = $otherShipmentScheduleServices->updateOtherShipmentSchedule($idOtherShipmentScheduleMstr, $items);
        if ($updateData == false) {
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Failed To Update Other Shipment Schedule.',
                ],
                422,
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Other Shipment schedule has been updated',
            ],
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_UNICODE,
        );
    }
}
