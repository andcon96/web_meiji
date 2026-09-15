<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PenyerahanBarang;
use App\Models\API\PenyerahanBarangPallet;
use App\Models\API\SingleTransfer;
use App\Models\API\TransactionHistory;
use App\Models\API\xxinvDet;
use App\Models\Settings\Domain;
use App\Models\Settings\Item;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use App\Models\Settings\PenyerahanBarangPrefix;
use App\Services\QxtendServices;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APIBarangJadi extends Controller
{
    public function getTransferBarangJadi(Request $req)
    {
        $trfid = $req->trfid;
        $trfdata = singleTransfer::where('st_trfid', $trfid)->first();
        if (! $trfdata) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {
            return GeneralResources::collection($trfdata);

        }
    }

    public function getPenerimaanBarangData(Request $req)
    {
        $search = $req->search;

        $trfdata = penyerahanBarang::where('pb_status', 'Open');

        if ($search) {
            $trfdata->where(function ($query) use ($search) {
                $query->where('pb_trfid', 'LIKE', '%'.$search.'%')
                    ->orWhere('pb_item', 'LIKE', '%'.$search.'%')
                    ->orWhere('pb_lot', 'LIKE', '%'.$search.'%');
            });
        }

        $trfdata = $trfdata->get();

        if ($trfdata->isEmpty()) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        }

        return GeneralResources::collection($trfdata);
    }

    public function receiptItempb(Request $req)
    {
        $log = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/penyerahanBarangJadi-2026-09-07.log'),
            'level' => 'debug',
        ]);

        $log->info('=== START RECEIPT ITEM PB ===', [
            'trfid' => $req->trfid,
            'locto' => $req->locto,
            'wh' => $req->wh,
            'pallets' => $req->pallets,
        ]);

        try {
            $trfid = $req->trfid;
            $locto = $req->locto;
            $whto = $req->wh;

            $log->info('Request data', [
                'trfid' => $trfid,
                'locto' => $locto,
                'whto' => $whto,
            ]);

            $palletsRaw = $req->pallets ?? '[]';
            $pallets = json_decode($palletsRaw, true);

            $log->info('Pallet data decoded', [
                'pallets' => $pallets,
                'total_pallet' => is_array($pallets) ? count($pallets) : 0,
            ]);

            if (! is_array($pallets) || count($pallets) === 0) {
                $log->warning('Receipt gagal: data pallet kosong');

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Data pallet (Level, Bin, Qty) wajib diisi',
                ], 422);
            }

            if (! $whto) {
                $log->warning('Receipt gagal: Warehouse kosong', [
                    'trfid' => $trfid,
                ]);

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Warehouse wajib diisi',
                ], 422);
            }

            $data = penyerahanBarang::where('pb_trfid', $trfid)->first();

            if (! $data) {
                $log->warning('Receipt gagal: Transfer ID tidak ditemukan', [
                    'trfid' => $trfid,
                ]);

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Transfer ID tidak ditemukan',
                ], 404);
            }

            $log->info('Data penyerahan ditemukan', [
                'id' => $data->id,
                'trfid' => $data->pb_trfid,
                'part' => $data->pb_item,
                'qty' => $data->pb_qty,
                'site_from' => $data->pb_site_from,
                'site_to' => $data->pb_site_to,
                'loc_from' => $data->pb_loc_from,
                'lot' => $data->pb_lot,
                'wh_from' => $data->pb_wh_from,
                'level_from' => $data->pb_level_from,
                'bin_from' => $data->pb_bin_from,
            ]);

            $remark = $data->pb_remark;
            $part = $data->pb_item;
            $qtyoh = $data->pb_qty;
            $sitefrom = $data->pb_site_from;
            $siteto = $data->pb_site_to;
            $locfrom = $data->pb_loc_from;
            $lotfrom = $data->pb_lot;
            $lotto = $data->pb_lot;
            $buildingfrom = $data->pb_wh_from ?? '';
            $buildingto = $whto;
            $levelfrom = $data->pb_level_from ?? '';
            $binfrom = $data->pb_bin_from ?? '';

            $totalPalletQty = 0;

            foreach ($pallets as $palletRow) {
                $qty = (float) str_replace(',', '', $palletRow['qty'] ?? 0);

                $totalPalletQty += $qty;

                $log->debug('Pallet qty calculation', [
                    'level' => $palletRow['level'] ?? '',
                    'bin' => $palletRow['bin'] ?? '',
                    'qty' => $qty,
                    'running_total' => $totalPalletQty,
                ]);
            }

            $log->info('Validasi total quantity', [
                'qty_penyerahan' => (float) $qtyoh,
                'total_pallet_qty' => $totalPalletQty,
            ]);

            if (abs($totalPalletQty - (float) $qtyoh) > 0.0001) {
                $log->warning('Receipt gagal: total pallet qty tidak sama', [
                    'total_pallet_qty' => $totalPalletQty,
                    'qty_penyerahan' => (float) $qtyoh,
                    'selisih' => $totalPalletQty - (float) $qtyoh,
                ]);

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Total Qty pallet ('.$totalPalletQty.') harus sama dengan Qty Penyerahan ('.$qtyoh.')',
                ], 422);
            }

            DB::beginTransaction();

            $log->info('Database transaction started', [
                'trfid' => $trfid,
            ]);

            try {

                if ($buildingfrom !== '' && $levelfrom !== '' && $binfrom !== '') {

                    $log->info('Mencari inventory asal', [
                        'part' => $part,
                        'lot' => $lotfrom,
                        'warehouse' => $buildingfrom,
                        'level' => $levelfrom,
                        'bin' => $binfrom,
                    ]);

                    $invFrom = xxinvDet::where('xxinv_part', $part)
                        ->where('xxinv_lot', $lotfrom)
                        ->where('xxinv_wrh', $buildingfrom)
                        ->where('xxinv_level', $levelfrom)
                        ->where('xxinv_bin', $binfrom)
                        ->first();

                    if (! $invFrom) {
                        $log->error('Inventory asal tidak ditemukan', [
                            'part' => $part,
                            'lot' => $lotfrom,
                            'warehouse' => $buildingfrom,
                            'level' => $levelfrom,
                            'bin' => $binfrom,
                        ]);

                        DB::rollBack();

                        return response()->json([
                            'Status' => 'Error',
                            'Message' => 'Data storage asal tidak ditemukan',
                        ], 422);
                    }

                    $qtyBefore = $invFrom->xxinv_qtyoh;

                    $invFrom->xxinv_qtyoh = $invFrom->xxinv_qtyoh - $qtyoh;
                    $invFrom->save();

                    $log->info('Inventory asal berhasil dikurangi', [
                        'inventory_id' => $invFrom->id ?? null,
                        'qty_before' => $qtyBefore,
                        'qty_keluar' => $qtyoh,
                        'qty_after' => $invFrom->xxinv_qtyoh,
                    ]);
                }

                $user = Auth::user()->name;

                $log->info('User receipt', [
                    'user' => $user,
                ]);

                $newTransactionHistoryfrom = new TransactionHistory();
                $newTransactionHistoryfrom->tr_nbr = $trfid;
                $newTransactionHistoryfrom->tr_program = 'Barang Jadi Module';
                $newTransactionHistoryfrom->tr_activity = 'Penerimaan Barang Jadi From';
                $newTransactionHistoryfrom->tr_user = $user ?? '';
                $newTransactionHistoryfrom->tr_part = $part ?? '';
                $newTransactionHistoryfrom->tr_uom = '';
                $newTransactionHistoryfrom->tr_line = '';
                $newTransactionHistoryfrom->tr_lot = $lotfrom ?? '';
                $newTransactionHistoryfrom->tr_qty = $qtyoh ?? '';
                $newTransactionHistoryfrom->tr_date = date('Y-m-d H:i:s');
                $newTransactionHistoryfrom->tr_reference = '';
                $newTransactionHistoryfrom->tr_site = $sitefrom ?? '';
                $newTransactionHistoryfrom->tr_location = $locfrom ?? '';
                $newTransactionHistoryfrom->tr_warehouse = $buildingfrom ?? '';
                $newTransactionHistoryfrom->tr_level = $levelfrom ?? '';
                $newTransactionHistoryfrom->tr_bin = $binfrom ?? '';
                $newTransactionHistoryfrom->tr_remark = $remark;
                $newTransactionHistoryfrom->save();

                $log->info('Transaction history FROM berhasil disimpan', [
                    'tr_nbr' => $trfid,
                    'part' => $part,
                    'lot' => $lotfrom,
                    'qty' => $qtyoh,
                    'warehouse' => $buildingfrom,
                    'level' => $levelfrom,
                    'bin' => $binfrom,
                ]);

                foreach ($pallets as $index => $palletRow) {

                    $palletLevel = trim((string) ($palletRow['level'] ?? ''));
                    $palletBin = trim((string) ($palletRow['bin'] ?? ''));
                    $palletQty = (float) str_replace(',', '', $palletRow['qty'] ?? 0);

                    $log->info('Processing pallet', [
                        'index' => $index,
                        'level' => $palletLevel,
                        'bin' => $palletBin,
                        'qty' => $palletQty,
                    ]);

                    if ($palletLevel === '' || $palletBin === '' || $palletQty <= 0) {

                        $log->warning('Data pallet tidak valid', [
                            'index' => $index,
                            'level' => $palletLevel,
                            'bin' => $palletBin,
                            'qty' => $palletQty,
                        ]);

                        DB::rollBack();

                        return response()->json([
                            'Status' => 'Error',
                            'Message' => 'Level, Bin, dan Qty setiap pallet wajib diisi dengan benar',
                        ], 422);
                    }

                    $invTo = xxinvDet::where('xxinv_part', $part)
                        ->where('xxinv_lot', $lotto)
                        ->where('xxinv_wrh', $buildingto)
                        ->where('xxinv_level', $palletLevel)
                        ->where('xxinv_bin', $palletBin)
                        ->first();

                    if ($invTo) {

                        $qtyBefore = $invTo->xxinv_qtyoh;

                        $invTo->xxinv_qtyoh = $invTo->xxinv_qtyoh + $palletQty;
                        $invTo->save();

                        $log->info('Inventory tujuan di-update', [
                            'inventory_id' => $invTo->id ?? null,
                            'part' => $part,
                            'lot' => $lotto,
                            'warehouse' => $buildingto,
                            'level' => $palletLevel,
                            'bin' => $palletBin,
                            'qty_before' => $qtyBefore,
                            'qty_masuk' => $palletQty,
                            'qty_after' => $invTo->xxinv_qtyoh,
                        ]);

                    } else {
                        $domain = Domain::first();
                        $invTo = new xxinvDet();

                        $invTo->xxinv_part = $part;
                        $invTo->xxinv_lot = $lotto;
                        $invTo->xxinv_loc = $locto;
                        $invTo->xxinv_site = $siteto;
                        $invTo->xxinv_wrh = $buildingto;
                        $invTo->xxinv_level = $palletLevel;
                        $invTo->xxinv_bin = $palletBin;
                        $invTo->xxinv_qtyoh = $palletQty;
                        $invTo->xxinv__dec01 = $palletQty;
                        $invTo->xxinv_domain = $domain->domain;

                        $invTo->save();

                        $log->info('Inventory tujuan baru dibuat', [
                            'inventory_id' => $invTo->id ?? null,
                            'part' => $part,
                            'lot' => $lotto,
                            'warehouse' => $buildingto,
                            'level' => $palletLevel,
                            'bin' => $palletBin,
                            'qty' => $palletQty,
                        ]);
                    }

                    $newPallet = new PenyerahanBarangPallet();
                    $newPallet->pbp_pb_id = $data->id;
                    $newPallet->pbp_level_penyimpanan = $palletLevel;
                    $newPallet->pbp_bin_penyimpanan = $palletBin;
                    $newPallet->pbp_qty_penyimpanan = $palletQty;
                    $newPallet->save();

                    $log->info('Data pallet berhasil disimpan', [
                        'pb_id' => $data->id,
                        'level' => $palletLevel,
                        'bin' => $palletBin,
                        'qty' => $palletQty,
                    ]);

                    $newTransactionHistory = new TransactionHistory();
                    $newTransactionHistory->tr_nbr = $trfid;
                    $newTransactionHistory->tr_order = '';
                    $newTransactionHistory->tr_program = 'Barang Jadi Module';
                    $newTransactionHistory->tr_activity = 'Penerimaan Barang Jadi To';
                    $newTransactionHistory->tr_user = $user ?? '';
                    $newTransactionHistory->tr_part = $part ?? '';
                    $newTransactionHistory->tr_uom = '';
                    $newTransactionHistory->tr_line = '';
                    $newTransactionHistory->tr_lot = $lotto ?? '';
                    $newTransactionHistory->tr_qty = $palletQty;
                    $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
                    $newTransactionHistory->tr_reference = '';
                    $newTransactionHistory->tr_site = $siteto ?? '';
                    $newTransactionHistory->tr_location = $locto ?? '';
                    $newTransactionHistory->tr_warehouse = $buildingto ?? '';
                    $newTransactionHistory->tr_level = $palletLevel;
                    $newTransactionHistory->tr_bin = $palletBin;
                    $newTransactionHistory->tr_remark = $remark;
                    $newTransactionHistory->save();

                    $log->info('Transaction history TO berhasil disimpan', [
                        'tr_nbr' => $trfid,
                        'part' => $part,
                        'lot' => $lotto,
                        'qty' => $palletQty,
                        'warehouse' => $buildingto,
                        'level' => $palletLevel,
                        'bin' => $palletBin,
                    ]);
                }

                $dataupdate = penyerahanBarang::where('pb_trfid', $trfid)->first();

                $dataupdate->pb_status = 'Received';
                $dataupdate->pb_loc_to = $locto;
                $dataupdate->pb_wh_to = $whto;
                $dataupdate->save();

                $log->info('Status penyerahan berhasil di-update', [
                    'trfid' => $trfid,
                    'status' => 'Received',
                    'loc_to' => $locto,
                    'wh_to' => $whto,
                ]);

                DB::commit();

                $log->info('Database transaction COMMITTED', [
                    'trfid' => $trfid,
                    'total_pallet_qty' => $totalPalletQty,
                ]);

                $log->info('=== RECEIPT ITEM PB SUCCESS ===', [
                    'trfid' => $trfid,
                ]);

                return response()->json([
                    'Status' => 'Success',
                    'Message' => 'Receipt Item Successful',
                ], 200);

            } catch (Exception $e) {

                DB::rollBack();

                $log->error('Database transaction ROLLBACK', [
                    'trfid' => $trfid,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Receipt Item Failed :'.$e->getMessage(),
                ], 422);
            }

        } catch (Exception $e) {

            $log->error('Unexpected error receiptItempb', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'Status' => 'Error',
                'Message' => 'Receipt Item Failed :'.$e->getMessage(),
            ], 422);
        }
    }

    public function getPicklistDet(Request $req)
    {
        $statusreq = $req->status;
        $status = str_replace('_', ' ', $statusreq);
        $hasil = (new WSAServices())->wsaGetPickDetail($status);

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {
            $listData = $hasil[1];
        }

        foreach ($listData as $key => $value) {

            $wonbrstring = (string) $value->t_wo_nbr;

            if (strlen($wonbrstring) == 0) {
                $wonbrstring = 'manual';

                if ($currentPick != (string) $value->t_pick_nbr) {
                    $wonbrstring = 'manual';
                    $currentWo = '';

                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string) $value->t_pick_nbr;

                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];

                        $wonbr[$currentWo] = [
                            'wonbrnbr' => $wonbrstring,
                            'wopart' => '',
                            'detail' => $detail,
                        ];

                        $master[$currentPick] = [
                            'picknbr' => (string) $value->t_pick_nbr,
                            'site' => (string) $value->t_site,
                            'status' => (string) $value->t_status,
                            'loc' => (string) $value->t_loc,
                            'wonbr' => $wonbr,
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                    }
                } else {
                    $wonbrstring = 'manual';
                    if ($currentWo != $wonbrstring) {
                        $currentWo = $wonbrstring;

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbrnbr' => $currentWo,
                            'wopart' => '',
                            'woid' => '',
                            'detail' => $detail,
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string) $value->t_pick_nbr,
                            'site' => (string) $value->t_site,
                            'status' => (string) $value->t_status,
                            'loc' => (string) $value->t_loc,
                            'wonbr' => $wonbr,
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                    }
                }
            } else {

                if ($currentPick != (string) $value->t_pick_nbr) {

                    $currentWo = '';
                    $detail = [];
                    $wonbr = [];
                    $currentPick = (string) $value->t_pick_nbr;

                    if ($currentWo != (string) $value->t_wo_nbr) {
                        $currentWo = (string) $value->t_wo_nbr;

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                        $wonbr[$currentWo] = [
                            'wonbrnbr' => (string) $value->t_wo_nbr,
                            'wopart' => (string) $value->t_wo_part,
                            'woid' => (string) $value->t_wo_id,
                            'detail' => $detail,
                        ];
                        $master[$currentPick] = [
                            'picknbr' => (string) $value->t_pick_nbr,
                            'site' => (string) $value->t_site,
                            'status' => (string) $value->t_status,
                            'loc' => (string) $value->t_loc,
                            'wonbr' => $wonbr,
                        ];
                    } else {
                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];
                    }
                } else {
                    if ($currentWo != (string) $value->t_wo_nbr) {

                        $currentWo = (string) $value->t_wo_nbr;

                        $wonbr = [];
                        $detail = [];

                        $detail[] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];

                        $master[$currentPick]['wonbr'][$currentWo] = [
                            'wonbrnbr' => (string) $value->t_wo_nbr,
                            'wopart' => (string) $value->t_wo_part,
                            'detail' => $detail,
                        ];
                    } else {

                        $master[$currentPick]['wonbr'][$currentWo]['detail'][] = [
                            'wodpart' => (string) $value->t_wod_part,
                            'qtyreq' => (string) $value->t_qty_req,
                            'qtypick' => (string) $value->t_qty_pick,
                            'qtytopick' => (string) $value->t_qty_topick,
                            'qtykemasan' => (string) $value->t_qty_kemasan,
                            'lot' => (string) $value->t_lot,
                            'id' => (string) $value->t_wo_id,
                            'wrh' => (string) $value->t_wrh,
                            'level' => (string) $value->t_level,
                            'bin' => (string) $value->t_bin,
                            'dd' => (string) $value->t_duedate,
                            'od' => (string) $value->t_orddate,
                            'rd' => (string) $value->t_reldate,
                        ];

                    }
                }
            }
        }

        return response()->json(
            [
                'DataWSA' => $master,
            ],
            200
        );

        return GeneralResources::collection($data);
    }

    public function wsaSendQtyPick(Request $req)
    {
        $data = $req->all();
        $picknbr = $data['data']['picknbr'];
        $site = $data['data']['site'];
        $loc = $data['data']['loc'];
        $wonbrlist = $data['data']['wonbr'];
        foreach ($wonbrlist as $wo) {
            foreach ($wo['detail'] as $det) {
                if ($wo['wonbrnbr'] == 'manual') {
                    $wonbr = '';
                } else {
                    $wonbr = $wo['wonbrnbr'];
                }

                $wodpart = $det['wodpart'];
                $lot = $det['lot'];
                $wrh = $det['wrh'];
                $level = $det['level'];
                $bin = $det['bin'];
                $qtypick = $det['qtypick'];
                $qxtendsingleitem = (new QxtendServices())->qxTransferSingleItemWo($wodpart, $wonbr, $site, $site, $loc, 'Shopping', $qtypick, $bin, $level, $wrh, $lot);
                if ($qxtendsingleitem == 'false') {
                    Log::channel('Picklist')->info('Transfer Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart);

                    return response()->json([
                        'Status' => 'Error',
                        'Message' => 'Transfer Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart,

                    ], 422);
                } else {
                    $hasil = (new WSAServices())->wsaUpdateQtyPick($picknbr, $qtypick, $wonbr, $wodpart, $site, $loc, $lot, $wrh, $level, $bin);
                    if ($hasil == 'false') {
                        Log::channel('Picklist')->info('Update Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart);

                        return response()->json([
                            'Status' => 'Error',
                            'Message' => 'Update Qty Pick Failed for Picklist : '.$picknbr.' WO : '.$wonbr.' Part : '.$wodpart,
                        ], 422);
                    }
                }
            }
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => 'Update Qty Pick Success',
        ], 200);
    }

    public function getLocationBarangJadi(Request $req)
    {

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';

        $wonbr = '';
        $item = $req->item;
        $site = $req->site;

        $hasil = (new WSAServices())->wsaGetLocationTransfer($wonbr, $item, $site);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
    }

    public function getSiteBarangJadi(Request $req)
    {

        $currentPick = '';
        $currentWo = '';
        $detail = [];
        $master = [];
        $wonbr = [];
        $wonbrstring = '';

        $wonbr = '';
        $site = $req->site ?? '';
        $item = $req->item ?? '';
        $location = $req->location ?? '';
        $hasil = (new WSAServices())->wsaGetSiteTransfer($site, $item, $location);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {
            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
    }

    public function getStrorage(Request $request)
    {
        $query = xxinvDet::with(['itemMaster:im_item_part,im_item_um'])
            ->where('xxinv_part', $request->part);

        if ($request->filled('lot')) {
            $query->where('xxinv_lot', $request->lot);
        }

        $storage = $query->get();

        return response()->json([
            'storage' => $storage,
        ], 200);
    }

    public function wsaWarehouseBarangJadi(Request $req)
    {
        $wsaData = Cache::remember('wsaWarehouse', 60, function () {
            return (new WSAServices())->wsaGenCode('mji_wrh');
        });
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No Data Available',
            ], 422);
        }

        return response()->json($wsaData[1]);
    }

    public function wsainvdetBarangJadi(Request $req)
    {
        $loc = $req->location;
        $site = $req->site;
        $wrh = $req->wrh;
        $item = $req->item;

        $wsaData = (new WSAServices())->wsaGetInvDet($site, $loc, $wrh, $item);
        if ($wsaData[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No Data Available',
            ], 422);
        } else {
            $listData = $wsaData[1];

            return response()->json(['DataWSA' => $listData], 200);
        }

    }

    public function nullConversion($data)
    {
        if ($data == null || strtolower($data) == 'null') {
            return '';
        } else {
            return $data;
        }
    }

    public function getWlbBarangJadi(Request $req)
    {

        $lot = '';
        $loc = $req->loc ?? '';
        $site = '';
        $part = '';
        $wrh = $req->wrh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';

        $hasil = (new WSAServices())->wsaGetWlb($part, $lot, $site, $loc, $wrh, $level, $bin);

        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Data Not Found.',
            ], 422);
        } else {

            $listData = $hasil[1];

            return response()->json(['DataWSA' => $listData], 200);
        }
    }

    public function getWebLocationDataTransfer(Request $req)
    {
        $warehouse = $req->wh ?? '';
        $level = $req->level ?? '';
        $bin = $req->bin ?? '';
        $site = $req->site ?? '';
        $loc = $req->location ?? '';
        $item = $req->item ?? '';
        $lot = $req->lot ?? '';

        $location = Location::where('location_site', $site)->where('location_code', $loc)->first();
        if (! $location) {
            return collect();
        }
        $locationdetail = LocationDetail::query()->where('ld_location_id', $location->id);
        if ($warehouse != '') {
            $locationdetail->where('ld_building', '=', $warehouse);
        }
        if ($level != '') {
            $locationdetail->where('ld_rak', '=', $level);
        }
        if ($bin != '') {
            $locationdetail->where('ld_bin', '=', $bin);
        }

        $locationdetail = $locationdetail->pluck('id')->toArray();

        $itemQuery = Item::with('getItemLocation.getLocationDetail')->where('im_item_part', $item)->select('id')->first();

        if (! $itemQuery) {
            return collect();
        }
        $arrayloc = [];
        $stringloc = '';
        foreach ($locationdetail as $locdetail) {
            $stringloc .= $locdetail.',';
        }

        $getAllItemLocation = ItemLocation::with(['getLocationDetail' => function ($query) {
            $query->orderBy('ld_building');
        }])
            ->where('il_item_id', $itemQuery->id)
            ->whereIn('il_ld_id', $locationdetail)
            ->get();

        if (count($getAllItemLocation) == 0) {
            return response()->json([
                'Status' => 'Error',
                'Message' => 'No Data Available',
            ], 422);
        }
        $hasil = (new WSAServices())->wsaGetWlb($item, $lot, $site, $loc, $warehouse, $level, $bin);

        if ($hasil[0] == 'true') {
            $wsaData = collect($hasil[1]);

            $getAllItemLocation->transform(function ($location) use ($wsaData) {

                $matchingWsa = $wsaData
                    ->where('t_wrh', $location->getLocationDetail->ld_building)
                    ->where('t_level', $location->getLocationDetail->ld_rak)
                    ->where('t_bin', $location->getLocationDetail->ld_bin)
                    ->first();

                $location->getLocationDetail->qty = $matchingWsa['t_qtyoh'] ?? 0;

                return $location;
            });
        }

        return response()->json($getAllItemLocation);
    }

    public function sendBarangJadi(Request $req)
    {
        DB::beginTransaction();

        try {
            $data = $req->all();
            $jumlahPallet = $data['jumlahpallet'] ?? 0;
            $item = $data['item'];
            $sitefrom = $data['sitefrom'];
            $siteto = $this->nullConversion($data['siteto'] ?? null);
            $locfrom = $data['locfrom'];
            $locto = $this->nullConversion($data['locto'] ?? null);
            $whfrom = $this->nullConversion($data['whfrom'] ?? null);
            $levelfrom = $this->nullConversion($data['levelfrom'] ?? null);
            $binfrom = $this->nullConversion($data['binfrom'] ?? null);
            $remark = $this->nullConversion($data['remark'] ?? null);
            $qty = $data['qty'];
            $wh = $this->nullConversion($data['wh'] ?? null);
            $ref = $this->nullConversion($data['ref'] ?? null);
            $level = $this->nullConversion($data['level'] ?? null);
            $bin = $this->nullConversion($data['bin'] ?? null);
            $lot = $this->nullConversion($data['lot'] ?? null);

            $palletRaw = $data['pallet'] ?? '[]';
            $palletList = json_decode($palletRaw, true);

            if (! is_array($palletList)) {
                $palletList = [];
            }

            $prefixTable = penyerahanBarangPrefix::lockForUpdate()->first();

            if ($prefixTable) {

                $prefix = $prefixTable->pbp_prefix;
                $runningnbr = (int) $prefixTable->pbp_running_nbr;

            } else {

                $prefix = 'PB';
                $runningnbr = 0;
            }

            $nextrunningnbr = $runningnbr + 1;

            $newRunningNbr = str_pad(
                $nextrunningnbr,
                6,
                '0',
                STR_PAD_LEFT
            );

            $newPrefix = $prefix.$newRunningNbr;

            $newPenyerahanBarang = new PenyerahanBarang();

            $newPenyerahanBarang->pb_trfid = $newPrefix;
            $newPenyerahanBarang->pb_item = $item;
            $newPenyerahanBarang->pb_site_from = $sitefrom;
            $newPenyerahanBarang->pb_site_to = $siteto;
            $newPenyerahanBarang->pb_loc_from = $locfrom;
            $newPenyerahanBarang->pb_loc_to = $locto;
            $newPenyerahanBarang->pb_wh_from = $whfrom;
            $newPenyerahanBarang->pb_wh_to = $wh;
            $newPenyerahanBarang->pb_ref = $ref;
            $newPenyerahanBarang->pb_remark = $remark;
            $newPenyerahanBarang->pb_qty = $qty;
            $newPenyerahanBarang->pb_level_from = $levelfrom;
            $newPenyerahanBarang->pb_level_to = $level;
            $newPenyerahanBarang->pb_bin_from = $binfrom;
            $newPenyerahanBarang->pb_bin_to = $bin;
            $newPenyerahanBarang->pb_lot = $lot;
            $newPenyerahanBarang->pb_status = 'Open';
            $newPenyerahanBarang->pb_qty_pallete = (int) $jumlahPallet;
            $newPenyerahanBarang->save();

            foreach ($palletList as $palletRow) {
                $newPallet = new PenyerahanBarangPallet();

                $newPallet->pbp_pb_id = $newPenyerahanBarang->id;

                $newPallet->pbp_level_penyimpanan = $palletRow['level'] ?? null;
                $newPallet->pbp_bin_penyimpanan = $palletRow['bin'] ?? null;
                $newPallet->pbp_qty_penyimpanan = isset($palletRow['qty'])
                    ? str_replace(',', '', $palletRow['qty'])
                    : null;

                $newPallet->save();
            }

            if ($prefixTable) {

                $prefixTable->pbp_running_nbr = $nextrunningnbr;
                $prefixTable->save();

            } else {

                $insertprefix = new penyerahanBarangPrefix();

                $insertprefix->pbp_prefix = $prefix;
                $insertprefix->pbp_running_nbr = $nextrunningnbr;

                $insertprefix->save();
            }

            DB::commit();

            return response()->json([
                'Status' => 'Success',
                'Message' => 'Transfer Item Success for Item : '.$item,
                'trfid' => $newPrefix,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::channel('SingleTransfer')->info($e);

            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getpaletpenyerahanbarang(Request $request)
    {
        $item = $request->item;
        $domain = 'MIPI';
        $hasil = (new WSAServices())->wsaGetPalletPenyerahanBarang($item, $domain);

        if ($hasil[0] !== 'true') {
            return response()->json([
                'Status' => 'Error',
                'Message' => $hasil[2] ?: 'Data Not Found.',
            ], 422);
        }

        return response()->json(['DataWSA' => $hasil[1]], 200);
    }

    public function getItemXxinvDet(Request $request)
    {
        $items = xxinvDet::orderBy('xxinv_part')
            ->get();

        return response()->json(
            [
                'items' => $items,
            ],
            200,
        );
    }

    public function getPenyerahanBarang(Request $request)
    {
        $items = PenyerahanBarang::get();

        return response()->json(
            [
                'items' => $items,
            ],
            200,
        );
    }

    public function index(Request $request)
    {
        $data = PenyerahanBarang::query()
            ->leftJoin('item_master', 'item_master.im_item_part', '=', 'penyerahan_barang.pb_item')
            ->where('pb_status', 'open')
            ->select(
                'penyerahan_barang.*',
                'item_master.im_item_desc'
            );

        if ($request->search) {
            $search = $request->search;

            $data->where(function ($q) use ($search) {
                $q->where('penyerahan_barang.pb_item', 'LIKE', "%{$search}%")
                    ->orWhere('penyerahan_barang.pb_trfid', 'LIKE', "%{$search}%")
                    ->orWhere('penyerahan_barang.pb_lot', 'LIKE', "%{$search}%");
            });
        }

        $data = $data->orderBy('penyerahan_barang.id', 'desc')->paginate(10);

        return GeneralResources::collection($data);
    }

    public function update(Request $req)
    {
        DB::beginTransaction();
        try {
            $data = $req->all();

            $trfid = $data['trfid'] ?? null;

            if (! $trfid) {
                DB::rollBack();

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'trfid wajib dikirim untuk update data',
                ], 422);
            }

            $penyerahanBarang = PenyerahanBarang::where('pb_trfid', $trfid)->first();

            if (! $penyerahanBarang) {
                DB::rollBack();

                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Data dengan trfid '.$trfid.' tidak ditemukan',
                ], 404);
            }

            $qty = $data['qty'] ?? $penyerahanBarang->pb_qty;
            $remark = $this->nullConversion($data['remark'] ?? null);

            $penyerahanBarang->pb_qty = $qty;
            $penyerahanBarang->pb_remark = $remark;
            $penyerahanBarang->save();

            DB::commit();

            return response()->json([
                'Status' => 'Success',
                'Message' => 'Transfer Item Updated Success for Item : '.$penyerahanBarang->pb_item,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('SingleTransfer')->info($e);

            return response()->json([
                'Status' => 'Error',
                'Message' => $e->getMessage(),
            ], 422);
        }
    }
}
