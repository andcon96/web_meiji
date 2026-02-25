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
class APITransRctUnpController extends Controller
{
    public function submitRctUnp(Request $req)
    {
        /* dd($req->all()); //array key-value */

        try {
            /* throw new Exception('test exception'); */

            DB::beginTransaction();

            $rctunp = (new InbServices)->inbrctunp($req->all());

            if ($rctunp == false) { //jika error koneksi qxtend
                DB::rollback();
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Qxtend Error Connection"
                ], 500);
            }

            if ($rctunp[0] == false) { //jika error dari response qxtend
                DB::rollBack();

                return response()->json([
                    'Status' => 'Unprocessable',
                    'Message' => $rctunp[1], //berisi message error qxtend
                ], 422); //
            }

            $inputSupplier = (new WSAServices)->wsaInputSupplier($req->part, $req->lotserial, $req->supplier);
            $message = '';

            if ($inputSupplier == false) {
                $message = 'Error Connection: WSA input supplier ';
            }

            if ($inputSupplier == 'false') {
                $message = 'Error Response: WSA input supplier ';
            }

            $newTransfer = new InvTransHist();
            $newTransfer->trans_type   = 'IN'; //IN = rct-unp, OUT = iss-unp
            $newTransfer->product_code = $req->part;
            $newTransfer->product_name = $req->partdesc;
            $newTransfer->supplier     = $req->supplier;
            /* $newTransfer->site         = $req->site; */
            $newTransfer->location     = $req->location;
            $newTransfer->pallet_no    = $req->lotserial; // pallet number
            $newTransfer->batch_no     = $req->lotref; // batch
            $newTransfer->quantity     = $req->qty;
            $newTransfer->created_by   = Auth::user()->id;
            $newTransfer->save();


            $newTransactionHistory = new TransactionHistory();
            $newTransactionHistory->tr_nbr = '';
            $newTransactionHistory->tr_order = '';
            $newTransactionHistory->tr_program = 'Receipt Unplanned Module';
            $newTransactionHistory->tr_activity = 'Submit Receipt';
            $newTransactionHistory->tr_user = Auth::user()->id ?? '';
            // $newTransactionHistory->tr_part = $data->nama_barang ?? '';
            $newTransactionHistory->tr_part = $req->part ?? '';
            $newTransactionHistory->tr_uom =  '';
            $newTransactionHistory->tr_line = ''; // Tambahkan nilai tr_line jika diperlukan
            $newTransactionHistory->tr_lot = $req->lotserial ?? '';
            // $newTransactionHistory->tr_qty = $data->rd_qty_terima ?? '';
            $newTransactionHistory->tr_qty = str_replace(',', '', $req->qty) ?? '';
            $newTransactionHistory->tr_date = date('Y-m-d H:i:s');
            $newTransactionHistory->tr_reference =  '';
            $newTransactionHistory->tr_site = $req->site ?? '';
            $newTransactionHistory->tr_location = $req->location ?? '';
            $newTransactionHistory->tr_warehouse = $req->warehouse ?? '';
            $newTransactionHistory->tr_level = $req->level ?? '';
            $newTransactionHistory->tr_bin = $req->bin ?? '';
            $newTransactionHistory->tr_remark = '';
            $newTransactionHistory->save();
            DB::commit();
            return response()->json([
                'Status' => 'success',
                'Message' => 'Receipt unplanned success',
                'MessageDetail' => $message
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => 'Internal server error',
            ], 500);
        }
    }
}
