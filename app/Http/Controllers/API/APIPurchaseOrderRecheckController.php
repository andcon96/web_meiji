<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\PurchaseOrderMaster;
use App\Models\API\ReceiptMaster;
use App\Models\API\ReceiptDetail;
use App\Models\API\ReceiptAttachment;
use App\Models\API\ApprovalReceiptTemp;
use App\Models\API\TransactionHistory;
use App\Models\Settings\ItemLocation;
use App\Models\API\xxinvDet;
use App\Models\Settings\Domain;
use App\Models\Settings\LocationDetail;
use App\Models\Settings\User;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptServices;
use Illuminate\Support\Facades\Cache;

class APIPurchaseOrderRecheckController extends Controller
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

        $data = $data->whereRelation('getReceipt', 'rm_status', 'Waiting For Recheck')->orderBy('id', 'desc')->paginate(10);


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

    public function saveReceiptRecheck(Request $req)
    {

        $receiptnbr = $req->receiptnbr;
        $status = $req->status;
        $nomorbuku = $req->nomorbuku;
        $creator = $req->approver;
        DB::beginTransaction();
        try {
            $receiptMstr = ReceiptMaster::where('rm_rn_number', $receiptnbr)->first();
            if (!$receiptMstr) {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Receipt Number Not Found.'
                ], 422);
            } else {
                $receiptDetail = ReceiptDetail::with(['getPurchaseOrderDetail.getMaster', 'getPallet'])->where('rd_rm_id', $receiptMstr->id)
                    ->where('rd_nomor_buku', $nomorbuku)
                    ->first();
                if (!$receiptMstr) {
                    return response()->json([
                        'Status' => 'Error',
                        'Message' => 'Receipt Number Not Found.'
                    ], 422);
                } else {
                    $receiptDetail->rd_status = 'Checked';
                    $receiptDetail->save();
                    $receiptTemp = ApprovalReceiptTemp::where('art_receipt_det_id', $receiptDetail->id)
                        ->get();
                    if ($receiptTemp) {
                        foreach ($receiptTemp as $temp) {
                            $temp->art_status = 'Checked';
                            $temp->save();
                        }
                    }
                }
                $checkOtherDetail = ReceiptDetail::where('rd_rm_id', $receiptMstr->id)
                    ->where('rd_status', '!=', 'Checked')
                    ->first();
                if (!$checkOtherDetail) {
                    $receiptMstr->rm_status = $status;
                    $receiptMstr->save();
                }
                $getPurchaseOrderDetail = $receiptDetail->getPurchaseOrderDetail;
                $getPallet = $receiptDetail->getPallet;
                foreach ($getPallet as $pallet) {
                    // Transaction History
                    $newTransactionHistory = new TransactionHistory();
                    $newTransactionHistory->tr_nbr = $receiptnbr;
                    $newTransactionHistory->tr_order = $getPurchaseOrderDetail->getMaster->po_nbr;
                    $newTransactionHistory->tr_program = 'PO Receipt Module';
                    $newTransactionHistory->tr_activity = 'Verify Receipt';
                    $newTransactionHistory->tr_user = $creator ?? '';
                    // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                    $newTransactionHistory->tr_part = $getPurchaseOrderDetail->pod_part ?? '';
                    $newTransactionHistory->tr_uom = '';
                    $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                    $newTransactionHistory->tr_lot = $receiptDetail->rd_batch ?? '';
                    // $newTransactionHistory->tr_qty = str_replace(',', '', $receiptDetail->rd_qty_terima) ?? '';
                    $newTransactionHistory->tr_qty = str_replace(',', '', $pallet->rdp_qty_penyimpanan) ?? '';
                    $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                    $newTransactionHistory->tr_reference = $receiptDetail->rd_kode_cetak ?? '';
                    $newTransactionHistory->tr_site = $receiptDetail->rd_site_penyimpanan ?? '';
                    $newTransactionHistory->tr_location = $receiptDetail->rd_location_penyimpanan ?? '';
                    $newTransactionHistory->tr_warehouse = $receiptDetail->rd_building_penyimpanan ?? '';
                    $newTransactionHistory->tr_level = $pallet->rdp_level_penyimpanan ?? '';
                    $newTransactionHistory->tr_bin = $pallet->rdp_bin_penyimpanan ?? '';
                    $newTransactionHistory->tr_remark = '';
                    $newTransactionHistory->save();
                }
            }



            DB::commit();
            return response()->json([
                'Status' => 'Success',
                'Message' => 'Data Receipt Saved, Receipt Number : ' . $receiptnbr,
                'ReceiptNumber' => $receiptnbr
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error Save Receipt Recheck: ' . $e->getMessage());
            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage()
            ], 422);
        }
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
                    $newReceiptAttachment->rda_rd_det_id = $inputan->rd_pod_det_id;
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
        if ($req->wh) {
            $warehouse = $req->wh;
        }

        // Ambil Relati Item ke Location di Web
        $getAllItemLocation = LocationDetail::query()->with(['getListItem.getItem', 'getMaster']);
        if ($itemCode) {
            $getAllItemLocation->whereRelation('getListItem.getItem', 'im_item_part', '=', $itemCode);
        }
        if ($req->wh) {
            $getAllItemLocation->where('ld_building', $warehouse);
        }
        $getAllItemLocation = $getAllItemLocation->get();

        // Ambil List Location di QAD untuk dibanding ke Web
        // $wsaData = Cache::remember('wsaPenyimpanan', 60, function () use ($itemCode) {
        //     return (new WSAServices())->wsaPenyimpanan('', $itemCode, '', '', '', '');
        // });

        // $wsaData = (new WSAServices())->wsaPenyimpanan('', $itemCode, '', '', $warehouse, '');
        // if ($wsaData[0] == 'false') {
        //     return response()->json([
        //         'Status' => 'Error',
        //         'Message' => "No Data Available"
        //     ], 422);
        // }

        // Prioritaskan Location yang ada di Web by order.
        // $getDataQAD = collect($wsaData[1]);
        // $grouped = $getDataQAD->groupBy(function ($item) {
        //     $site  = (string) ($item['t_inv_site'] ?? '');
        //     $loc   = (string) ($item['t_inv_loc'] ?? '');
        //     $bin   = is_array($item['t_inv_bin']) ? '' : (string) ($item['t_inv_bin'] ?? '');
        //     $wrh   = is_array($item['t_inv_wrh']) ? '' : (string) ($item['t_inv_wrh'] ?? '');
        //     $level = is_array($item['t_inv_level']) ? '' : (string) ($item['t_inv_level'] ?? '');

        //     return "{$site}-{$loc}-{$bin}-{$wrh}-{$level}";
        //     // return $item['t_inv_site'] . '-' . $item['t_inv_loc'] . '-' . $item['t_inv_bin'] . '-' . $item['t_inv_wrh'] . '-' . $item['t_inv_level'];
        // });

        // $merged = $grouped->map(function ($items) {
        //     $first = $items->first(); // take base data from the first item
        //     $first['t_inv_qtyoh'] = $items->sum(function ($i) {
        //         return (int)$i['t_inv_qtyoh'];
        //     });
        //     return $first;
        // })
        //     ->filter(function ($item) {
        //         return (int) $item['t_inv_qtyoh'] <= 0;
        //     })
        //     ->values();

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

        // $dataQAD = $dataQAD->sortByDesc('t_is_prioritize')->values();
        //save point
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
    }

    public function wsaWarehouse(Request $req)
    {
        $wsaData = Cache::remember('wsaWarehouse', 60, function () {
            return (new WSAServices())->wsaGenCode('mji_wrh');
        });
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

    public function validateRecheck(Request $req)
    {
        $receiptnbr = $req->receiptnbr;
        $status = $req->status;
        $nomorbuku = $req->nomorbuku;
        $creator = $req->approver;
        $duplicate = false;

        
        try {
            $receiptMstr = ReceiptMaster::where('rm_rn_number', $receiptnbr)->first();
            $receiptDetail = ReceiptDetail::with('getPallet')
                ->where('rd_rm_id', $receiptMstr->id)
                ->where('rd_nomor_buku', $nomorbuku)
                ->first();
            $receiptDetailCheck = ReceiptDetail::with('getPallet') // fixed casing
                ->where('id', '!=', $receiptDetail->id)
                ->where('rd_site_penyimpanan', $receiptDetail->rd_site_penyimpanan)
                ->where('rd_location_penyimpanan', $receiptDetail->rd_location_penyimpanan)
                ->whereIn('rd_status', ['checked', 'draft'])
                ->get();

            foreach ($receiptDetail->getPallet as $rp) {
                foreach ($receiptDetailCheck as $rdc) {
                    foreach ($rdc->getPallet as $rp2) {
                        if (
                            $rp->rdp_bin_penyimpanan == $rp2->rdp_bin_penyimpanan &&
                            $rp->rdp_level_penyimpanan == $rp2->rdp_level_penyimpanan &&
                            $rp->rdp_warehouse_penyimpanan == $rp2->rdp_warehouse_penyimpanan
                        ) {
                            $duplicate = true;
                            break 3; // fixed: breaks all 3 loops
                        }
                    }
                }
            }
            Log::info('duplicate:' . var_export($duplicate, true)); // "true" or "false"
            return response()->json([
                'Status' => 'Success',
                'Message' => 'Data Receipt Saved, Receipt Number : ' . $receiptnbr,
                'duplicate' => $duplicate
            ], 200);
        } catch (Exception $e) {
            
            Log::error('Error validating Receipt Recheck: ' . $e->getMessage());
            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage()
            ], 422);
        }
    }
    public function getDuplicateKeys(Request $req)
    {
        $receiptnbr = $req->receiptnbr;
        $status = $req->status;
        $nomorbuku = $req->nomorbuku;
        $creator = $req->approver;
        $duplicate = false;
        $keyDuplicate = [];

        try {
            $receiptMstr = ReceiptMaster::where('rm_rn_number', $receiptnbr)->first();
            $receiptDetail = ReceiptDetail::with('getPallet')
                ->where('rd_rm_id', $receiptMstr->id)
                ->where('rd_nomor_buku', $nomorbuku)
                ->first();
            $receiptDetailCheck = ReceiptDetail::with('getPallet') // fixed casing
                ->where('id', '!=', $receiptDetail->id)
                ->where('rd_site_penyimpanan', $receiptDetail->rd_site_penyimpanan)
                ->where('rd_location_penyimpanan', $receiptDetail->rd_location_penyimpanan)
                ->whereIn('rd_status', ['checked', 'draft'])
                ->get();

            // foreach ($receiptDetail->getPallet as $rp) {
            //     foreach ($receiptDetailCheck as $rdc) {
            //         foreach ($rdc->getPallet as $rp2) {
            //             if (
            //                 $rp->rdp_bin_penyimpanan == $rp2->rdp_bin_penyimpanan &&
            //                 $rp->rdp_level_penyimpanan == $rp2->rdp_level_penyimpanan &&
            //                 $rp->rdp_warehouse_penyimpanan == $rp2->rdp_warehouse_penyimpanan
            //             ) {
            //                 $duplicate = true;
            //                 break 3; // fixed: breaks all 3 loops
            //             }
            //         }
            //     }
            // }

            foreach ($receiptDetail->getPallet as $key => $rp) {
                $isDuplicate = false; // default to false

                foreach ($receiptDetailCheck as $rdc) {
                    foreach ($rdc->getPallet as $rp2) {
                        if (
                            $rp->rdp_bin_penyimpanan == $rp2->rdp_bin_penyimpanan &&
                            $rp->rdp_level_penyimpanan == $rp2->rdp_level_penyimpanan &&
                            $rp->rdp_warehouse_penyimpanan == $rp2->rdp_warehouse_penyimpanan
                        ) {
                            $keyDuplicate[] = $key; // store the key of the duplicate pallet
                            break 2; // no need to keep checking once a match is found
                        }
                    }
                }

               
            }
            Log::info('duplicate:' . var_export($isDuplicate, true)); // "true" or "false"
            return response()->json([
                'Status' => 'Success',
                'Message' => 'Data Receipt Saved, Receipt Number : ' . $receiptnbr,
                'keyDuplicate' => $keyDuplicate
            ], 200);
        } catch (Exception $e) {

            Log::error('Error validating Receipt Recheck: ' . $e->getMessage());
            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage()
            ], 422);
        }
    }
}
