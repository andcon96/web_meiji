<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class APIPurchaseOrderController extends Controller
{
    public function index(Request $req)
    {
        $data = PurchaseOrderMaster::query()->with('getDetail');

        if ($req->search) {
            $data->where('po_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhere('po_vend', 'LIKE', '%' . $req->search . '%')
                ->orWhere('po_vend_desc', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getDetail', 'pod_part', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getDetail', 'pod_part_desc', 'LIKE', '%' . $req->search . '%')
            ;
        }

        $data = $data->orderBy('id', 'desc')->paginate(10);


        return GeneralResources::collection($data);
    }

    public function wsaDataPO(Request $req)
    {
        $hasil = (new WSAServices())->wsaPurchaseOrder($req->search);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Purchase Order : " . $req->search . " Not Found."
            ], 422);
        }

        $listData = $hasil[1];
        try {
            DB::beginTransaction();
            $dataHeader = [];

            $dataMaster = PurchaseOrderMaster::firstOrNew(
                ['po_nbr' => (string)$listData[0]->t_poNbr]
            );
            $dataMaster->po_vend = (string)$listData[0]->t_poVend;
            $dataMaster->po_vend_desc = (string)$listData[0]->t_poVendDesc;
            $dataMaster->po_ord_date = (string)$listData[0]->t_poOrdDate;
            $dataMaster->po_due_date = (string)$listData[0]->t_poDueDate;
            $dataMaster->po_rmks = (string)$listData[0]->t_poRmks;
            $dataMaster->po_stat = (string)$listData[0]->t_poStat;
            $dataMaster->save();

            $dataHeader[] = [
                'id' => $dataMaster->id,
                'po_nbr' => (string)$listData[0]->t_poNbr,
                'po_vend' => (string)$listData[0]->t_poVend,
                'po_vend_desc' => (string)$listData[0]->t_poVendDesc,
                'po_ord_date' => (string)$listData[0]->t_poOrdDate,
                'po_due_date' => (string)$listData[0]->t_poDueDate,
                'po_stat' => (string)$listData[0]->t_poStat,
            ];

            $dataDetail = [];
            foreach ($listData as $listDatas) {
                $newDataDetail = PurchaseOrderDetail::firstOrNew(
                    [
                        'pod_po_mstr_id' => $dataMaster->id,
                        'pod_line' => (string)$listDatas->t_podLine
                    ]
                );
                $newDataDetail->pod_part = (string)$listDatas->t_podPart;
                $newDataDetail->pod_part_desc = (string)$listDatas->t_podPartDesc;
                $newDataDetail->pod_qty_ord = (string)$listDatas->t_podQtyOrd;
                $newDataDetail->pod_qty_rcpt = (string)$listDatas->t_podQtyRcpt;
                $newDataDetail->pod_um = (string)$listDatas->t_podUm;
                $newDataDetail->save();

                $dataDetail[] = [
                    'id' => $newDataDetail->id,
                    'po_mstr_id' => $dataMaster->id,
                    'pod_line' => (string)$listDatas->t_podLine,
                    'pod_part' => (string)$listDatas->t_podPart,
                    'pod_part_desc' => (string)$listDatas->t_podPartDesc,
                    'pod_qty_ord' => (string)$listDatas->t_podQtyOrd,
                    'pod_qty_rcpt' => (string)$listDatas->t_podQtyRcpt,
                    'pod_qty_ongoing' => $newDataDetail->pod_qty_ongoing,
                    'pod_um' => (string)$listDatas->t_podUm,
                    'is_selected' => false, // Buat Menu Android
                    'is_expandable' => false, // Buat Menu Android
                ];
            }

            DB::commit();
            return response()->json([
                'DataHeader' => $dataHeader,
                'DataWSA' => $dataDetail
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Unable to Proccess Request"
            ], 422);
        }
    }
}
