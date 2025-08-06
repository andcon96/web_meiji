<?php

namespace App\Http\Controllers\API\ShipmentSchedule;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\Settings\qxwsa;
use App\Services\ShipmentScheduleServices;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APIShipmentScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $data = ShipmentScheduleMstr::query();

        if ($req->search) {
            $data->where('ssm_number', 'LIKE', '%' . $req->search . '%')
                ->orWhere('ssm_cust_code', 'LIKE', '%' . $req->search . '%')
                ->orWhere('ssm_cust_desc', 'LIKE', '%' . $req->search . '%');
            ;
        }

        $data = $data->orderBy('id', 'desc')->paginate(10);


        return GeneralResources::collection($data);
    }

    public function wsaCustomer()
    {
        $activeConnection = qxwsa::first();
        $customerData = (new WSAServices())->wsaCustomer($activeConnection);

        if ($customerData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No customer data found."
            ], 422);
        }

        return response()->json([
            'customerData' => $customerData[1],
        ], 200);
    }

    public function wsaSalesOrder(Request $request)
    {
        $customer = $request->search;

        $activeConnection = qxwsa::first();
        $salesOrderData = (new WSAServices())->wsaSalesOrder($customer, $activeConnection);

        if ($salesOrderData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No sales order data found.'
            ], 422);
        }

        $tempData = [];

        foreach ($salesOrderData[1] as $data) {
            array_push($tempData, [
                't_so_nbr' => (String)$data->t_so_nbr,
                't_so_line' => (string)$data->t_so_line,
                't_so_part' => (string)$data->t_so_part,
                't_so_part_desc' => (string)$data->t_so_part_desc,
                't_so_ord_qty' => (string)$data->t_so_ord_qty,
            ]);
        }

        return response()->json([
            'salesOrderData' => $tempData,
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }

    public function wsaInventoryDetail(Request $request)
    {
        $itemCode = $request->search;

        $activeConnection = qxwsa::first();
        $wsaInventory = (new WSAServices())->wsaInventoryDetail($itemCode, $activeConnection);

        if ($wsaInventory[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No inventory data found.'
            ], 422);
        }

        $tempData = [];

        foreach ($wsaInventory[1] as $data) {
            array_push($tempData, [
                't_inv_part' => (string)$data->t_inv_part,
                't_inv_loc' => (string)$data->t_inv_loc,
                't_inv_lot' => (string)$data->t_inv_lot,
                't_inv_bin' => (string)$data->t_inv_bin,
                't_inv_level' => (string)$data->t_inv_level,
                't_inv_site' => (string)$data->t_inv_site,
                't_inv_wrh' => (string)$data->t_inv_wrh,
                't_inv_qtyoh' => (string)$data->t_inv_qtyoh,
            ]);
        }

        return response()->json([
            'inventoryData' => $tempData,
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request)
    {
        // Log::channel('shipmentSchedule')->info(json_encode($request->all()));

        $customerCode = $request->customer_id;
        $customerName = $request->customer_desc;
        $salesOrders = $request->sales_orders;

        $saveData = (new ShipmentScheduleServices())->saveShipmentSchedule(
            $customerCode,
            $customerName,
            $salesOrders
        );

        if ($saveData == false) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Save Shipment Schedule."
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment schedule has been created',
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }

    public function delete(Request $request)
    {
        $id = $request->id;

        // Ambil data master, loop ke detail, loop ke lokasi, sebelum hapus masukin ke history, terakhir delete
        $shipmentScheduleMstr = ShipmentScheduleMstr::with(['getShipmentScheduleDetail.getShipmentScheduleLocation'])->find($id);

        if (!$shipmentScheduleMstr) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Data not found',
            ], 422, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
        }

        $deleteData = (new ShipmentScheduleServices())->deleteShipmentSchedule($shipmentScheduleMstr);

        if ($deleteData == false) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to delete shipment schedule',
            ], 422, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment schedule has been deleted',
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }

    public function edit($id)
    {
        $shipmentSchedule = ShipmentScheduleMstr::with(['getShipmentScheduleDetail.getShipmentScheduleLocation'])->find($id);

        if (!$shipmentSchedule) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to fetch shipment schedule data',
            ], 422, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'status' => 'success',
            'shipmentScheduleData' => $shipmentSchedule
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, $id)
    {
        // Log::channel('shipmentSchedule')->info(json_encode($request->all()));

        $idShipmentScheduleMstr = $id;
        $salesOrders = $request->sales_orders;

        $updateData = (new ShipmentScheduleServices())->updateShipmentSchedule(
            $idShipmentScheduleMstr,
            $salesOrders
        );

        if ($updateData == false) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Update Shipment Schedule."
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment schedule has been created',
        ], 200, ['Content-Type' => 'application/json'], JSON_UNESCAPED_UNICODE);
    }
}
