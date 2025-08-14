<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\API\ReceiptAttachment;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\LocationDetail;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptServices;
use Illuminate\Support\Facades\Cache;

class APIPurchaseOrderController extends Controller
{
    public function index(Request $req)
    {
        $data = PurchaseOrderMaster::query()->with([
            'getDetail',
            'getReceipt.getDetailReceipt',
            'getReceipt.getDetailReceipt.getPurchaseOrderDetail',
            'getReceipt.getDetailReceipt.getDokumen',
            'getReceipt.getDetailReceipt.getKemasan',
            'getReceipt.getDetailReceipt.getKendaraan',
            'getReceipt.getDetailReceipt.getPenanda',
            'getReceipt.getDetailReceipt.getAttachment',
            'getReceipt.getDetailReceipt.getApprovalTemp.getUserApprove:id,username,name',
            'getReceipt.getDetailReceipt.getApprovalTemp.getUserApproveAlt:id,username,name',
            'getReceipt.getDetailReceipt.getApprovalTemp.getUserApproveBy:id,username,name',

            'getReceipt.getDetailReceipt.getApprovalHist.getUserApprove:id,username,name',
            'getReceipt.getDetailReceipt.getApprovalHist.getUserApproveAlt:id,username,name',
            'getReceipt.getDetailReceipt.getApprovalHist.getUserApproveBy:id,username,name',
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

    public function wsaDataPO(Request $req)
    {
        $hasil = (new WSAServices())->wsaPurchaseOrder($req->search);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "Purchase Order : " . $req->search . " Not Found."
            ], 422);
        }

        return response()->json([
            'DataHeader' => $hasil[1],
            'DataWSA' => $hasil[2]
        ], 200);
    }

    public function saveReceipt(Request $req)
    {
        $inputan = json_decode($req->data);
        $images = $req->input('images', []); // Gets indexTabPod values
        $files = $req->file('images');       // Gets UploadedFile objects

        $arrayKoneksiImage = [];
        foreach ($images as $index => $imageInfo) {
            $idPodTab = $imageInfo['idPodTab'] ?? null;
            $idSubTab = $imageInfo['idSubTab'] ?? null;

            if (isset($files[$index]['file'])) {
                $file = $files[$index]['file'];

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $dataTime = date('Ymd_His');
                    $filename = $dataTime . '-' . $file->getClientOriginalName();

                    // Simpan File Upload pada Public
                    $savepath = public_path('upload/receipttemp/');
                    $file->move($savepath, $filename);

                    $arrayKoneksiImage[] = [
                        'idSubTab' => $idSubTab,
                        'idPodTab' => $idPodTab,
                        'fileName' => $filename,
                    ];
                }
            }
        }

        $saveData = (new ReceiptServices())->saveDataReceiptPerLot($inputan, $arrayKoneksiImage);


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
        $data = $req->all();
        $inputan = json_decode($req->data);

        if (array_key_exists('images', $data)) {
            foreach ($data['images'] as $key => $dataImage) {
                if ($dataImage->isValid()) {
                    $dataTime = date('Ymd_His');
                    $filename = $dataTime . '-EDIT-' . $dataImage->getClientOriginalName();

                    // Simpan File Upload pada Public
                    $savepath = public_path('upload/receipt/');
                    $filepath = 'upload/receipt/';
                    $dataImage->move($savepath, $filename);


                    $newReceiptAttachment = new ReceiptAttachment();
                    $newReceiptAttachment->rda_rd_det_id = $inputan->rd_pod_det_id;
                    $newReceiptAttachment->rda_filepath = $filepath . $filename;
                    $newReceiptAttachment->save();
                }
            }
        }

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
            return (new WSAServices())->wsaPenyimpanan($itemCode, '');
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        // Prioritaskan Location yang ada di Web by order.
        $getDataQAD = collect($wsaData[1]);
        $grouped = $getDataQAD->groupBy(function ($item) {
            return $item['t_inv_site'] . '-' . $item['t_inv_loc'] . '-' . $item['t_inv_bin'] . '-' . $item['t_inv_wrh'] . '-' . $item['t_inv_level'];
        });

        $merged = $grouped->map(function ($items) {
            $first = $items->first(); // take base data from the first item
            $first['t_inv_qtyoh'] = $items->sum(function ($i) {
                return (int)$i['t_inv_qtyoh'];
            });
            return $first;
        })->values();

        $dataQAD = $merged->map(function ($item) use ($getAllItemLocation) {
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
