<?php

namespace App\Services;

use App\Models\API\ReceiptDetail;
use App\Models\API\ReceiptDokumen;
use App\Models\API\ReceiptKemasan;
use App\Models\API\ReceiptKendaraan;
use App\Models\API\ReceiptMaster;
use App\Models\API\ReceiptPenanda;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiptServices
{

    public function saveDataReceiptPerLot($data)
    {
        try {
            DB::beginTransaction();

            // Ambil Data PO Master
            $poMasterID = $data[0]->id_po_mstr ?? '';

            // Generate Running Number Receipt
            $getRunningNumber = (new RunningNumberServices())->getRunningNumberReceipt();

            // Create Receipt Master
            $newReceiptMaster = new ReceiptMaster();
            $newReceiptMaster->rm_po_mstr_id = $poMasterID;
            $newReceiptMaster->rm_rn_number = $getRunningNumber;
            $newReceiptMaster->rm_status = 'Draft';
            $newReceiptMaster->save();

            // Create Receipt Detail
            foreach ($data as $dataDetail) {
                // Generate Running Number Buku
                $getRunningNumberBuku = (new RunningNumberServices())->getRunningNumberReceipt();


                // Receipt Detail
                $newReceiptDetail = new ReceiptDetail();
                $newReceiptDetail->rd_rm_id = $newReceiptMaster->id;
                $newReceiptDetail->rd_pod_det_id = $dataDetail->id_pod_det;
                $newReceiptDetail->rd_nomor_buku = $getRunningNumberBuku;
                $newReceiptDetail->rd_tanggal_datang = $dataDetail->tgl_datang;
                $newReceiptDetail->rd_nama_barang = $dataDetail->nama_barang;
                $newReceiptDetail->rd_nama_barang_note = $dataDetail->nama_barang_note;
                $newReceiptDetail->rd_batch = $dataDetail->batch;
                $newReceiptDetail->rd_batch_note = $dataDetail->batch_note;
                $newReceiptDetail->rd_tgl_expire = $dataDetail->exp_date;
                $newReceiptDetail->rd_tgl_expire_note = $dataDetail->exp_date_note;
                $newReceiptDetail->rd_tgl_retest = $dataDetail->retest_date;
                $newReceiptDetail->rd_tgl_retest_note = $dataDetail->retest_date_note;
                $newReceiptDetail->rd_kode_cetak = $dataDetail->kode_cetak;
                $newReceiptDetail->rd_kode_cetak_note = $dataDetail->kode_cetak_note;
                $newReceiptDetail->rd_qty_terima = $dataDetail->jumlah_terima;
                $newReceiptDetail->rd_qty_terima_note = $dataDetail->jumlah_terima_note;
                $newReceiptDetail->rd_qty_potensi = $dataDetail->qty_potensi;
                $newReceiptDetail->rd_qty_pallete = $dataDetail->qty_pallete;
                $newReceiptDetail->rd_site_penyimpanan = $dataDetail->site_penyimpanan;
                $newReceiptDetail->rd_location_penyimpanan = $dataDetail->loc_penyimpanan;
                $newReceiptDetail->rd_level_penyimpanan = $dataDetail->level_penyimpanan;
                $newReceiptDetail->rd_bin_penyimpanan = $dataDetail->bin_penyimpanan;
                $newReceiptDetail->save();

                // Dokumen
                $newReceiptDetailDokumen = new ReceiptDokumen();
                $newReceiptDetailDokumen->rdd_rd_det_id = $newReceiptDetail->id;
                $newReceiptDetailDokumen->rdd_is_purchase_order = $dataDetail->is_po == true ? 1 : 0;
                $newReceiptDetailDokumen->rdd_is_msds = $dataDetail->is_msds == true ? 1 : 0;
                $newReceiptDetailDokumen->rdd_is_packing_list = $dataDetail->is_daftar_barang == true ? 1 : 0;
                $newReceiptDetailDokumen->rdd_is_coa = $dataDetail->is_coa == true ? 1 : 0;
                $newReceiptDetailDokumen->rdd_is_surat_jalan = $dataDetail->is_sj == true ? 1 : 0;
                $newReceiptDetailDokumen->rdd_surat_jalan = $dataDetail->nomor_sj;
                $newReceiptDetailDokumen->save();

                // Kemasan
                $newReceiptDetailKemasan = new ReceiptKemasan();
                $newReceiptDetailKemasan->rdk_rd_det_id = $newReceiptDetail->id;
                $newReceiptDetailKemasan->rdk_is_pabrik_pembuat = $dataDetail->is_pabrik_pembuat == true ? 1 : 0;
                $newReceiptDetailKemasan->rdk_is_alamat_pembuat = $dataDetail->is_alamat_pembuat == true ? 1 : 0;
                $newReceiptDetailKemasan->rdk_is_agen_pemasuk = $dataDetail->is_agen_pembuat == true ? 1 : 0;
                $newReceiptDetailKemasan->rdk_jenis_kemasan_luar = $dataDetail->jenis_kemasan_luar;
                $newReceiptDetailKemasan->rdk_jenis_kemasan_dalam = $dataDetail->jenis_kemasan_dalam;
                $newReceiptDetailKemasan->rdk_isi_per_kemasan = $dataDetail->berat_per_kemasan;
                $newReceiptDetailKemasan->rdk_isi_total_kemasan = $dataDetail->berat_total_kemasan;
                $newReceiptDetailKemasan->rdk_jumlah_kemasan_luar = $dataDetail->qty_kemasan_luar;
                $newReceiptDetailKemasan->rdk_jumlah_kemasan_luar_baik = $dataDetail->qty_kemasan_luar_baik;
                $newReceiptDetailKemasan->rdk_jumlah_kemasan_luar_tidak_baik = $dataDetail->qty_kemasan_luar_tidak_baik;
                $newReceiptDetailKemasan->rdk_jumlah_kemasan_dalam = $dataDetail->qty_kemasan_dalam;
                $newReceiptDetailKemasan->rdk_jumlah_kemasan_dalam_baik = $dataDetail->qty_kemasan_dalam_baik;
                $newReceiptDetailKemasan->rdk_jumlah_kemasan_dalam_tidak_baik = $dataDetail->qty_kemasan_dalam_tidak_baik;
                $newReceiptDetailKemasan->save();

                // Kendaraan
                $newReceiptDetailKendaraan = new ReceiptKendaraan();
                $newReceiptDetailKendaraan->rdken_rd_det_id = $newReceiptDetail->id;
                $newReceiptDetailKendaraan->rdken_is_bersih = $dataDetail->is_bersih == true ? 1 : 0;
                $newReceiptDetailKendaraan->rdken_is_tidak_bersih = $dataDetail->is_tidak_bersih == true ? 1 : 0;
                $newReceiptDetailKendaraan->rdken_is_ada_serangga = $dataDetail->is_ada_serangga == true ? 1 : 0;
                $newReceiptDetailKendaraan->rdken_keterangan = $dataDetail->keterangan_penangkut;
                $newReceiptDetailKendaraan->save();

                // Penanda
                $newReceiptDetailPenanda = new ReceiptPenanda();
                $newReceiptDetailPenanda->rdp_rd_det_id = $newReceiptDetail->id;
                $newReceiptDetailPenanda->rdp_nama_barang = $dataDetail->nama_barang_penanda;
                $newReceiptDetailPenanda->rdp_nomor_lot = $dataDetail->batch_penanda;
                $newReceiptDetailPenanda->rdp_expire_date = $dataDetail->exp_date_penanda;
                $newReceiptDetailPenanda->rdp_mfg_date = $dataDetail->mfg_date_penanda;
                $newReceiptDetailPenanda->save();
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            Log::info($e);
            DB::rollBack();

            return false;
        }
    }
}
