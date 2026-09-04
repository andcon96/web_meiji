<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Settings\qxwsa;
use App\Services\QxtendServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\API\xxinvDet;
use App\Models\API\TransactionHistory;

class ApiSIngelTransferLot extends Controller
{
    public function store(Request $request)
    {
        Log::info('REQUEST', $request->all());

        $part       = $request->input('part');
        $qty        = floatval($request->input('qty', 0));
        $rmks       = $request->input('rmks', '');
        $effdate    = $request->input('effdate', date('Y-m-d'));

        
        $siteFrom   = $request->input('siteFrom');
        $locFrom    = $request->input('locFrom');
        $lotserFrom = $request->input('lotserFrom');
        $wrhFrom    = $request->input('wrhFrom');
        $levelFrom  = $request->input('levelFrom');
        $binFrom    = $request->input('binFrom');

        
        $siteTo     = $request->input('siteTo');
        $locTo      = $request->input('locTo');
        $lotserTo   = $request->input('lotserTo');
        $wrhTo      = $request->input('wrhTo');
        $levelTo    = $request->input('levelTo');
        $binTo      = $request->input('binTo');

        $activeConnection = qxwsa::first();
        $qxtendServices   = new QxtendServices();

        $qxtend = $qxtendServices->qxTransferLotSerial($part, $qty, $siteFrom, $locFrom, $lotserFrom, $lotserTo, $rmks, $effdate, $activeConnection);

        if ($qxtend[0] === false) {
            Log::channel('transferlotserian')->error($qxtend[1]);

            return response()->json([
                'Status'  => 'error',
                'Message' => $qxtend[1] ?? 'Transfer Lot Serial failed.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            
            
            
            $fromRecord = xxinvDet::where('xxinv_part', $part)
                ->where('xxinv_site', $siteFrom)
                ->where('xxinv_loc', $locFrom)
                ->where('xxinv_lot', $lotserFrom)
                ->when($wrhFrom, function ($q) use ($wrhFrom) {
                    return $q->where('xxinv_wrh', $wrhFrom);
                })
                ->when($levelFrom, function ($q) use ($levelFrom) {
                    return $q->where('xxinv_level', $levelFrom);
                })
                ->when($binFrom, function ($q) use ($binFrom) {
                    return $q->where('xxinv_bin', $binFrom);
                })
                ->first();

            if ($fromRecord) {
                $fromRecord->xxinv_qtyoh = $fromRecord->xxinv_qtyoh - $qty;
                $fromRecord->save();
            }

            
            
            
            $toRecord = xxinvDet::where('xxinv_part', $part)
                ->where('xxinv_site', $siteTo)
                ->where('xxinv_loc', $locTo)
                ->where('xxinv_lot', $lotserTo)
                ->when($wrhTo, function ($q) use ($wrhTo) {
                    return $q->where('xxinv_wrh', $wrhTo);
                })
                ->when($levelTo, function ($q) use ($levelTo) {
                    return $q->where('xxinv_level', $levelTo);
                })
                ->when($binTo, function ($q) use ($binTo) {
                    return $q->where('xxinv_bin', $binTo);
                })
                ->first();

            if ($toRecord) {
                $toRecord->xxinv_qtyoh = $toRecord->xxinv_qtyoh + $qty;
                $toRecord->save();
            } else {
                $newInvDet = new xxinvDet();
                $newInvDet->xxinv_domain   = $fromRecord->xxinv_domain ?? 'MIPI';
                $newInvDet->xxinv_part     = $part;
                $newInvDet->xxinv_site     = $siteTo;
                $newInvDet->xxinv_loc      = $locTo;
                $newInvDet->xxinv_lot      = $lotserTo;
                $newInvDet->xxinv_wrh      = $wrhTo;
                $newInvDet->xxinv_level    = $levelTo;
                $newInvDet->xxinv_bin      = $binTo;
                $newInvDet->xxinv_qtyoh    = $qty;
                $newInvDet->xxinv_ref      = $fromRecord->xxinv_ref ?? null;
                $newInvDet->xxinv_exp_date = $fromRecord->xxinv_exp_date ?? null;
                $newInvDet->save();
            }

            
            
            
            $userName = Auth::check() ? Auth::user()->name : 'System';

            
            $trHistoryFrom = new TransactionHistory();
            // $trHistoryFrom->tr_nbr       = 'TRF' . date('YmdHis');
            $trHistoryFrom->tr_program   = 'Single Transfer Lot';
            $trHistoryFrom->tr_activity  = 'Transfer Out';
            $trHistoryFrom->tr_user      = $userName;
            $trHistoryFrom->tr_part      = $part;
            $trHistoryFrom->tr_lot       = $lotserFrom;
            $trHistoryFrom->tr_location  = $locFrom;
            $trHistoryFrom->tr_site      = $siteFrom;
            $trHistoryFrom->tr_reference = $fromRecord->xxinv_ref ?? null;
            $trHistoryFrom->tr_warehouse = $wrhFrom;
            $trHistoryFrom->tr_level     = $levelFrom;
            $trHistoryFrom->tr_bin       = $binFrom;
            $trHistoryFrom->tr_remark    = $rmks;
            $trHistoryFrom->tr_date      = $effdate;
            $trHistoryFrom->tr_qty       = -$qty; 
            $trHistoryFrom->save();

            
            $trHistoryTo = new TransactionHistory();
            $trHistoryTo->tr_nbr       = $trHistoryFrom->tr_nbr;
            $trHistoryTo->tr_program   = 'Single Transfer Lot';
            $trHistoryTo->tr_activity  = 'Transfer In';
            $trHistoryTo->tr_user      = $userName;
            $trHistoryTo->tr_part      = $part;
            $trHistoryTo->tr_lot       = $lotserTo;
            $trHistoryTo->tr_location  = $locTo;
            $trHistoryTo->tr_site      = $siteTo;
            $trHistoryTo->tr_reference = $fromRecord->xxinv_ref ?? null;
            $trHistoryTo->tr_warehouse = $wrhTo;
            $trHistoryTo->tr_level     = $levelTo;
            $trHistoryTo->tr_bin       = $binTo;
            $trHistoryTo->tr_remark    = $rmks;
            $trHistoryTo->tr_date      = $effdate;
            $trHistoryTo->tr_qty       = $qty; 
            $trHistoryTo->save();

            DB::commit();

            Log::channel('transferlotserian')->info('Transfer Lot Serial Success', [
                'part' => $part,
                'qty'  => $qty,
                'site' => $siteFrom,
            ]);

            return response()->json([
                'Status'  => 'success',
                'Message' => 'Shipment / Lot Serial Transfer has been processed successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transferlotserian')->error('Database Update Failed: ' . $e->getMessage());

            return response()->json([
                'Status'  => 'error',
                'Message' => 'Failed to update local inventory details: ' . $e->getMessage(),
            ], 500);
        }
    }
}