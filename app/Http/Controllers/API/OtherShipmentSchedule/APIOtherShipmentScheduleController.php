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
use App\Models\Settings\Itemlocation;
class APIOtherShipmentScheduleController extends Controller
{
    public function index(Request $req)
    {
        $data = OtherShipmentScheduleMstr::query();

        if ($req->search) {
            $data->where(function ($query) use ($req) {
                $query
                    ->where('ossm_number', 'LIKE', '%' . $req->search . '%')
                    ->orWhere('ossm_cust_code', 'LIKE', '%' . $req->search . '%')
                    ->orWhere('ossm_cust_desc', 'LIKE', '%' . $req->search . '%')
                    ->orWhere('ossm_status', 'LIKE', '%' . $req->search . '%');
            });
        }

        $data = $data->orderBy('ossm_number', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function getItemOSS(Request $request)
    {
        $items = Item::with(['getLoadedBy:id,name', 'getUpdatedBy:id,name'])
            ->orderBy('im_item_part')
            ->get();

        return response()->json(
            [
                'items' => $items,
            ],
            200,
        );
    }

   public function getLocationByPart(Request $request)
{
    $item = Item::where('im_item_part', $request->search)
        ->with('getItemLocation.getLocationDetail.getMaster')
        ->first();

    $tempData = [];

    if ($item) {
        foreach ($item->getItemLocation as $location) {
            $detail = $location->getLocationDetail;

            if (!$detail) {
                continue;
            }

            $master = $detail->getMaster;

            $tempData[] = [
                't_inv_part'  => $item->im_item_part,
                't_inv_loc'   => $master?->location_code ?? '',
                't_inv_lot'   => $detail->ld_lot_serial,
                't_inv_bin'   => $detail->ld_bin,
                't_inv_level' => $detail->ld_rak,
                't_inv_site'  => $master?->location_site ?? '',
                't_inv_wrh'   => $master?->location_desc ?? $master?->location_code ?? '',
                't_inv_qtyoh' => '0',
                't_inv_uom'   => $item->im_item_um,
            ];
        }
    }

    return response()->json([
        'inventoryData' => $tempData,
    ]);
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

        if (!$otherShipmentScheduleMstr) {
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

        if (!$otherShipmentSchedule) {
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
