<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\API\ReceiptAttachment;
use App\Models\API\ReceiptDetail;
use App\Models\API\ReceiptPallet;
use App\Models\API\ReceiptMaster;
use App\Models\API\xxinvDet;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\LocationDetail;
use App\Models\Settings\Location;
use App\Models\API\TransactionHistory;
use App\Models\Settings\User;
use App\Models\Settings\Item;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptServices;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class APIPurchaseOrderController extends Controller
{
    public function index(Request $req)
    {
        $data = PurchaseOrderMaster::query()->with([
            'getDetail',
            'getReceipt' => function ($query) {
                $query->orderBy('created_at', 'desc'); // order receipts
            },
            'getReceipt.getDetailReceipt' => function ($query) {
                $query->orderBy('created_at', 'desc'); // order receipt details
            },
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
        $poMasterID = $inputan[0]->id_po_mstr;

        $poddet = PurchaseOrderDetail::where('pod_po_mstr_id', $poMasterID)->first();
        log::info($inputan);
        if ($poddet) {
            $poddet->pod_qty_rcpt = $poddet->pod_qty_rcpt + $inputan[0]->total;
            $poddet->save();
        }

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
        log::info('masuk');
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
        if ($req->item) {
            $itemCode = $req->item;
        }
        log::info('getdata');
        // Ambil Relati Item ke Location di Web
        $getAllItemLocation = LocationDetail::query()->with(['getListItem.getItem', 'getMaster']);
        if ($itemCode) {
            $getAllItemLocation->whereRelation('getListItem.getItem', 'im_item_part', '=', $itemCode);
        }
        if ($req->wh) {
            $getAllItemLocation->where('ld_building', $warehouse);
        }
        $getAllItemLocation = $getAllItemLocation->get();
        log::info('gettable');
        // Ambil List Location di QAD untuk dibanding ke Web
        // $wsaData = Cache::remember('wsaPenyimpanan', 60, function () use ($itemCode) {
        //     return (new WSAServices())->wsaPenyimpanan('', $itemCode, '', '', '', '');
        // });
        $wsa = qxwsa::first();
        $qxUrl = $wsa->wsa_url;

        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $getDataQAD = xxinvDet::where('xxinv_domain', $domainCode)
            ->where('xxinv_part', $itemCode)
            ->where('xxinv_wrh', $warehouse)
            ->get();

        $grouped = $getDataQAD->groupBy(function ($item) {
            $site  = (string) ($item->xxinv_site ?? '');
            $loc   = (string) ($item->xxinv_loc ?? '');
            $bin   = (string) ($item->xxinv_bin ?? '');
            $wrh   = (string) ($item->xxinv_wrh ?? '');
            $level = (string) ($item->xxinv_level ?? '');

            return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
        });

        $merged = $grouped->map(function ($items) {
            $first = $items->first();
            $first->xxinv_qtyoh = $items->sum(fn($i) => (int) $i->xxinv_qtyoh);
            return $first;
        })
            ->filter(function ($item) {
                return (int) $item->xxinv_qtyoh <= 0;
            })
            ->values();

        $dataQAD = $merged->filter(function ($item) use ($getAllItemLocation) {
            foreach ($getAllItemLocation as $datas) {
                if (
                    $item->xxinv_level == $datas->ld_rak &&
                    $item->xxinv_wrh   == $datas->ld_building &&
                    $item->xxinv_bin   == $datas->ld_bin &&
                    $item->xxinv_loc   == $datas->getMaster->location_code
                ) {
                    return true;
                }
            }
            return false;
        })
            ->values();

        $dataQAD = $dataQAD->sortBy('xxinv_qtyoh')->sortBy('xxinv_wrh')->values();

        return response()->json($dataQAD);
        /*
        $wsaData = (new WSAServices())->wsaPenyimpanan('', $itemCode, '', '', $warehouse, '');
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        // Prioritaskan Location yang ada di Web by order.
        $getDataQAD = collect($wsaData[1]);
        
        //log::info('getwsa');
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
        // log::info('dataqad: ' . $getDataQAD);
        // log::info('dataweb: ' . $getAllItemLocation);
        // log::info('merged: ' . $merged);
        // log::info('dataqad final: ' . $dataQAD);
        return response()->json($dataQAD);
        */
    }

    public function wsaPenyimpananWarehouse(Request $req)
    {
        // $itemCode = $req->search; 
        // Request Xena 1609
        log::info(carbon::now());
        $itemCode = '';
        $warehouse = '';
        $type = 'input';
        if ($req->wh) {
            $warehouse = $req->wh;
        }
        if ($req->search) {
            $warehouse = $req->search;
        }
        if ($req->item) {
            $itemCode = $req->item;
        }

        // Ambil Relati Item ke Location di Web
        $getAllItemLocation = LocationDetail::query()
            // ->select('ld_building as xxinv_wrh')
            ->with(['getListItem.getItem', 'getMaster']);
        if ($itemCode) {
            $getAllItemLocation->whereRelation('getListItem.getItem', 'im_item_part', '=', $itemCode);
        }
        if ($req->wh) {
            $getAllItemLocation->where('ld_building', $warehouse);
        }
        $getAllItemLocation = $getAllItemLocation->get();
        // return response()->json($getAllItemLocation);
        // dd($getAllItemLocation);
        // Ambil List Location di QAD untuk dibanding ke Web
        // $wsaData = Cache::remember('wsaPenyimpanan', 60, function () use ($itemCode) {
        //     return (new WSAServices())->wsaPenyimpanan('', $itemCode, '', '', '', '');
        // });


        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        /*
        $wsaData = (new WSAServices())->wsaPenyimpananWrh('', $itemCode, '', '', $warehouse, '');

        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        // Prioritaskan Location yang ada di Web by order.
        $getDataQAD = collect($wsaData[1]);
        log::info($getDataQAD);
        //log::info($getDataQAD);
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
       
        // log::info($merged);
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
        //log::info(carbon::now());
        // log::info('dataqad final: ' . $dataQAD);
        // log::info($getAllItemLocation);
        // $dataQAD = $dataQAD->sortByDesc('t_is_prioritize')->values();
   
        return response()->json($dataQAD);
        */

        /**get daata from sql   */
        $xxinvDet = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->when($itemCode !== '', fn($q) => $q->where('xxinv_part', $itemCode))
            ->when($warehouse !== '', fn($q) => $q->where('xxinv_wrh', $warehouse))
            ->get();
        $getDataQAD = $xxinvDet;



        $grouped = $getDataQAD->groupBy(function ($item) {
            $site  = (string) ($item['xxinv_site'] ?? '');
            $loc   = (string) ($item['xxinv_loc'] ?? '');
            $bin   = (string) ($item['xxinv_bin'] ?? '');
            $wrh   = (string) ($item['xxinv_wrh'] ?? '');
            $level = (string) ($item['xxinv_level'] ?? '');

            return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
        });

        $merged = $grouped->map(function ($items) {
            $first = $items->first();
            $first['xxinv_qtyoh'] = $items->sum(function ($i) {
                return (int) $i['xxinv_qtyoh'];
            });
            return $first;
        })
            ->filter(function ($item) {
                return (int) $item['xxinv_qtyoh'] <= 0;
            })
            ->values();
        $dataQAD = $merged->filter(function ($item) use ($getAllItemLocation) {
            foreach ($getAllItemLocation as $datas) {
                if (
                    $this->normalize($item['xxinv_level']) == $this->normalize($datas->ld_rak) &&
                    $this->normalize($item['xxinv_wrh']) == $this->normalize($datas->ld_building) &&
                    $this->normalize($item['xxinv_bin']) == $this->normalize($datas->ld_bin) &&
                    $this->normalize($item['xxinv_loc']) == $this->normalize($datas->getMaster->location_code)
                ) {
                    return true;
                }
            }
            return false;
        })->values();
      

        // $dataQAD = $merged->filter(function ($item) use ($getAllItemLocation) {
        //     foreach ($getAllItemLocation as $datas) {
        //         // dd($datas->getItem);
        //         // dd($item['xxinv_level'],$datas->ld_rak,$item['xxinv_wrh'],$datas->ld_building,$item['xxinv_bin'],$datas->ld_bin,$item['xxinv_loc'],$datas->getMaster->location_code);
        //         if (
        //             $item['xxinv_level'] == $datas->ld_rak &&
        //             $item['xxinv_wrh'] == $datas->ld_building &&
        //             $item['xxinv_bin'] == $datas->ld_bin && 
        //             $item['xxinv_loc'] == $datas->getMaster->location_code
        //         ) {
        //             return true; // Keep this item
        //         }
        //     }

        //     return false; // Exclude this item
        // })
        //     ->values();
        // dd($dataQAD);
        // $dataQAD = $dataQAD->select('xxinv_wrh')->groupBy('xxinv_wrh')->sortBy('xxinv_qtyoh')->sortBy('xxinv_wrh')->values();
        $dataQAD = $dataQAD
    ->groupBy('xxinv_wrh')
    ->map(function ($items) {
        $first = $items->first();
        $first['xxinv_qtyoh'] = $items->sum('xxinv_qtyoh'); // sum() accepts a key shorthand too
        return $first;
    })
    ->sortBy('xxinv_wrh')
    ->values();
        // dd($dataQAD);
        return response()->json($dataQAD);
    }
    private function normalize($value)
    {
        return preg_replace('/\s+/', '', (string) $value); // removes ALL whitespace
    }
    public function wsaPenyimpananPalet(Request $req)
    {
        // $itemCode = $req->search; 
        // Request Xena 1609
        $itemCode = '';
        $warehouse = '';
        $levelsearch = '';
        $binSearch = '';
        $location = '';
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
        if ($req->location) {
            $location = $req->location;
        }
        /*
        $wsaData = (new WSAServices())->wsaPenyimpananPalet('', $itemCode, '', $binSearch, $warehouse, $levelsearch, $location);
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
        */
        //$wsaData = (new WSAServices())->wsaPenyimpananPalet('', $itemCode, '', $binSearch, $warehouse, $levelsearch, $location);
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $xxinvDet = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->where('xxinv_part', $itemCode)
            ->where('xxinv_wrh', $warehouse)
            ->when($location !== '', fn($q) => $q->where('xxinv_loc', $location))
            ->when($binSearch !== '', fn($q) => $q->where('xxinv_bin', $binSearch))
            ->when($levelsearch !== '', fn($q) => $q->where('xxinv_level', $levelsearch))
            ->orderBy('xxinv_level')
            ->orderBy('xxinv_bin')
            ->get();


        $getDataQAD = $xxinvDet;

        if ($levelsearch != '') {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  = is_array($item['xxinv_site'])  ? '' : (string)($item['xxinv_site']  ?? '');
                $loc   = is_array($item['xxinv_loc'])   ? '' : (string)($item['xxinv_loc']   ?? '');
                $bin   = is_array($item['xxinv_bin'])   ? '' : (string)($item['xxinv_bin']   ?? '');
                $wrh   = is_array($item['xxinv_wrh'])   ? '' : (string)($item['xxinv_wrh']   ?? '');
                $level = is_array($item['xxinv_level']) ? '' : (string)($item['xxinv_level'] ?? '');
                return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
            });
        } else {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  = is_array($item['xxinv_site'])  ? '' : (string)($item['xxinv_site']  ?? '');
                $wrh   = is_array($item['xxinv_wrh'])   ? '' : (string)($item['xxinv_wrh']   ?? '');
                $level = is_array($item['xxinv_level']) ? '' : (string)($item['xxinv_level'] ?? '');
                return "{$site}-{$wrh}-{$level}";
            });
        }

        $merged = $grouped->map(function ($items) {
            $first = $items->first();
            $first['xxinv_qtyoh'] = $items->sum(function ($i) {
                return (int)$i['xxinv_qtyoh'];
            });
            return $first;
        })->values();

        $dataQAD = $merged->sortBy('xxinv_qtyoh')->sortBy('xxinv_wrh')->values();

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
        // $domain = Domain::first();
        // $domainCode = $domain->domain ?? '';
        // $xxinvDet = xxinvDet::query()
        //     ->where('xxinv_domain', $domainCode)
        //     ->where('xxinv_part', $itemCode)
        //     ->when($warehouse !== '', fn($q) => $q->where('xxinv_wrh', $warehouse))
        //     ->orderBy('xxinv_level')
        //     ->orderBy('xxinv_bin')
        //     ->get();
        /*
        $wsaData = (new WSAServices())->wsaWarehouse('', $itemCode, $lot, '', $warehouse, '');
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
        */
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';

        $xxinvDet = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->where('xxinv_part', $itemCode)

            ->when($warehouse !== '', fn($q) => $q->where('xxinv_wrh', $warehouse))
            ->groupBy('xxinv_wrh')
            ->get();

        return response()->json($xxinvDet);
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

        /*
        $wsaData = (new WSAServices())->wsaGetLevelForPo($part, $lot, $site, $wrh, $loc, $level);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }
        return response()->json($wsaData[1]);
        */
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $xxinvDet = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->when($part !== '', fn($q) => $q->where('xxinv_part', $part))
            ->when($lot !== '', fn($q) => $q->where('xxinv_lot', $lot))
            ->when($level !== '', fn($q) => $q->where('xxinv_level', $level))
            ->where('xxinv_site', $site)
            ->where('xxinv_loc', $loc)
            ->where('xxinv_wrh', $wrh)
            ->orderBy('xxinv_level')
            ->get();
        return response()->json($xxinvDet);
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
        // $wsaData = (new WSAServices())->wsaGetBinForPo($part, $lot, $site, $wrh, $loc, $level, $bin);

        // if ($wsaData[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // }
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $xxinvDet = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->when($part !== '', fn($q) => $q->where('xxinv_part', $part))
            ->when($lot !== '', fn($q) => $q->where('xxinv_lot', $lot))
            ->where('xxinv_site', $site)
            ->where('xxinv_loc', $loc)
            ->where('xxinv_wrh', $wrh)
            ->where('xxinv_level', $level)
            ->when($bin !== '', fn($q) => $q->where('xxinv_bin', $bin))
            ->where('xxinv_qtyoh', '=', 0)
            ->orderBy('xxinv_bin')
            ->get();
        return response()->json($xxinvDet);
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
        // $wsaData = (new WSAServices())->wsaGetPotensi($part, $lot, $site, $loc);

        // if ($wsaData[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // }
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $xxinvDet = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->where('xxinv_part', $part)
            ->where('xxinv_lot', $lot)
            ->where('xxinv_site', $site)
            ->first();

        return response()->json($xxinvDet);
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
        $search = $req->search ?? '';
        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        $warehouse = LocationDetail::query()
            ->where('ld_location_id', $location->id);

        if ($search != '') {
            $warehouse = $warehouse->whereRaw('LOWER(ld_building) LIKE ?', ['%' . strtolower($search) . '%']);
            // $warehouse->where('ld_building', 'like', '%' . $search . '%');
        }

        $warehouse = $warehouse
            ->select('ld_building')
            ->groupBy('ld_building')
            ->orderBy('ld_building')
            ->get();
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
        $search = $req->search ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        $level = LocationDetail::query()
            ->where('ld_location_id', $location->id)
            ->where('ld_building', $warehouse);

        if ($search != '') {
            $level = $level->whereRaw('LOWER(ld_rak) LIKE ?', ['%' . strtolower($search) . '%']);
            //$level = $level->where('ld_rak', 'like', '%' . $search . '%');
        }

        $level = $level->groupBy('ld_rak')->orderBy('ld_rak')->select('ld_rak')->get();

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
        $search = $req->search ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        $bin = LocationDetail::query()
            ->where('ld_location_id', $location->id)
            ->where('ld_building', $warehouse)
            ->where('ld_rak', $level);
        if ($search != '') {
            $bin = $bin->whereRaw('LOWER(ld_bin) LIKE ?', ['%' . strtolower($search) . '%']);
            // $bin = $bin->where('ld_bin','like','%'.$search.'%');
        }
        $bin = $bin->groupBy('ld_bin')->orderBy('ld_bin')->select('ld_bin')->get();

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
        //$hasil = (new WSAServices())->wsaGetWlb($item, $lot, $site, $loc, $warehouse, $level, $bin);
        // if ($hasil[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "Data Not Found."
        //     ], 422);
        // } 
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $xxinvDet = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->when($item !== '', fn($q) => $q->where('xxinv_part', $item))
            ->when($lot !== '', fn($q) => $q->where('xxinv_lot', $lot))
            ->when($site !== '', fn($q) => $q->where('xxinv_site', $site))
            ->when($loc !== '', fn($q) => $q->where('xxinv_loc', $loc))
            ->when($warehouse !== '', fn($q) => $q->where('xxinv_wrh', $warehouse))
            ->when($level !== '', fn($q) => $q->where('xxinv_level', $level))
            ->when($bin  !== '', fn($q) => $q->where('xxinv_bin',  $bin))
            ->get();

        if ($xxinvDet->isNotEmpty()) {
            $wsaData = $xxinvDet;

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

    //mira
    public function wsaWOPrint(Request $req)
    {
        $wo = $req->query('wo');
        $site = $req->query('site');
        $part = $req->query('part');
        $lot = $req->query('lot');

        $wsaData = (new WSAServices())->wsaGetWOPrint($wo, $site, $part, $lot);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    //mira
    public function wsaWOMaster(Request $req)
    {
        // $wo = $req->query('wo');
        // $site = $req->query('site');
        // $part = $req->query('part');
        // $lot = $req->query('lot');
        $$wonbr = $req->query('wonbr');

        $wsaData = (new WSAServices())->wsaGetWOMstr($wonbr);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Available"
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsaPenyimpananPaletSearch(Request $req)
    {
        // $itemCode = $req->search; 
        // Request Xena 1609
        $itemCode = '';
        $warehouse = '';
        $levelsearch = '';
        $binSearch = '';
        $search = '';
        $location = '';
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
        if ($req->search) {
            $search = $req->search; // Capture the search parameter
        }
        if ($req->location) {
            $location = $req->location; // Capture the location parameter
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

        // $receiptDetail = ReceiptDetail::with('getPallet')->query()->where('rd_status', '!=', 'Approved')->where('rd_status', '!=', 'Reject');
        // if ($warehouse != '') {
        //     $receiptDetail->where('rd_building_penyimpanan', $warehouse);
        // }
        // if ($levelsearch != '') {
        //     $receiptDetail->where('rd_level_penyimpanan', $levelsearch);
        // }
        // if ($binSearch != '') {
        //     $receiptDetail->where('rd_bin_penyimpanan', $binSearch);
        // }

        // $receiptDetail = $receiptDetail
        //     ->select('rd_building_penyimpanan', 'rd_level_penyimpanan', 'rd_bin_penyimpanan')
        //     ->distinct()
        //     ->get();
        $receiptDetail = ReceiptPallet::with('getDetail')
            ->whereRelation('getDetail', 'rd_status', '!=', 'Approved')
            ->whereRelation('getDetail', 'rd_status', '!=', 'Reject')
            ->distinct()
            ->get();

        // log::info('receiptDetail', [$receiptDetail]);

        // $wsaData = (new WSAServices())->wsaPenyimpananPalet('', $itemCode, '', $binSearch, $warehouse, $levelsearch, $location);
        // if ($wsaData[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // }

        // // Prioritaskan Location yang ada di Web by order.
        // $getDataQAD = collect($wsaData[1]);

        // // dd($getDataQAD);
        // if ($levelsearch != '') {
        //     $grouped = $getDataQAD->groupBy(function ($item) {
        //         $site  =  is_array($item['t_inv_site']) ? '' : (string) ($item['t_inv_site'] ?? '');
        //         $loc   = is_array($item['t_inv_loc']) ? '' : (string)($item['t_inv_loc'] ?? '');
        //         $bin   = is_array($item['t_inv_bin']) ? '' : (string) ($item['t_inv_bin'] ?? '');
        //         $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
        //         $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');
        //         return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
        //     });
        // } else {
        //     $grouped = $getDataQAD->groupBy(function ($item) {
        //         $site  =  is_array($item['t_inv_site']) ? '' : (string) ($item['t_inv_site'] ?? '');
        //         $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
        //         $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');
        //         $bin   = is_array($item['t_inv_bin']) ? '' : (string) ($item['t_inv_bin'] ?? '');
        //         return "{$site}-{$wrh}-{$level}-{$bin}";
        //     });
        // }





        // $merged = $grouped->map(function ($items) {
        //     $first = $items->first(); // take base data from the first item
        //     $first['t_inv_qtyoh'] = $items->sum(function ($i) {
        //         return (int)$i['t_inv_qtyoh'];
        //     });
        //     return $first;
        // })
        //     // ->filter(function ($item) {
        //     //     return (int) $item['t_inv_qtyoh'] <= 0;
        //     // })
        //     ->values();



        // $dataQAD = $merged->map(function ($item) use ($receiptDetail) {
        //     foreach ($receiptDetail as $datas) {

        //         if (
        //             $item['t_inv_wrh'] == $datas->getDetail->rd_building_penyimpanan &&
        //             $item['t_inv_level'] == $datas->rdp_level_penyimpanan &&
        //             $item['t_inv_bin'] == $datas->rdp_bin_penyimpanan
        //         ) {
        //             $item['t_is_prioritize'] = '1';
        //             break;
        //         }
        //     }
        //     return $item;
        // });

        // // Add search filter for level OR bin
        // if ($search != '') {
        //     $dataQAD = $dataQAD->filter(function ($item) use ($search) {
        //         $level = is_array($item['t_inv_level']) ? '' : (string)($item['t_inv_level'] ?? '');
        //         $bin = is_array($item['t_inv_bin']) ? '' : (string)($item['t_inv_bin'] ?? '');

        //         // Search in both level and bin (case-insensitive partial match)
        //         return stripos($level, $search) !== false || stripos($bin, $search) !== false;
        //     });
        // }
        // // $dataQAD = $dataQAD->where('t_is_prioritize','0')->values();
        // $dataQAD = $dataQAD->where('t_is_prioritize', '0')
        //     ->sortBy('t_inv_wrh')
        //     ->sortBy('t_inv_qtyoh')
        //     ->values();
        // return response()->json($dataQAD);
        $domain = Domain::first();
        $domainCode = $domain->domain ?? '';
        $results = xxinvDet::query()
            ->where('xxinv_domain', $domainCode)
            ->where('xxinv_part', $itemCode)
            ->where('xxinv_wrh', $warehouse)
            ->when($location   !== '', fn($q) => $q->where('xxinv_loc',   $location))
            ->when($binSearch   !== '', fn($q) => $q->where('xxinv_bin',   $binSearch))
            ->when($levelsearch !== '', fn($q) => $q->where('xxinv_level', $levelsearch))
            ->orderBy('xxinv_level')
            ->orderBy('xxinv_bin')
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'Status'  => 'Error',
                'Message' => 'No Data Available'
            ], 422);
        }

        $temp       = collect();
        $totalQtyoh = 0;

        foreach ($results as $row) {
            $totalQtyoh += $row->xxinv_qtyoh;

            $isLastOfBin = $results->last(fn($r) => $r->xxinv_bin === $row->xxinv_bin) === $row;

            if ($isLastOfBin) {
                // Push as ARRAY to match downstream [] access
                $temp->push([
                    't_domain'        => $domainCode,
                    't_inv_part'      => $row->xxinv_part,
                    't_inv_part_desc' => '',
                    't_inv_loc'       => $row->xxinv_loc,
                    't_inv_lot'       => $row->xxinv_lot,
                    't_inv_bin'       => $row->xxinv_bin,
                    't_inv_level'     => $row->xxinv_level,
                    't_inv_site'      => $row->xxinv_site,
                    't_inv_wrh'       => $row->xxinv_wrh,
                    't_inv_qty_pick'  => $row->xxinv_qty_pick,
                    't_inv_qtyoh'     => $totalQtyoh,
                    't_is_prioritize' => '0',
                ]);

                $totalQtyoh = 0;
            }
        }

        // Use $temp (processed) instead of $results (raw DB rows)
        $getDataQAD = $temp;

        if ($levelsearch != '') {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  = (string)($item['t_inv_site']  ?? '');
                $loc   = (string)($item['t_inv_loc']   ?? '');
                $bin   = (string)($item['t_inv_bin']   ?? '');
                $wrh   = (string)($item['t_inv_wrh']   ?? '');
                $level = (string)($item['t_inv_level'] ?? '');
                return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
            });
        } else {
            $grouped = $getDataQAD->groupBy(function ($item) {
                $site  = (string)($item['t_inv_site']  ?? '');
                $wrh   = (string)($item['t_inv_wrh']   ?? '');
                $level = (string)($item['t_inv_level'] ?? '');
                $bin   = (string)($item['t_inv_bin']   ?? '');
                return "{$site}-{$wrh}-{$level}-{$bin}";
            });
        }

        $merged = $grouped->map(function ($items) {
            $first = $items->first();
            $first['t_inv_qtyoh'] = $items->sum(fn($i) => (int)$i['t_inv_qtyoh']);
            return $first;
        })->values();

        $dataQAD = $merged->map(function ($item) use ($receiptDetail) {
            foreach ($receiptDetail as $datas) {
                if (
                    $item['t_inv_wrh']   == $datas->getDetail->rd_building_penyimpanan &&
                    $item['t_inv_level'] == $datas->rdp_level_penyimpanan &&
                    $item['t_inv_bin']   == $datas->rdp_bin_penyimpanan
                ) {
                    $item['t_is_prioritize'] = '1';
                    break;
                }
            }
            return $item;
        });

        if ($search != '') {
            $dataQAD = $dataQAD->filter(function ($item) use ($search) {
                $level = (string)($item['t_inv_level'] ?? '');
                $bin   = (string)($item['t_inv_bin']   ?? '');
                return stripos($level, $search) !== false || stripos($bin, $search) !== false;
            });
        }

        $dataQAD = $dataQAD->where('t_is_prioritize', '0')
            ->sortBy('t_inv_wrh')
            ->sortBy('t_inv_qtyoh')
            ->values();

        return response()->json($dataQAD);
    }
    public function deleteDraft(Request $req)
    {

        DB::beginTransaction();
        try {
            $id = $req->id;

            $data = ReceiptDetail::with([
                'getMaster',
                'getPurchaseOrderDetail.getMaster',
                'getPallet',
                'getAttachment',
                'getDokumen',
                'getKemasan',
                'getKendaraan',
                'getPenanda',

                'getUserSeenBy',
                'getApprovalTemp',
                'getApprovalHist'
            ])->findOrFail($id);
            $master = ReceiptMaster::findOrFail($data->rd_rm_id);
            $getPurchaseOrderDetail = $data->getPurchaseOrderDetail;
            $getPallet = $data->getPallet;
            foreach ($getPallet as $plt) {


                $newTransactionHistory = new TransactionHistory();
                $newTransactionHistory->tr_nbr = $data->getMaster->rm_rn_number;
                $newTransactionHistory->tr_order = $getPurchaseOrderDetail->getMaster->po_nbr;
                $newTransactionHistory->tr_program = 'PO Approval Module';
                $newTransactionHistory->tr_activity = 'Delete Receipt';
                $newTransactionHistory->tr_user =  Auth::user()->username ?? '';
                // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                $newTransactionHistory->tr_part = $getPurchaseOrderDetail->pod_part ?? '';
                $newTransactionHistory->tr_uom = $data->rd_pt_um ?? '';
                $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                $newTransactionHistory->tr_lot = $data->rd_batch ?? '';
                $newTransactionHistory->tr_qty = $data->rd_qty_terima ?? '';
                $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                $newTransactionHistory->tr_reference = $data->rd_kode_cetak ?? '';
                $newTransactionHistory->tr_site = $data->rd_site_penyimpanan ?? '';
                $newTransactionHistory->tr_location = $data->rd_location_penyimpanan ?? '';
                $newTransactionHistory->tr_warehouse = $data->rd_building_penyimpanan ?? '';
                $newTransactionHistory->tr_level = $plt->rdp_level_penyimpanan ?? '';
                $newTransactionHistory->tr_bin = $plt->rdp_bin_penyimpanan ?? '';
                $newTransactionHistory->tr_remark = '';
                $newTransactionHistory->save();
            }

            $allDetails = ReceiptDetail::where('rd_rm_id', $master->id)->get();

            foreach ($allDetails as $detail) {
                $detail->getAttachment()->delete();
                $detail->getDokumen()->delete();
                $detail->getKemasan()->delete();
                $detail->getKendaraan()->delete();
                $detail->getPenanda()->delete();
                $detail->getPallet()->delete();
                $detail->getUserSeenBy()->delete();
                $detail->getApprovalTemp()->delete();
                $detail->getApprovalHist()->delete();
                $detail->delete(); // delete this detail after all its children
            }

            $master->delete(); // delete master only after all details are gone
            // // Delete all related records using query builder (more efficient)
            // $data->getAttachment()->delete();
            // $data->getDokumen()->delete();
            // $data->getKemasan()->delete();
            // $data->getKendaraan()->delete();
            // $data->getPenanda()->delete();
            // $data->getPallet()->delete();
            // $data->getUserSeenBy()->delete();
            // $data->getApprovalTemp()->delete();
            // $data->getApprovalHist()->delete();


            // // Delete the main record
            // $data->delete();
            // $master->delete();

            DB::commit();

            return response()->json([
                'Status' => 'Success',
                'Message' => "Data deleted successfully"
            ], 200);
        } catch (Exception $err) {
            DB::rollback();
            Log::error($err);
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed to delete data"
            ], 422);
        }
    }
}
