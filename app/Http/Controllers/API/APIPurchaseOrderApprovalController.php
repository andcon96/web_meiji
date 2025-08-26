<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ApprovalReceiptHistory;
use App\Models\API\ApprovalReceiptTemp;
use App\Models\API\PurchaseOrderDetail;
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
            'getUserApprove:id,username,name',
            'getUserApproveAlt:id,username,name',
            'getReceiptDetail.getMaster.getPurchaseOrderMaster',
            'getReceiptDetail.getDokumen',
            'getReceiptDetail.getKemasan',
            'getReceiptDetail.getKendaraan',
            'getReceiptDetail.getPenanda',
            'getReceiptDetail.getPurchaseOrderDetail'
        ]);

        if ($req->search) {
            $data->whereRelation('getReceiptDetail', 'rd_nomor_buku', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getReceiptDetail.getMaster', 'rm_rn_number', 'LIKE', '%' . $req->search . '%')
                ->orWhereRelation('getReceiptDetail.getMaster.getPurchaseOrderMaster', 'po_nbr', 'LIKE', '%' . $req->search . '%')
            ;
        }

        $data = $data
            ->where('art_status', '=', 'Waiting')
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
                        $newHistoryApproval->arh_status = $datas->art_status;
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


                    break;

                case 'Approve':
                    // Update Status Current
                    $tempApprove = ApprovalReceiptTemp::find($req->idApproval);
                    $tempApprove->art_status = 'Approved';
                    $tempApprove->art_approved_by = Auth::user()->id;
                    $tempApprove->save();

                    // Get Sisa Approval Temp yg blm approve
                    $sisaApproval = ApprovalReceiptTemp::where('art_receipt_det_id', $tempApprove->art_receipt_det_id)->where('art_status', 'Waiting')->count();
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
                            $newHistoryApproval->arh_status = $datas->art_status;
                            $newHistoryApproval->arh_reason = $datas->art_reason;
                            $newHistoryApproval->created_at = $datas->created_at;
                            $newHistoryApproval->updated_at = $datas->updated_at;
                            $newHistoryApproval->save();
                        }

                        // Apus Smua Approval Temp
                        ApprovalReceiptTemp::where('art_receipt_det_id', $tempApprove->art_receipt_det_id)->delete();

                        // Ubah Status Receipt Det -> Approved
                        $detailReceipt = ReceiptDetail::find($tempApprove->art_receipt_det_id);
                        $detailReceipt->rd_status = 'Approved';
                        $detailReceipt->save();

                        // Ambil Qty Ongoing jadi Qty Receipt
                        $dataReceipt = ReceiptDetail::find($tempApprove->art_receipt_det_id);
                        $totalReceipt = $dataReceipt->rd_qty_terima * $dataReceipt->rd_qty_potensi;

                        // Qxtend Po Receipt
                        $dataPurchaseOrderDetail = PurchaseOrderDetail::with('getMaster')->find($dataReceipt->rd_pod_det_id);
                        $poNbr = $dataPurchaseOrderDetail->getMaster->po_nbr ?? '';
                        $line = $dataPurchaseOrderDetail->pod_line ?? '';
                        $lotserialQty = $totalReceipt;
                        $receiptUm = $dataPurchaseOrderDetail->pod_pt_um ?? '';
                        $site = $dataReceipt->rd_site_penyimpanan ?? '';
                        $location = $dataReceipt->rd_location_penyimpanan ?? '';
                        $lotserial = $dataReceipt->rd_batch ?? '';

                        $submitReceiptQxtend = (new QxtendServices())->qxPurchaseOrderReceipt($poNbr, $line, $lotserialQty, $receiptUm, $site, $location, $lotserial);
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

                            // Update Data xxinv_det pake WSA
                            $updateDataQAD = (new WSAServices())->wsaUpdateStockTableCustom(
                                $dataPurchaseOrderDetail->pod_part,
                                $location,
                                $lotserial,
                                $dataReceipt->rd_bin_penyimpanan,
                                $dataReceipt->rd_level_penyimpanan,
                                $site,
                                $dataReceipt->rd_building_penyimpanan,
                                $lotserialQty,
                                $dataReceipt->rd_tanggal_datang,
                                $dataReceipt->rd_tgl_expire
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
                    break;
            }

            DB::commit();
            return response()->json([
                'Status' => 'Success',
                'Message' => 'Data Succesfully Approved / Reject',
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed To Approve / Reject Data"
            ], 422);
        }
    }
}
