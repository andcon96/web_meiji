<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\API\ReceiptAttachment;
use App\Models\API\ReceiptDetail;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\LocationDetail;
use App\Models\Settings\Location;

use App\Models\Settings\User;
use App\Models\Settings\Item;
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
            'getReceipt.getDetailReceipt.getPallet',
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


        if ($saveData[0] == false) {
            $msg = "Failed To Save Receipt Data.";
            if ($saveData[1] != '') {
                $msg = $saveData[1];
            }
            return response()->json([
                'Status' => 'Error',
                'Message' => $msg
            ], 422);
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => 'Data Receipt Saved, Receipt Number : ' . $saveData[1],
            'ReceiptNumber' => 'RCPT00001'
        ], 200);
    }

    public function saveEditReceipt(Request $req)
    {
        $data = $req->all();
        $inputan = json_decode($req->data);
        $approval = json_decode($req->userApprove);

        // Cek Approval

        if (empty($approval)) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No Approval'
            ], 422);
        }

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
                    $newReceiptAttachment->rda_rd_det_id = $inputan->id;
                    $newReceiptAttachment->rda_filepath = $filepath . $filename;
                    $newReceiptAttachment->save();
                }
            }
        }

        $saveData = (new ReceiptServices())->editDataReceipt($inputan, $approval);
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
        // $itemCode = $req->search; 
        // Request Xena 1609
        $itemCode = '';
        $warehouse = '';
        $type = 'input';
        if ($req->wh) {
            $warehouse = $req->wh;
        }
        if ($req->search) {
            $warehouse = $req->search;
        }
        if($req->item){
            $itemCode = $req->item;
        }

        // Ambil Relati Item ke Location di Web
        $getAllItemLocation = LocationDetail::query()->with(['getListItem.getItem', 'getMaster']);
        if ($itemCode) {
            $getAllItemLocation->whereRelation('getListItem.getItem', 'im_item_part', '=', 'CRAFT60LS');
        }
        if ($req->wh) {
            $getAllItemLocation->where('ld_building', $warehouse);
        }
        $getAllItemLocation = $getAllItemLocation->get();

        // Ambil List Location di QAD untuk dibanding ke Web
        // $wsaData = Cache::remember('wsaPenyimpanan', 60, function () use ($itemCode) {
        //     return (new WSAServices())->wsaPenyimpanan('', $itemCode, '', '', '', '');
        // });

        $wsaData = (new WSAServices())->wsaPenyimpanan('', $itemCode, '', '', $warehouse, '');
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        // Prioritaskan Location yang ada di Web by order.
        $getDataQAD = collect($wsaData[1]);

        $grouped = $getDataQAD->groupBy(function ($item) {
            $site  =  is_array($item['t_inv_site']) ? '' : (string) ($item['t_inv_site'] ?? '');
            $loc   = is_array($item['t_inv_loc']) ? '' : (string)($item['t_inv_loc'] ?? '');
            $bin   = is_array($item['t_inv_bin']) ? '' : (string) ($item['t_inv_bin'] ?? '');
            $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
            $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');

            return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
            // return $item['t_inv_site'] . '-' . $item['t_inv_loc'] . '-' . $item['t_inv_bin'] . '-' . $item['t_inv_wrh'] . '-' . $item['t_inv_level'];
        });

        $merged = $grouped->map(function ($items) {
            $first = $items->first(); // take base data from the first item
            $first['t_inv_qtyoh'] = $items->sum(function ($i) {
                return (int)$i['t_inv_qtyoh'];
            });
            return $first;
        })
            ->filter(function ($item) {
                return (int) $item['t_inv_qtyoh'] <= 0;
            })
            ->values();

        //$dataQAD = $merged->sortBy('t_inv_qtyoh')->sortBy('t_inv_wrh')->values();

        // //return response()->json($dataQAD);
        // $dataQAD = $merged->map(function ($item) use ($getAllItemLocation) {
        //     foreach ($getAllItemLocation as $datas) {
        //         if (
        //             $item['t_inv_level'] == $datas->ld_rak &&
        //             $item['t_inv_wrh'] == $datas->ld_building &&
        //             $item['t_inv_bin'] == $datas->ld_bin &&
        //             $item['t_inv_loc'] == $datas->getMaster->location_code
        //         ) {
        //             $item['t_is_prioritize'] = '1';
        //             break;
        //         }
        //     }
        //     return $item;
        // });

        $dataQAD = $merged->filter(function ($item) use ($getAllItemLocation) {
            foreach ($getAllItemLocation as $datas) {
                if (
                    $item['t_inv_level'] == $datas->ld_rak &&
                    $item['t_inv_wrh'] == $datas->ld_building &&
                    $item['t_inv_bin'] == $datas->ld_bin &&
                    $item['t_inv_loc'] == $datas->getMaster->location_code
                ) {
                   
                    return true; // Keep this item
                }
            }
            
            return false; // Exclude this item
        })
            ->values();
 
        $dataQAD = $dataQAD->sortBy('t_inv_qtyoh')->sortBy('t_inv_wrh')->values();
        // $dataQAD = $dataQAD->sortByDesc('t_is_prioritize')->values();

        return response()->json($dataQAD);
    }

    public function wsaPenyimpananPalet(Request $req)
    {
        // $itemCode = $req->search; 
        // Request Xena 1609
        $itemCode = '';
        $warehouse = '';
        $levelsearch = '';
        $binSearch = '';

        if ($req->wh) {
            $warehouse = $req->wh;
        }
        if ($req->item) {
            $itemCode = $req->item;
        }
        if ($req->level) {
            $levelsearch = $req->level;
        }
        if ($req->bin) {
            $binSearch = $req->bin;
        }

        $wsaData = (new WSAServices())->wsaPenyimpananPalet('', $itemCode, '', $binSearch, $warehouse, $levelsearch);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        // Prioritaskan Location yang ada di Web by order.
        $getDataQAD = collect($wsaData[1]);
        if ($levelsearch != '') {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  =  is_array($item['t_inv_site']) ? '' : (string) ($item['t_inv_site'] ?? '');
                $loc   = is_array($item['t_inv_loc']) ? '' : (string)($item['t_inv_loc'] ?? '');
                $bin   = is_array($item['t_inv_bin']) ? '' : (string) ($item['t_inv_bin'] ?? '');
                $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
                $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');
                return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
            });
        } else {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  =  is_array($item['t_inv_site']) ? '' : (string) ($item['t_inv_site'] ?? '');
                $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
                $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');
                return "{$site}-{$wrh}-{$level}";
            });
        }





        $merged = $grouped->map(function ($items) {
            $first = $items->first(); // take base data from the first item
            $first['t_inv_qtyoh'] = $items->sum(function ($i) {
                return (int)$i['t_inv_qtyoh'];
            });
            return $first;
        })
            // ->filter(function ($item) {
            //     return (int) $item['t_inv_qtyoh'] <= 0;
            // })
            ->values();

        $dataQAD = $merged->sortBy('t_inv_qtyoh')->sortBy('t_inv_wrh')->values();

        return response()->json($dataQAD);
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

    public function wsaWarehouse(Request $req)
    {

        // $itemCode = $req->search; 
        // Request Xena 1609
        $itemCode = '';
        $warehouse = '';
        $type = 'input';
        if ($req->wh) {
            $warehouse = $req->wh ?? '';
        }
        if ($req->item) {
            $itemCode = $req->item ?? '';
        }
        if ($req->lot) {
            $lot = $req->lot ?? '';
        }

        $wsaData = (new WSAServices())->wsaWarehouse('', $itemCode, $lot, '', $warehouse, '');
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }



        return response()->json($wsaData[1]);
    }

    public function wsaLevel(Request $req)
    {
        $wsaData = Cache::remember('wsaLevel', 60, function () {
            return (new WSAServices())->wsaGenCode('mji_level');
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsaBin(Request $req)
    {
        $wsaData = Cache::remember('wsaBin', 60, function () {
            return (new WSAServices())->wsaGenCode('mji_bin');
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsaLoc(Request $req)
    {
        $wsaData = Cache::remember('wsaLoc', 60, function () {
            return (new WSAServices())->wsaGenCode('mji_qc');
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsaLastBatch(Request $req)
    {
        // $wsaData = Cache::remember('wsaLastBatch', 60, function () use ($req) {
        //     return (new WSAServices())->wsaLastBatch($req->search);
        // });

        $wsaData = (new WSAServices())->wsaLastBatch($req->search, $req->search2);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function getListUser(Request $req)
    {
        $data = User::query();
        // ->with('getRole')->whereRelation('getRole','role_android_acc','like','%AP01%');;
        if ($req->search) {
            $data->where('username', $req->search)
                ->orWhere('name', $req->search);
        }

        $data = $data->select('id', 'username', 'name')->get();

        return response()->json($data);
    }

    public function wsaCheckBatch(Request $req)
    {
        $wsaData = (new WSAServices())->wsaLastBatch($req->search, $req->search2);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'No Data'
            ], 200);
        }

        return response()->json($wsaData[0], 422);
    }

    public function getWebLocationData(Request $req)
    {
        $warehouse = $req->wh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $site = $req->site ?? '';
        $location = $req->location ?? '';
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        $itemQuery = Item::query()->with('getItemLocation.getLocationDetail')->where('im_item_part', $item)->first();


        $getAllItemLocation = ItemLocation::with(['getLocationDetail' => function ($query) use ($lot) {
            $query->orderBy('ld_building');
        }])
            ->where('il_item_id', $itemQuery->id);

        if ($warehouse != '') {
            $getAllItemLocation->whereRelation('getLocationDetail', 'ld_building', '=', $warehouse);
        }

        $getAllItemLocation = $getAllItemLocation->get();

        if (count($getAllItemLocation) == 0) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($getAllItemLocation);
    }
    public function wsaNewLevel(Request $req)
    {
        $part = $req->part ?? '';
        $lot = $req->lot ?? '';
        $site = $req->site ?? '';
        $wrh = $req->wh ?? '';
        $loc = $req->location ?? '';
        $level = $req->level ?? '';
        $wsaData = (new WSAServices())->wsaGetLevelForPo($part, $lot, $site, $wrh, $loc, $level);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }
        return response()->json($wsaData[1]);
        // $locationDetail = Location::query()->with(['getDetailLocation' => function($query){
        //     $query->select('ld_location_id','ld_building','ld_level')->groupBy('ld_level')->orderBy('ld_level');}])
        //     ->where('location_site', $site)
        //     ->where('location_code', $loc)
        //     ->whereRelation('getDetailLocation', 'ld_building', '=', $wrh);
        //     if ($level != '') {
        //         $locationDetail->whereRelation('getDetailLocation', 'ld_level', '=', $level);
        //     }

        //     $getAllItemLocation = $locationDetail->get();

        //     if (count($getAllItemLocation) == 0) {
        //         return response()->json([
        //             'Status' => 'Error',
        //             'Message' => "No Data Available"
        //         ], 422);
        //     }
        //     return response()->json($getAllItemLocation);

    }

    public function wsaNewBin(Request $req)
    {
        $part = $req->part ?? '';
        $lot = $req->lot ?? '';
        $site = $req->site ?? '';
        $wrh = $req->wh ?? '';
        $loc = $req->loc ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $wsaData = (new WSAServices())->wsaGetBinForPo($part, $lot, $site, $wrh, $loc, $level, $bin);

        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
        //     $locationDetail = Location::query()->with(['getDetailLocation' => function($query){
        // $query->select('ld_location_id','ld_building','ld_level','ld_bin')->groupBy('ld_bin')->orderBy('ld_bin');}])
        // ->where('location_site', $site)
        // ->where('location_code', $loc)
        // ->whereRelation('getDetailLocation', 'ld_building', '=', $wrh)
        // ->whereRelation('getDetailLocation', 'ld_level', '=', $level);
        // if ($bin != '') {
        //     $locationDetail->whereRelation('getDetailLocation', 'ld_bin', '=', $bin);
        // }

        // $getAllItemLocation = $locationDetail->get();

        // if (count($getAllItemLocation) == 0) {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // }
        // return response()->json($getAllItemLocation);
    }
    public function wsaGetPotensi(Request $req)
    {
        $part = $req->part ?? '';
        $lot = $req->lot ?? '';
        $site = $req->site ?? '';
        $wrh = $req->wrh ?? '';
        $loc = $req->loc ?? '';
        $level = $req->level ?? '';
        $wsaData = (new WSAServices())->wsaGetPotensi($part, $lot, $site, $loc);

        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }


    public function getAllWarehouse(Request $req)
    {
        $warehouse = $req->wh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $site = $req->site ?? '';
        $loc = $req->location ?? '';
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        $warehouse = LocationDetail::where('ld_location_id', $location->id)->groupBy('ld_building')->orderBy('ld_building')->select('ld_building')->get();
        return response()->json($warehouse);
    }
    public function getAllLevel(Request $req)
    {

        $warehouse = $req->wh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $site = $req->site ?? '';
        $loc = $req->location ?? '';
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        $level = LocationDetail::where('ld_location_id', $location->id)->where('ld_building', $warehouse)->groupBy('ld_rak')->orderBy('ld_rak')->select('ld_rak')->get();

        return response()->json($level);
    }

    public function getAllBin(Request $req)
    {

        $warehouse = $req->wh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $site = $req->site ?? '';
        $loc = $req->location ?? '';
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        $bin = LocationDetail::where('ld_location_id', $location->id)->where('ld_building', $warehouse)->where('ld_rak', $level)->groupBy('ld_bin')->orderBy('ld_bin')->select('ld_bin')->get();

        return response()->json($bin);
    }

    public function getWebLocationDataReceipt(Request $req)
    {
        $warehouse = $req->wh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $site = $req->site ?? '';
        $loc = $req->location ?? '';
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        $locationdetail = LocationDetail::where('ld_location_id', $location->id)->where('ld_building', $warehouse)->where('ld_rak', $level)->where('ld_bin', $bin)->first();




        $getAllItemLocation = ItemLocation::with(['getLocationDetail' => function ($query) use ($lot) {
            $query->orderBy('ld_building');
        }])

            ->whereRelation('getLocationDetail', 'ld_location_id', $location->id);

        // if($lot != ''){
        //     $getAllItemLocation->whereRelation('getLocationDetail', 'ld_lot_serial', '=', $lot);
        // }
        if ($item != '') {
            $itemQuery = Item::query()->with('getItemLocation.getLocationDetail')->where('im_item_part', $item)->first();
            $getAllItemLocation->where('il_item_id', $itemQuery->id);
        }
        if ($warehouse != '') {
            $getAllItemLocation->whereRelation('getLocationDetail', 'ld_building', '=', $warehouse);
        }
        if ($level != '') {
            $getAllItemLocation->whereRelation('getLocationDetail', 'ld_rak', '=', $level);
        }
        if ($bin != '') {
            $getAllItemLocation->whereRelation('getLocationDetail', 'ld_bin', '=', $bin);
        }

        $getAllItemLocation = $getAllItemLocation->get();


        if (count($getAllItemLocation) == 0) {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }
        $hasil = (new WSAServices())->wsaGetWlb($item, $lot, $site, $loc, $warehouse, $level, $bin);
        // if ($hasil[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Data Not Found."
        //     ], 422);
        // } 

        if ($hasil[0] == 'true') {
            $wsaData = collect($hasil[1]);

            // Add qty to each locationDetail
            $getAllItemLocation->transform(function ($location) use ($wsaData) {
                // Match based on location detail properties
                $matchingWsa = $wsaData
                    ->where('t_wrh', $location->getLocationDetail->ld_building)
                    ->where('t_level', $location->getLocationDetail->ld_rak)
                    ->where('t_bin', $location->getLocationDetail->ld_bin)
                    ->first();

                // Add qty to getLocationDetail
                $location->getLocationDetail->qty = $matchingWsa['t_qtyoh'] ?? 0;

                return $location;
            });
        }

        return response()->json($getAllItemLocation);
    }

    public function wsaPenyimpananPaletSearch(Request $req)
    {
        // $itemCode = $req->search; 
        // Request Xena 1609
        $itemCode = '';
        $warehouse = '';
        $levelsearch = '';
        $binSearch = '';

        if ($req->wh) {
            $warehouse = $req->wh ?? '';
        }
        if ($req->item) {
            $itemCode = $req->item;
        }
        if ($req->level) {
            $levelsearch = $req->level ?? '';
        }
        if ($req->bin) {
            $binSearch = $req->bin ?? '';
        }

        // Ambil Relati Item ke Location di Web
        // $getAllItemLocation = LocationDetail::query()->with(['getListItem.getItem', 'getMaster']);
        // if ($itemCode) {
        //     $getAllItemLocation->whereRelation('getListItem.getItem', 'im_item_part', '=', $itemCode);
        // }
        // if ($req->wh) {
        //     $getAllItemLocation->where('ld_building', $warehouse);
        // }
        // $getAllItemLocation = $getAllItemLocation->get();

        $receiptDetail = ReceiptDetail::query()->where('rd_status', '!=', 'Approved')->where('rd_status', '!=', 'Reject');
        if ($warehouse != '') {
            $receiptDetail->where('rd_building_penyimpanan', $warehouse);
        }
        if ($levelsearch != '') {
            $receiptDetail->where('rd_level_penyimpanan', $levelsearch);
        }
        if ($binSearch != '') {
            $receiptDetail->where('rd_bin_penyimpanan', $binSearch);
        }
        
        $receiptDetail = $receiptDetail
            ->select('rd_building_penyimpanan', 'rd_level_penyimpanan', 'rd_bin_penyimpanan')
            ->distinct()
            ->get();


        $wsaData = (new WSAServices())->wsaPenyimpananPalet('', $itemCode, '', $binSearch, $warehouse, $levelsearch);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        // Prioritaskan Location yang ada di Web by order.
        $getDataQAD = collect($wsaData[1]);
        // dd($getDataQAD);
        if ($levelsearch != '') {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  =  is_array($item['t_inv_site']) ? '' : (string) ($item['t_inv_site'] ?? '');
                $loc   = is_array($item['t_inv_loc']) ? '' : (string)($item['t_inv_loc'] ?? '');
                $bin   = is_array($item['t_inv_bin']) ? '' : (string) ($item['t_inv_bin'] ?? '');
                $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
                $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');
                return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
            });
        } else {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  =  is_array($item['t_inv_site']) ? '' : (string) ($item['t_inv_site'] ?? '');
                $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
                $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');
                return "{$site}-{$wrh}-{$level}";
            });
        }





        $merged = $grouped->map(function ($items) {
            $first = $items->first(); // take base data from the first item
            $first['t_inv_qtyoh'] = $items->sum(function ($i) {
                return (int)$i['t_inv_qtyoh'];
            });
            return $first;
        })
            // ->filter(function ($item) {
            //     return (int) $item['t_inv_qtyoh'] <= 0;
            // })
            ->values();

        // $dataQAD = $merged->sortBy('t_inv_qtyoh')->sortBy('t_inv_wrh')->values();

        // return response()->json($dataQAD);


        $dataQAD = $merged->map(function ($item) use ($receiptDetail) {
            foreach ($receiptDetail as $datas) {
                if (
                    $item['t_inv_wrh'] == $datas->rd_building_penyimpanan &&
                    $item['t_inv_level'] == $datas->rd_level_penyimpanan &&
                    $item['t_inv_bin'] == $datas->rd_bin_penyimpanan

                ) {
                    $item['t_is_prioritize'] = '1';
                    break;
                }
            }
            return $item;
        });


        // $dataQAD = $dataQAD->where('t_is_prioritize','0')->values();
        $dataQAD = $dataQAD->where('t_is_prioritize', '0')
            ->sortBy('t_inv_wrh')
            ->sortBy('t_inv_qtyoh')
            ->values();
        return response()->json($dataQAD);
    }
}
