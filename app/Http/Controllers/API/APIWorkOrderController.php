<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\LocationDetail;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptServices;
use Illuminate\Support\Facades\Cache;

class APIWorkOrderController extends Controller
{
    public function getWO(Request $req)
    {
        $data = PurchaseOrderMaster::query()->with([
            'getDetail',
            'getReceipt.getDetailReceipt',
            'getReceipt.getDetailReceipt.getPurchaseOrderDetail',
            'getReceipt.getDetailReceipt.getDokumen',
            'getReceipt.getDetailReceipt.getKemasan',
            'getReceipt.getDetailReceipt.getKendaraan',
            'getReceipt.getDetailReceipt.getPenanda',
        ]);

        if ($req->search) {
            $data->where('po_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhere('po_vend', 'LIKE', '%' . $req->search . '%')
                ->orWhere('po_vend_desc', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getReceipt', 'rm_rn_number', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getReceipt.getDetailReceipt', 'rd_nomor_buku', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getDetail', 'pod_part', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getDetail', 'pod_part_desc', 'LIKE', '%' . $req->search . '%')
            ;
        }

        $data = $data->orderBy('id', 'desc')->paginate(10);


        return GeneralResources::collection($data);
    }

    public function wsaDataWo(Request $req)
    {
        $hasil = (new WSAServices())->wsaGetWO($req->search);

        
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Work Order : " . $req->search . " Not Found."
            ], 422);
        }

        $listData = $hasil[1];
        // try {
        //     DB::beginTransaction();
        //     $dataHeader = [];

        //     $dataMaster = PurchaseOrderMaster::firstOrNew(
        //         ['po_nbr' => (string)$listData[0]->t_poNbr]
        //     );
        //     $dataMaster->po_vend = (string)$listData[0]->t_poVend;
        //     $dataMaster->po_vend_desc = (string)$listData[0]->t_poVendDesc;
        //     $dataMaster->po_ord_date = (string)$listData[0]->t_poOrdDate;
        //     $dataMaster->po_due_date = (string)$listData[0]->t_poDueDate;
        //     $dataMaster->po_rmks = (string)$listData[0]->t_poRmks;
        //     $dataMaster->po_stat = (string)$listData[0]->t_poStat;
        //     $dataMaster->save();

        //     $dataHeader[] = [
        //         'id' => $dataMaster->id,
        //         'po_nbr' => (string)$listData[0]->t_poNbr,
        //         'po_vend' => (string)$listData[0]->t_poVend,
        //         'po_vend_desc' => (string)$listData[0]->t_poVendDesc,
        //         'po_ord_date' => (string)$listData[0]->t_poOrdDate,
        //         'po_due_date' => (string)$listData[0]->t_poDueDate,
        //         'po_stat' => (string)$listData[0]->t_poStat,
        //     ];

        //     $dataDetail = [];
        //     foreach ($listData as $listDatas) {
        //         $newDataDetail = PurchaseOrderDetail::firstOrNew(
        //             [
        //                 'pod_po_mstr_id' => $dataMaster->id,
        //                 'pod_line' => (string)$listDatas->t_podLine
        //             ]
        //         );
        //         $newDataDetail->pod_part = (string)$listDatas->t_podPart;
        //         $newDataDetail->pod_part_desc = (string)$listDatas->t_podPartDesc;
        //         $newDataDetail->pod_qty_ord = (string)$listDatas->t_podQtyOrd;
        //         $newDataDetail->pod_qty_rcpt = (string)$listDatas->t_podQtyRcpt;
        //         $newDataDetail->pod_um = (string)$listDatas->t_podUm;
        //         $newDataDetail->save();

        //         $dataDetail[] = [
        //             'id' => $newDataDetail->id,
        //             'po_mstr_id' => $dataMaster->id,
        //             'pod_line' => (string)$listDatas->t_podLine,
        //             'pod_part' => (string)$listDatas->t_podPart,
        //             'pod_part_desc' => (string)$listDatas->t_podPartDesc,
        //             'pod_qty_ord' => (string)$listDatas->t_podQtyOrd,
        //             'pod_qty_rcpt' => (string)$listDatas->t_podQtyRcpt,
        //             'pod_qty_ongoing' => '0',
        //             'pod_um' => (string)$listDatas->t_podUm,
        //             'is_selected' => false, // Buat Menu Android
        //             'is_expandable' => false, // Buat Menu Android
        //         ];
        //     }

        //     DB::commit();
        //     return response()->json([
        //         'DataHeader' => $dataHeader,
        //         'DataWSA' => $dataDetail
        //     ], 200);
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Unable to Proccess Request"
        //     ], 422);
        // }
    }

    public function saveReceipt(Request $req)
    {
        $inputan = json_decode($req->data);

        $saveData = (new ReceiptServices())->saveDataReceiptPerLot($inputan);

        if ($saveData == false) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Save Receipt Data."
            ], 422);
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => 'Data Receipt Saved',
            'ReceiptNumber' => 'RCPT00001'
        ], 200);
    }

    public function saveEditReceipt(Request $req)
    {
        $inputan = json_decode($req->data);

        $saveData = (new ReceiptServices())->editDataReceipt($inputan);
        if ($saveData == false) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Save Receipt Data."
            ], 422);
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => 'Data Receipt Updated',
        ], 200);
    }

    public function wsaLotBatch(Request $req)
    {
        $itemCode = $req->search;

        $wsaData = Cache::remember('wsaLotSerial', 60, function () use ($itemCode) {
            return (new WSAServices())->wsaLotSerialLdDetail($itemCode);
        });

        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsaPenyimpanan(Request $req)
    {
        $itemCode = $req->search;

        // Ambil Relati Item ke Location di Web
        $getAllItemLocation = LocationDetail::query()->with(['getListItem.getItem', 'getMaster']);
        if ($itemCode) {
            $getAllItemLocation->whereRelation('getListItem.getItem', 'im_item_part', '=', $itemCode);
        }
        $getAllItemLocation = $getAllItemLocation->get();


        // Ambil List Location di QAD untuk dibanding ke Web
        $wsaData = Cache::remember('wsaPenyimpanan', 60, function () use ($itemCode) {
            return (new WSAServices())->wsaPenyimpanan($itemCode);
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        // Prioritaskan Location yang ada di Web by order.
        $dataQAD = collect($wsaData[1]);
        $dataQAD = $dataQAD->map(function ($item) use ($getAllItemLocation) {
            foreach ($getAllItemLocation as $datas) {
                if (
                    $item['t_inv_level'] == $datas->ld_rak &&
                    $item['t_inv_wrh'] == $datas->ld_building &&
                    $item['t_inv_bin'] == $datas->ld_bin &&
                    $item['t_inv_loc'] == $datas->getMaster->location_code
                ) {
                    $item['t_is_prioritize'] = '1';
                    break;
                }
            }
            return $item;
        });

        $dataQAD = $dataQAD->sortByDesc('t_is_prioritize')->values();

        return response()->json($dataQAD);
    }
}
