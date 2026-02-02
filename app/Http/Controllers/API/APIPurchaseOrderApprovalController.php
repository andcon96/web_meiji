<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ApprovalReceiptHistory;
use App\Models\API\ApprovalReceiptTemp;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\TransactionHistory;
use App\Models\API\ReceiptDetail;
use App\Models\Settings\ApprovalReceipt;
use App\Services\QxtendServices;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APIPurchaseOrderApprovalController extends Controller
{
    public function index(Request $req)
    {
        $data = ApprovalReceiptTemp::with([
            'getChildren.getUserApprove:id,username,name',
            'getChildren.getHistory.getUserApprove:id,username,name',
            'getUserApprove:id,username,name',
            'getUserApproveAlt:id,username,name',
            'getReceiptDetail.getMaster.getPurchaseOrderMaster',
            'getReceiptDetail.getDokumen',
            'getReceiptDetail.getKemasan',
            'getReceiptDetail.getKendaraan',
            'getReceiptDetail.getPenanda',
            'getReceiptDetail.getPurchaseOrderDetail',
            'getReceiptDetail.getPallet',
            'getReceiptDetail.getAttachment',
        ]);

        if ($req->search) {
            $data->whereRelation('getReceiptDetail', 'rd_nomor_buku', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getReceiptDetail.getMaster', 'rm_rn_number', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getReceiptDetail.getMaster.getPurchaseOrderMaster', 'po_nbr', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getReceiptDetail.getPurchaseOrderDetail', 'pod_part', 'LIKE', '%' . $req->search . '%')
            ;
        }

        $data = $data
            ->where('art_status', '=', 'Checked')
            ->where(function ($query) {
                $query->where('art_user_approve', '=', Auth::user()->id)
                    ->orWhere('art_user_approve_alt', '=', Auth::user()->id);
            })
            ->where(function ($query) {
                $query->where('art_sequence', 1) // Always show first sequence
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('approval_receipt_temp as prev')
                            ->whereRaw('prev.art_receipt_det_id = approval_receipt_temp.art_receipt_det_id')
                            ->whereRaw('prev.art_sequence = approval_receipt_temp.art_sequence - 1')
                            ->where('prev.art_status', 'approved');
                    });
            })
            ->orderBy('id', 'desc')
            ->paginate(10);


        return GeneralResources::collection($data);
    }

    public function approveRejectReceipt(Request $req)
    {
        // Log::channel('customlog')->info('Data : ', ['input' => $req->all()]);
        try {
            DB::beginTransaction();
            $approver = Auth::user()->name;

            switch ($req->action) {

                case 'Reject':
                    // Update Status Current
                    $tempApprove = ApprovalReceiptTemp::find($req->idApproval);
                    $tempApprove->art_status = 'Reject';
                    $tempApprove->art_reason = $req->reason;
                    $tempApprove->art_approved_by = Auth::user()->id;
                    $tempApprove->save();

                    // Ambil semua approval receipt det yang bukan waiting & pindain ke hist.
                    $getAllApproval = ApprovalReceiptTemp::where('art_receipt_det_id', $tempApprove->art_receipt_det_id)->where('art_status', '!=', 'Waiting')->get();
                    foreach ($getAllApproval as $datas) {
                        $newHistoryApproval = new ApprovalReceiptHistory();
                        $newHistoryApproval->arh_receipt_det_id = $datas->art_receipt_det_id;
                        $newHistoryApproval->arh_user_approve = $datas->art_user_approve;
                        $newHistoryApproval->arh_user_approve_alt = $datas->art_user_approve_alt;
                        $newHistoryApproval->arh_sequence = $datas->art_sequence;
                        $newHistoryApproval->arh_approved_by = $datas->art_approved_by;
                        // $newHistoryApproval->arh_status = $datas->art_status;
                        $newHistoryApproval->arh_status = 'Reject';
                        $newHistoryApproval->arh_reason = $datas->art_reason;
                        $newHistoryApproval->created_at = $datas->created_at;
                        $newHistoryApproval->updated_at = $datas->updated_at;
                        $newHistoryApproval->save();
                    }

                    // Apus smua approval temp
                    ApprovalReceiptTemp::where('art_receipt_det_id', $tempApprove->art_receipt_det_id)->delete();

                    // Ubah Status Receipt Det -> Draft
                    $detailReceipt = ReceiptDetail::find($tempApprove->art_receipt_det_id);
                    $detailReceipt->rd_status = 'Draft';
                    $detailReceipt->save();

                    //getDetail Receipt
                    $data = ReceiptDetail::with(['getMaster', 'getPurchaseOrderDetail.getMaster'])->find($tempApprove->art_receipt_det_id);
                    $getMaster = $data->getMaster;
                    $getPurchaseOrderDetail = $data->getPurchaseOrderDetail;
                    // Transaction History
                    $newTransactionHistory = new TransactionHistory();
                    $newTransactionHistory->tr_nbr = $getMaster->rm_rn_number;
                    $newTransactionHistory->tr_order = $getPurchaseOrderDetail->getMaster->po_nbr;
                    $newTransactionHistory->tr_program = 'PO Approval Module';
                    $newTransactionHistory->tr_activity = 'Reject Receipt';
                    $newTransactionHistory->tr_user = $approver ?? '';
                    // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                    $newTransactionHistory->tr_part = $getPurchaseOrderDetail->pod_part ?? '';
                    $newTransactionHistory->tr_uom = $data->satuan ?? '';
                    $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                    $newTransactionHistory->tr_lot = $data->rd_batch ?? '';
                    $newTransactionHistory->tr_qty = $data->jumlah_terima ?? '';
                    $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                    $newTransactionHistory->tr_reference = $data->kode_cetak ?? '';
                    $newTransactionHistory->tr_site = $data->site_penyimpanan ?? '';
                    $newTransactionHistory->tr_location = $data->loc_penyimpanan ?? '';
                    $newTransactionHistory->tr_warehouse = $data->building_penyimpanan ?? '';
                    $newTransactionHistory->tr_level = $data->level_penyimpanan ?? '';
                    $newTransactionHistory->tr_bin = $data->bin_penyimpanan ?? '';
                    $newTransactionHistory->tr_remark = '';
                    $newTransactionHistory->save();


                    break;

                case 'Approve':
                    // Update Status Current
                    $tempApprove = ApprovalReceiptTemp::find($req->idApproval);
                    $tempApprove->art_status = 'Approved';
                    $tempApprove->art_approved_by = Auth::user()->id;
                    $tempApprove->save();

                    // Get Sisa Approval Temp yg blm approve
                    $sisaApproval = ApprovalReceiptTemp::where('art_receipt_det_id', $tempApprove->art_receipt_det_id)->where('art_status', 'Checked')->count();
                    if ($sisaApproval == 0) {
                        // Pindain ke Approval Receipt Hist.
                        $getAllApproval = ApprovalReceiptTemp::where('art_receipt_det_id', $tempApprove->art_receipt_det_id)->get();
                        foreach ($getAllApproval as $datas) {
                            $newHistoryApproval = new ApprovalReceiptHistory();
                            $newHistoryApproval->arh_receipt_det_id = $datas->art_receipt_det_id;
                            $newHistoryApproval->arh_user_approve = $datas->art_user_approve;
                            $newHistoryApproval->arh_user_approve_alt = $datas->art_user_approve_alt;
                            $newHistoryApproval->arh_sequence = $datas->art_sequence;
                            $newHistoryApproval->arh_approved_by = $datas->art_approved_by;
                            // $newHistoryApproval->arh_status = $datas->art_status;
                            $newHistoryApproval->arh_status = 'Approved';
                            $newHistoryApproval->arh_reason = $datas->art_reason;
                            $newHistoryApproval->created_at = $datas->created_at;
                            $newHistoryApproval->updated_at = $datas->updated_at;
                            $newHistoryApproval->save();
                        }

                        // Apus Smua Approval Temp
                        ApprovalReceiptTemp::where('art_receipt_det_id', $tempApprove->art_receipt_det_id)->delete();

                        // Ubah Status Receipt Det -> Approved
                        $detailReceipt = ReceiptDetail::with(['getDokumen', 'getKemasan'])->find($tempApprove->art_receipt_det_id);
                        $detailReceipt->rd_status = 'Approved';
                        $detailReceipt->save();

                        // Ambil Qty Ongoing jadi Qty Receipt
                        $dataReceipt = ReceiptDetail::find($tempApprove->art_receipt_det_id);
                        $totalReceipt = $dataReceipt->rd_qty_terima * $dataReceipt->rd_qty_potensi;

                        // Qxtend Po Receipt
                        $dataPurchaseOrderDetail = PurchaseOrderDetail::with('getMaster')->find($dataReceipt->rd_pod_det_id);
                        $poNbr = $dataPurchaseOrderDetail->getMaster->po_nbr ?? '';
                        $line = $dataPurchaseOrderDetail->pod_line ?? '';
                        // $lotserialQty = $totalReceipt; Dirubah Receipt sesuai UM PO
                        $lotserialQty = $dataReceipt->rd_qty_terima;
                        $receiptUm = $dataPurchaseOrderDetail->pod_um ?? '';
                        $site = $dataReceipt->rd_site_penyimpanan ?? '';
                        $location = $dataReceipt->rd_location_penyimpanan ?? '';
                        $lotserial = $dataReceipt->rd_batch ?? '';
                        $qtyPotensi = $dataReceipt->rd_qty_potensi ?? 1;
                        $ref = $dataReceipt->rd_ref ?? '';
                        $expireddate = date('Y-m-d', strtotime($dataReceipt->rd_tgl_expire));
                        $effdate = date('Y-m-d', strtotime($dataReceipt->rd_tanggal_datang));
                        // Assign pod_um_conv sebelum receipt -> request bang dany
                        $changeUmConv = (new WSAServices())->wsaChangeUmConv($poNbr, $line, $qtyPotensi);
                        $suratjalan = $dataReceipt->getDokumen->rdd_surat_jalan ?? '';
                        $jumlahkemasanluar = $dataReceipt->getKemasan->rdk_jumlah_kemasan_luar ?? 0;
                        if ($changeUmConv == false) {
                            DB::rollback();
                            return response()->json([
                                'Status' => 'Error',
                                'Message' => "Failed to Update UM Conv Purchase Order"
                            ], 422);
                        }



                        $submitReceiptQxtend = (new QxtendServices())->qxPurchaseOrderReceipt($poNbr, $line, $lotserialQty, $receiptUm, $site, $location, $lotserial, $expireddate, $ref, $suratjalan, $jumlahkemasanluar, $effdate);
                        if ($submitReceiptQxtend == false) {
                            DB::rollback();
                            return response()->json([
                                'Status' => 'Error',
                                'Message' => "Qxtend Error Connection"
                            ], 422);
                        }
                        if ($submitReceiptQxtend[0] == false) {
                            DB::rollback();
                            return response()->json([
                                'Status' => 'Error',
                                'Message' => 'Qxtend Error : ' . $submitReceiptQxtend[1]
                            ], 422);
                        } else {
                            // Update Data PO di web
                            (new WSAServices())->wsaPurchaseOrder($poNbr);


                            $dataReceiptPallet = ReceiptDetail::with('getPallet')->find($tempApprove->art_receipt_det_id);
                            foreach ($dataReceiptPallet->getPallet as $dataPallet) {

                                // Update Data xxinv_det pake WSA
                                $updateDataQAD = (new WSAServices())->wsaUpdateStockTableCustom(
                                    $dataPurchaseOrderDetail->pod_part,
                                    $location,
                                    $lotserial,
                                    $dataPallet->rdp_bin_penyimpanan,
                                    $dataPallet->rdp_level_penyimpanan,
                                    $site,
                                    $dataReceipt->rd_building_penyimpanan,
                                    $dataPallet->rdp_qty_penyimpanan,
                                    $dataReceipt->rd_tanggal_datang,
                                    $dataReceipt->rd_tgl_expire,
                                    $dataReceipt->rd_qty_potensi,
                                    $dataReceipt->rd_ref,
                                );

                                if ($updateDataQAD == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'Status' => 'Error',
                                        'Message' => "Gagal update data stock WSA"
                                    ], 422);
                                }
                            }
                        }
                    }
                    //getDetail Receipt
                    $data = ReceiptDetail::with(['getMaster', 'getPurchaseOrderDetail.getMaster'])->find($tempApprove->art_receipt_det_id);
                    $getMaster = $data->getMaster;
                    $getPurchaseOrderDetail = $data->getPurchaseOrderDetail;
                    // Transaction History
                    $newTransactionHistory = new TransactionHistory();
                    $newTransactionHistory->tr_nbr = $getMaster->rm_rn_number;
                    $newTransactionHistory->tr_order = $getPurchaseOrderDetail->getMaster->po_nbr;
                    $newTransactionHistory->tr_program = 'PO Approval Module';
                    $newTransactionHistory->tr_activity = 'Approve Receipt';
                    $newTransactionHistory->tr_user = $approver ?? '';
                    // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
                    $newTransactionHistory->tr_part = $getPurchaseOrderDetail->pod_part ?? '';
                    $newTransactionHistory->tr_uom = $data->satuan ?? '';
                    $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
                    $newTransactionHistory->tr_lot = $data->rd_batch ?? '';
                    $newTransactionHistory->tr_qty = $data->jumlah_terima ?? '';
                    $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                    $newTransactionHistory->tr_reference = $data->kode_cetak ?? '';
                    $newTransactionHistory->tr_site = $data->site_penyimpanan ?? '';
                    $newTransactionHistory->tr_location = $data->loc_penyimpanan ?? '';
                    $newTransactionHistory->tr_warehouse = $data->building_penyimpanan ?? '';
                    $newTransactionHistory->tr_level = $data->level_penyimpanan ?? '';
                    $newTransactionHistory->tr_bin = $data->bin_penyimpanan ?? '';
                    $newTransactionHistory->tr_remark = '';
                    $newTransactionHistory->save();
                    break;
            }

            DB::commit();
            return response()->json([
                'Status' => 'Success',
                'Message' => 'Data Succesfully Approved / Reject',
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Approve / Reject Data"
            ], 422);
        }
    }
}
