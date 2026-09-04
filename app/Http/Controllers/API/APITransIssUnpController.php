<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InvTransHist;
use App\Services\InbServices;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\API\TransactionHistory;
use App\Services\QxtendServices;
class APITransIssUnpController extends Controller
{
    public function submitIssOut(Request $req)
 {
        

        try {
          

            DB::beginTransaction();

          
            $qty = floatval(str_replace(',', '', $req->qty));
 
            $qxtendServices = new QxtendServices();
            
            $qxtend = $qxtendServices->qxIssueInventoryUnplanned($req);

            if ($qxtend[0] == false) {
                DB::rollback();

                Log::channel('confirmOtherTransaction')->info($qxtend[1]);

                return response()->json(
                    [
                        'Status' => 'Unprocessable',
                        'Message' => $qxtend[1],
                    ],
                    422,
                );
            }
          
            $part = $req->part;
            $site = $req->site;
            $location = $req->location;
            $lotserial = $req->lotserial;
            $warehouse = $req->warehouse;
            $level = $req->level;
            $bin = $req->bin;

            $existingInv = xxinvDet::where('xxinv_part', $part)
                ->where('xxinv_site', $site)
                ->where('xxinv_loc', $location)
                ->where('xxinv_lot', $lotserial)
                ->when($warehouse, function ($q) use ($warehouse) {
                    return $q->where('xxinv_wrh', $warehouse);
                })
                ->when($level, function ($q) use ($level) {
                    return $q->where('xxinv_level', $level);
                })
                ->when($bin, function ($q) use ($bin) {
                    return $q->where('xxinv_bin', $bin);
                })
                ->first();

            if ($existingInv) {
 
                $existingInv->xxinv_qtyoh = $existingInv->xxinv_qtyoh - $qty;
                $existingInv->save();
            } else {
 
                $newInv = new xxinvDet();
                $newInv->xxinv_domain = 'MIPI';
                $newInv->xxinv_part = $part;
                $newInv->xxinv_site = $site;
                $newInv->xxinv_loc = $location;
                $newInv->xxinv_lot = $lotserial;
                $newInv->xxinv_wrh = $warehouse;
                $newInv->xxinv_level = $level;
                $newInv->xxinv_bin = $bin;
                $newInv->xxinv_qtyoh = $qty;
                $newInv->xxinv_ref = $req->lotref ?? null;
                $newInv->xxinv_exp_date = $req->exp_date ?? null;
                $newInv->save();
            }

            $newTransfer = new InvTransHist();
            $newTransfer->trans_type = 'IN';  
            $newTransfer->product_code = $req->part;
            $newTransfer->product_name = $req->partdesc;
            $newTransfer->supplier = $req->supplier;
 
            $newTransfer->location = $req->location;
            $newTransfer->pallet_no = $req->lotserial;  
            $newTransfer->batch_no = $req->lotref; 
            $newTransfer->quantity = $qty;
            $newTransfer->created_by = Auth::user()->id;
            $newTransfer->save();

            $newTransactionHistory = new TransactionHistory();
            $newTransactionHistory->tr_nbr = '';
            $newTransactionHistory->tr_order = '';
            $newTransactionHistory->tr_program = 'Issues Unplanned Module';
            $newTransactionHistory->tr_activity = 'Submit Issues';
            $newTransactionHistory->tr_user = Auth::user()->username ?? '';
            $newTransactionHistory->tr_part = $req->part ?? '';
            $newTransactionHistory->tr_uom = '';
            $newTransactionHistory->tr_line = '';
            $newTransactionHistory->tr_lot = $req->lotserial ?? '';
            $newTransactionHistory->tr_qty = $qty;
            $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
            $newTransactionHistory->tr_reference = $req->lotref ?? '';
            $newTransactionHistory->tr_site = $req->site ?? '';
            $newTransactionHistory->tr_location = $req->location ?? '';
            $newTransactionHistory->tr_warehouse = $req->warehouse ?? '';
            $newTransactionHistory->tr_level = $req->level ?? '';
            $newTransactionHistory->tr_bin = $req->bin ?? '';
            $newTransactionHistory->tr_remark = '';
            $newTransactionHistory->save();

            DB::commit();

            return response()->json(
                [
                    'Status' => 'success',
                    'Message' => 'issues unplanned success',
                    'MessageDetail' => 'issues unplanned success',
                ],
                200,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json(
                [
                    'Status' => 'Error',
                    'Message' => 'Internal server error',
                ],
                500,
            );
        }
    }
    // public function submitIssOut(Request $request)
    // {
    //     /* dd($request->all()); //array key-value */
    //     try {

    //         // throw new Exception('test exception');

    //         DB::beginTransaction();

    //         $part = $request->part;
    //         $partdesc = $request->partdesc;
    //         $supplier = $request->supplier;
    //         $qty = $request->qty;
    //         $site = $request->site;
    //         $location = $request->location;
    //         $lotserial = $request->lotserial;
    //         $lotref = $request->ref;
    //         $warehouse = $request->warehouse ?? '';
    //         $level = $request->level ?? '';
    //         $bin = $request->bin ?? '';
    //         $qty = str_replace(',', '', $qty); // Remove commas from the quantity
    //         $submitQxtendIssunp = (new InbServices())->inbissunp([
    //             'part' => $part,
    //             'qty' => $qty,
    //             'site' => $site,
    //             'location' => $location,
    //             'lotserial' => $lotserial,
    //             'warehouse' => $warehouse,
    //             'level' => $level,
    //             'bin' => $bin,
    //             //'lotref' => $lotref
    //         ]);

    //         if ($submitQxtendIssunp == false) { //jika error koneksi qxtend
    //             DB::rollback();
    //             return response()->json([
    //                 'Status' => 'Error',
    //                 'Message' => "Qxtend Error Connection"
    //             ], 422);
    //         }

    //         if ($submitQxtendIssunp[0] == false) { //jika error dari response qxtend
    //             DB::rollBack();
    //             return response()->json([
    //                 'Status' => 'Error',
    //                 'Message' => 'Qxtend Error : ' . $submitQxtendIssunp[1]
    //             ], 422);
    //         } else {

    //             //simpan history transaksi ketika qxtend sudah berhasil
    //             $newTransfer = new InvTransHist();
    //             $newTransfer->trans_type = 'OUT'; //IN = rct-unp, OUT = iss-unp
    //             $newTransfer->product_code = $part;
    //             $newTransfer->product_name = $partdesc;
    //             $newTransfer->supplier = $supplier;
    //             $newTransfer->site = '';
    //             $newTransfer->location = $location;
    //             $newTransfer->pallet_no = $lotserial; // pallet number
    //             $newTransfer->batch_no = $lotref ?? ''; // batch
    //             $newTransfer->quantity = $qty;
    //             $newTransfer->created_by = Auth::user()->id;
    //             $newTransfer->save();
    //         }
            

    //         $newTransactionHistory = new TransactionHistory();
    //         $newTransactionHistory->tr_nbr = '';
    //         $newTransactionHistory->tr_order = '';
    //         $newTransactionHistory->tr_program = 'Issue Unplanned Module';
    //         $newTransactionHistory->tr_activity = 'Submit Issue';
    //         $newTransactionHistory->tr_user =  Auth::user()->username ?? '';
    //         // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
    //         $newTransactionHistory->tr_part = $part ?? '';
    //         $newTransactionHistory->tr_uom =  '';
    //         $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
    //         $newTransactionHistory->tr_lot = $lotserial ?? '';
    //         // $newTransactionHistory->tr_qty = $data->rd_qty_terima ?? '';
    //         $newTransactionHistory->tr_qty = str_replace(',', '', $qty) ?? '';
    //         $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
    //         $newTransactionHistory->tr_reference =  '';
    //         $newTransactionHistory->tr_site =  $site ?? '';
    //         $newTransactionHistory->tr_location = $location ?? '';
    //         $newTransactionHistory->tr_warehouse = $warehouse ?? '';
    //         $newTransactionHistory->tr_level = $level ?? '';
    //         $newTransactionHistory->tr_bin = $bin ?? '';
    //         $newTransactionHistory->tr_remark = '';
    //         $newTransactionHistory->save();
    //         DB::commit();

    //         return response()->json([
    //             'Status' => 'success',
    //             'Message' => 'Transaction Out Successfully'
    //         ], 200);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error($e);
    //         return response()->json([
    //             'Status' => 'error',
    //             'Message' => 'Internal server error' /* 'Failed to submit transaction' */,
    //         ], 422);
    //     }
    // }
}
