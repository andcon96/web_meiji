<?php

namespace App\Http\Controllers;

use App\Models\API\ReceiptDetail;
use App\Models\API\TransactionHistory;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptBookController extends Controller
{
    public function getReceiptBook(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $data = ReceiptDetail::query()->with(['getMaster.getPurchaseOrderMaster', 'getPurchaseOrderDetail', 'getDokumen', 'getKemasan', 'getKendaraan', 'getPenanda', 'getPallet']);

        if ($request->search) {
            $data->where('rd_nomor_buku', $request->search)
                ->orWhereRelation('getMaster', 'rm_rn_number', $request->search);
        }

        $data = $data->get();

        return view('printBook.index', compact('data', 'menuMaster'));
    }

    public function printBook(Request $request, $id)
    {
        $dataReceipt = ReceiptDetail::with(['getPurchaseOrderDetail','getMaster.getPurchaseOrderMaster', 'getDokumen', 'getKemasan', 'getKendaraan', 'getPenanda', 'getPallet', 'getApprovalHist.getUserApprove', 'getApprovalHist.getUserApproveAlt'])->findOrFail($id);
        $receiptnumber = $dataReceipt->getMaster->rm_rn_number;
        $transactionHist = TransactionHistory::where('tr_program', 'PO Receipt Module')->where('tr_activity', 'Create Receipt')->where('tr_nbr', $receiptnumber)->first();
        $approverReceipt = ReceiptDetail::with(['getApprovalHist' => function ($query) {
            $query->where('arh_status', 'Approved')
                ->orderBy('updated_at', 'desc')
                ->take(2);
        }, 'getApprovalHist.getUserApprove'])
            ->where('id', $id)


            ->first(); // Add this to execute the query

        if ($transactionHist) {
            $approver[] = [['Approved' ?? ''], [$transactionHist->tr_user ?? ''], [$transactionHist->updated_at ?? '']];
        }




        foreach ($approverReceipt->getApprovalHist as $status) {

            $approver[] = [[$status->arh_status], [$status->getUserApprove->name], [$status->updated_at]];
        }


        $data = [
            'no_buku' => $dataReceipt->rd_nomor_buku,
            'tanggal' => $dataReceipt->rd_tanggal_datang,
            'nama_barang' => $dataReceipt->rd_nama_barang,
            'no_batch' => $dataReceipt->rd_batch,
            'expire_date' => $dataReceipt->rd_tgl_expire,
            'retest_date' => $dataReceipt->rd_tgl_retest,
            'kode_cetak' => $dataReceipt->rd_kode_cetak,
            'jumlah_terima' => $dataReceipt->rd_qty_terima,

            'is_po' => $dataReceipt->getDokumen->rdd_is_purchase_order,
            'is_sj' => $dataReceipt->getDokumen->rdd_is_surat_jalan,
            'is_packing_list' => $dataReceipt->getDokumen->rdd_is_packing_list,
            'is_msds' => $dataReceipt->getDokumen->rdd_is_msds,
            'is_coa' => $dataReceipt->getDokumen->rdd_is_coa,
            'no_surat_jalan' => $dataReceipt->getDokumen->rdd_surat_jalan,

            'note_namabarang' => $dataReceipt->rd_nama_barang_note,
            'note_batch' => $dataReceipt->rd_batch_note,
            'note_expdate' => $dataReceipt->rd_tgl_expire_note,
            'note_retestdate' => $dataReceipt->rd_tgl_retest_note,
            'note_kodecetak' => $dataReceipt->rd_kode_cetak_note,
            'note_jumlahterima' => $dataReceipt->rd_qty_terima_note_note,
            'jumlahterima_um' => $dataReceipt->getPurchaseOrderDetail->pod_um,
            'is_pabrikpembuat' => $dataReceipt->getKemasan->rdk_is_pabrik_pembuat,
            'is_alamatpembuat' => $dataReceipt->getKemasan->rdk_is_alamat_pembuat,
            'is_agenpemasok' => $dataReceipt->getKemasan->rdk_is_agen_pemasuk,

            'qty_jeniskemasanluar' => $dataReceipt->getKemasan->rdk_jenis_kemasan_luar,
            'qty_jeniskemasandalam' => $dataReceipt->getKemasan->rdk_jenis_kemasan_dalam,
            'qty_isiberatperkemasan' => $dataReceipt->getKemasan->rdk_isi_per_kemasan,
            'qty_isiberattotalkemasan' => $dataReceipt->getKemasan->rdk_isi_total_kemasan,

            'qty_jumlahkemasanluar' => $dataReceipt->getKemasan->rdk_jumlah_kemasan_luar,
            'qty_jumlahkemasandalam' => $dataReceipt->getKemasan->rdk_jumlah_kemasan_luar_baik,
            'qty_kondisikemasanluarbaik' => $dataReceipt->getKemasan->rdk_jumlah_kemasan_luar_tidak_baik,
            'qty_kondisikemasanluartidakbaik' => $dataReceipt->getKemasan->rdk_jumlah_kemasan_dalam,
            'qty_kondisikemasandalambaik' => $dataReceipt->getKemasan->rdk_jumlah_kemasan_dalam_baik,
            'qty_kondisikemasandalamtidakbaik' => $dataReceipt->getKemasan->rdk_jumlah_kemasan_dalam_tidak_baik,

            'nama_barang_penanda' => $dataReceipt->getPenanda->rdp_nama_barang,
            'no_batch_penanda' => $dataReceipt->getPenanda->rdp_nomor_lot,
            'expire_date_penanda' => $dataReceipt->getPenanda->rdp_expire_date,
            'mfg_date_penanda' => $dataReceipt->getPenanda->rdp_mfg_date,
            'suhu_penanda' => $dataReceipt->getPenanda->rdp_suhu,

            'kendaraan_is_bersih' => $dataReceipt->getKendaraan->rdken_is_bersih,
            'kendaraan_is_tidak_bersih' => $dataReceipt->getKendaraan->rdken_is_tidak_bersih,
            'kendaraan_is_serangga' => $dataReceipt->getKendaraan->rdken_is_ada_serangga,

            'rd_keterangan_tambahan' => $dataReceipt->rd_keterangan_tambahan,
            'approver' => $approver,
            
        ];

        $pdf = Pdf::loadView('printBook.print', $data)
            ->setPaper('A4', 'portrait');

        return $pdf->stream('checksheet_penerimaan_barang.pdf');
    }
}
