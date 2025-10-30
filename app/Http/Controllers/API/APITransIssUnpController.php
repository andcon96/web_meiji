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

class APITransIssUnpController extends Controller
{
    //
    public function submitIssOut(Request $request)
    {
        /* dd($request->all()); //array key-value */
        try {
        
            DB::beginTransaction();

            $part = $request->part;
            $partdesc = $request->partdesc;
            $supplier = $request->supplier;
            $qty = $request->qty;
            // $site = $request->site;
            $location = $request->location;
            $lotserial = $request->lotserial;
            $lotref = $request->ref;

            $submitQxtendIssunp = (new InbServices())->inbissunp([
                'part' => $part,
                'qty' => $qty,
                // 'site' => $site,
                'location' => $location,
                'lotserial' => $lotserial,
                'lotref' => $lotref
            ]);

            if ($submitQxtendIssunp == false) { //jika error koneksi qxtend
                DB::rollback();
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Qxtend Error Connection"
                ], 422);
            }

            if ($submitQxtendIssunp[0] == false) { //jika error dari response qxtend
                DB::rollBack();
                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Qxtend Error : ' . $submitQxtendIssunp[1]
                ], 422);
            } else {

                //simpan history transaksi ketika qxtend sudah berhasil
                $newTransfer = new InvTransHist();
                $newTransfer->trans_type = 'OUT'; //IN = rct-unp, OUT = iss-unp
                $newTransfer->product_code = $part;
                $newTransfer->product_name = $partdesc;
                $newTransfer->supplier = $supplier;
                $newTransfer->site = '';
                $newTransfer->location = $location;
                $newTransfer->pallet_no = $lotserial; // pallet number
                $newTransfer->batch_no = $lotref; // batch
                $newTransfer->quantity = $qty;
                $newTransfer->created_by = Auth::user()->id;
                $newTransfer->save();
            }


            DB::commit();
            return response()->json([
                'Status' => 'success',
                'Message' => 'Transaction Out Successfully'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit transaction',
            ],422);
        }
    }
}
