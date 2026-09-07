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
use App\Models\API\xxinvDet;
use App\Services\QxtendServices;
class APITransRctUnpController extends Controller
{
    public function submitRctUnp(Request $req)
    {
        

        try {
          

            DB::beginTransaction();

          
            $qty = floatval(str_replace(',', '', $req->qty));
 
            $qxtendServices = new QxtendServices();
            
            $qxtend = $qxtendServices->qxinventoryReceipt($req);

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
 
                $existingInv->xxinv_qtyoh = $existingInv->xxinv_qtyoh + $qty;
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
            $newTransactionHistory->tr_program = 'Receipt Unplanned Module';
            $newTransactionHistory->tr_activity = 'Submit Receipt';
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
                    'Message' => 'Receipt unplanned success',
                    'MessageDetail' => 'Receipt unplanned success',
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
}
