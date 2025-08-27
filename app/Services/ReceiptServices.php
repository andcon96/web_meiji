<?php

namespace App\Services;

use App\Models\API\ApprovalReceiptTemp;
use App\Models\API\PurchaseOrderDetail;
use App\Models\API\ReceiptAttachment;
use App\Models\API\ReceiptDetail;
use App\Models\API\ReceiptDokumen;
use App\Models\API\ReceiptKemasan;
use App\Models\API\ReceiptKendaraan;
use App\Models\API\ReceiptMaster;
use App\Models\API\ReceiptPenanda;
use App\Models\Settings\ApprovalReceipt;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiptServices
{

    public function saveDataReceiptPerLot($data, $arrayKoneksiImage)
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
            $newReceiptMaster->rm_status = 'Waiting For Approval';
            $newReceiptMaster->save();

            // Create Receipt Detail
            foreach ($data as $dataDetail) {
                // Generate Running Number Buku
                $getRunningNumberBuku = (new RunningNumberServices())->getRunningNumberBuku();


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
                $newReceiptDetail->rd_building_penyimpanan = $dataDetail->building_penyimpanan;
                $newReceiptDetail->rd_status = 'Waiting'; // Langsung Approval
                $newReceiptDetail->save();

                // Create Approval
                $currentApprover = ApprovalReceipt::get();
                if ($currentApprover) {
                    foreach ($currentApprover as $dataApprover) {
                        $approvalReceiptTemp = new ApprovalReceiptTemp();
                        $approvalReceiptTemp->art_receipt_det_id = $newReceiptDetail->id;
                        $approvalReceiptTemp->art_user_approve = $dataApprover->ar_user_approve;
                        $approvalReceiptTemp->art_user_approve_alt = $dataApprover->ar_user_approve_alt;
                        $approvalReceiptTemp->art_sequence = $dataApprover->ar_sequence;
                        $approvalReceiptTemp->art_status = 'Waiting';
                        $approvalReceiptTemp->save();
                    }
                } else {
                    $newReceiptDetail->rd_status = 'Approved'; // Kalo ga ada data approval langsung Approved
                    $newReceiptDetail->save();
                }


                // Update Qty Ongoing PO Detail
                // $dataPodDetail = PurchaseOrderDetail::find($dataDetail->id_pod_det);
                // $dataPodDetail->pod_qty_ongoing = $dataPodDetail->pod_qty_ongoing + $dataDetail->total;
                // $dataPodDetail->save();

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
                $newReceiptDetailPenanda->rdp_suhu = $dataDetail->suhu_penanda;
                $newReceiptDetailPenanda->save();

                // Attachment
                $targetSubTab = $dataDetail->index_tab_lot;
                $targetPodTab = $dataDetail->index_tab_pod;

                $filtered = array_filter($arrayKoneksiImage, function ($item) use ($targetSubTab, $targetPodTab) {
                    return $item['idSubTab'] == $targetSubTab && $item['idPodTab'] == $targetPodTab;
                });

                foreach ($filtered as $datas) {
                    $from = public_path('upload/receipttemp/' . $datas['fileName']);
                    $to = public_path('upload/receipt/' . $datas['fileName']);        // target path
                    $targetPath = 'upload/receipt/' . $datas['fileName'];

                    rename($from, $to);

                    $newReceiptAttachment = new ReceiptAttachment();
                    $newReceiptAttachment->rda_rd_det_id = $newReceiptDetail->id;
                    $newReceiptAttachment->rda_filepath = $targetPath;
                    $newReceiptAttachment->save();
                }
            }



            DB::commit();
            return [true, $getRunningNumber];
        } catch (Exception $e) {
            Log::info($e);
            DB::rollBack();

            return [false, ''];
        }
    }

    public function editDataReceipt($data)
    {
        try {
            DB::beginTransaction();

            // Receipt Detail
            $findReceiptDetail = ReceiptDetail::findOrFail($data->id);
            $findReceiptDetail->rd_tanggal_datang = $data->rd_tanggal_datang;
            $findReceiptDetail->rd_nama_barang = $data->rd_nama_barang;
            $findReceiptDetail->rd_nama_barang_note = $data->rd_nama_barang_note;
            $findReceiptDetail->rd_batch = $data->rd_batch;
            $findReceiptDetail->rd_batch_note = $data->rd_batch_note;
            $findReceiptDetail->rd_tgl_expire = $data->rd_tgl_expire;
            $findReceiptDetail->rd_tgl_expire_note = $data->rd_tgl_expire_note;
            $findReceiptDetail->rd_tgl_retest = $data->rd_tgl_retest;
            $findReceiptDetail->rd_tgl_retest_note = $data->rd_tgl_retest_note;
            $findReceiptDetail->rd_kode_cetak = $data->rd_kode_cetak;
            $findReceiptDetail->rd_kode_cetak_note = $data->rd_kode_cetak_note;
            $findReceiptDetail->rd_qty_terima = $data->rd_qty_terima;
            $findReceiptDetail->rd_qty_terima_note = $data->rd_qty_terima_note;
            $findReceiptDetail->rd_qty_potensi = $data->rd_qty_potensi;
            $findReceiptDetail->rd_qty_pallete = $data->rd_qty_pallete;
            $findReceiptDetail->rd_site_penyimpanan = $data->rd_site_penyimpanan;
            $findReceiptDetail->rd_location_penyimpanan = $data->rd_location_penyimpanan;
            $findReceiptDetail->rd_level_penyimpanan = $data->rd_level_penyimpanan;
            $findReceiptDetail->rd_bin_penyimpanan = $data->rd_bin_penyimpanan;
            $findReceiptDetail->rd_building_penyimpanan = $data->rd_building_penyimpanan;
            $findReceiptDetail->rd_status = 'Waiting';
            $findReceiptDetail->save();

            // Dokumen
            $newReceiptDetailDokumen = ReceiptDokumen::findOrFail($data->get_dokumen->id);
            $newReceiptDetailDokumen->rdd_is_purchase_order = $data->get_dokumen->rdd_is_purchase_order;
            $newReceiptDetailDokumen->rdd_is_msds = $data->get_dokumen->rdd_is_msds;
            $newReceiptDetailDokumen->rdd_is_packing_list = $data->get_dokumen->rdd_is_packing_list;
            $newReceiptDetailDokumen->rdd_is_coa = $data->get_dokumen->rdd_is_coa;
            $newReceiptDetailDokumen->rdd_is_surat_jalan = $data->get_dokumen->rdd_is_surat_jalan;
            $newReceiptDetailDokumen->rdd_surat_jalan = $data->get_dokumen->rdd_surat_jalan;
            $newReceiptDetailDokumen->save();

            // Kemasan
            $newReceiptDetailKemasan = ReceiptKemasan::findOrFail($data->get_kemasan->id);
            $newReceiptDetailKemasan->rdk_is_pabrik_pembuat = $data->get_kemasan->rdk_is_pabrik_pembuat;
            $newReceiptDetailKemasan->rdk_is_alamat_pembuat = $data->get_kemasan->rdk_is_alamat_pembuat;
            $newReceiptDetailKemasan->rdk_is_agen_pemasuk = $data->get_kemasan->rdk_is_agen_pemasuk;
            $newReceiptDetailKemasan->rdk_jenis_kemasan_luar = $data->get_kemasan->rdk_jenis_kemasan_luar;
            $newReceiptDetailKemasan->rdk_jenis_kemasan_dalam = $data->get_kemasan->rdk_jenis_kemasan_dalam;
            $newReceiptDetailKemasan->rdk_isi_per_kemasan = $data->get_kemasan->rdk_isi_per_kemasan;
            $newReceiptDetailKemasan->rdk_isi_total_kemasan = $data->get_kemasan->rdk_isi_total_kemasan;
            $newReceiptDetailKemasan->rdk_jumlah_kemasan_luar = $data->get_kemasan->rdk_jumlah_kemasan_luar;
            $newReceiptDetailKemasan->rdk_jumlah_kemasan_luar_baik = $data->get_kemasan->rdk_jumlah_kemasan_luar_baik;
            $newReceiptDetailKemasan->rdk_jumlah_kemasan_luar_tidak_baik = $data->get_kemasan->rdk_jumlah_kemasan_luar_tidak_baik;
            $newReceiptDetailKemasan->rdk_jumlah_kemasan_dalam = $data->get_kemasan->rdk_jumlah_kemasan_dalam;
            $newReceiptDetailKemasan->rdk_jumlah_kemasan_dalam_baik = $data->get_kemasan->rdk_jumlah_kemasan_dalam_baik;
            $newReceiptDetailKemasan->rdk_jumlah_kemasan_dalam_tidak_baik = $data->get_kemasan->rdk_jumlah_kemasan_dalam_tidak_baik;
            $newReceiptDetailKemasan->save();

            // Kendaraan
            $newReceiptDetailKendaraan = ReceiptKendaraan::findOrFail($data->get_kendaraan->id);
            $newReceiptDetailKendaraan->rdken_is_bersih = $data->get_kendaraan->rdken_is_bersih;
            $newReceiptDetailKendaraan->rdken_is_tidak_bersih = $data->get_kendaraan->rdken_is_tidak_bersih;
            $newReceiptDetailKendaraan->rdken_is_ada_serangga = $data->get_kendaraan->rdken_is_ada_serangga;
            $newReceiptDetailKendaraan->rdken_keterangan = $data->get_kendaraan->rdken_keterangan;
            $newReceiptDetailKendaraan->save();

            // Penanda
            $newReceiptDetailPenanda = ReceiptPenanda::findOrFail($data->get_penanda->id);
            $newReceiptDetailPenanda->rdp_nama_barang = $data->get_penanda->rdp_nama_barang;
            $newReceiptDetailPenanda->rdp_nomor_lot = $data->get_penanda->rdp_nomor_lot;
            $newReceiptDetailPenanda->rdp_expire_date = $data->get_penanda->rdp_expire_date;
            $newReceiptDetailPenanda->rdp_mfg_date = $data->get_penanda->rdp_mfg_date;
            $newReceiptDetailPenanda->rdp_suhu = $data->get_penanda->rdp_suhu;
            $newReceiptDetailPenanda->save();

            // Create Approval
            $currentApprover = ApprovalReceipt::get();
            if ($currentApprover) {
                foreach ($currentApprover as $dataApprover) {
                    $approvalReceiptTemp = new ApprovalReceiptTemp();
                    $approvalReceiptTemp->art_receipt_det_id = $findReceiptDetail->id;
                    $approvalReceiptTemp->art_user_approve = $dataApprover->ar_user_approve;
                    $approvalReceiptTemp->art_user_approve_alt = $dataApprover->ar_user_approve_alt;
                    $approvalReceiptTemp->art_sequence = $dataApprover->ar_sequence;
                    $approvalReceiptTemp->art_status = 'Waiting';
                    $approvalReceiptTemp->save();
                }
            } else {
                $findReceiptDetail->rd_status = 'Approved'; // Kalo ga ada data approval langsung Approved
                $findReceiptDetail->save();
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
