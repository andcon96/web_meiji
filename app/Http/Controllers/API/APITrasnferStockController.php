<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\TransferStock;
use App\Services\QxtendServices;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APITrasnferStockController extends Controller
{
    public function index(Request $req)
    {
        $data = TransferStock::query()->with('getUser:id,name,username');

        if ($req->search) {
            $data->where('ts_part', 'LIKE', '%' . $req->search . '%')
                ->orWhere('ts_status', 'LIKE', '%' . $req->search . '%');
        }

        $data = $data->paginate(10);

        return GeneralResources::collection($data);
    }

    public function getStockItemBin(Request $req)
    {
        $hasil = (new WSAServices())->wsaPenyimpanan('', $req->itemCode, '', $req->bin, $req->warehouse, $req->lvl);
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Avail"
            ], 422);
        }

        return response()->json(
            $hasil[1],
        );
    }

    public function getDefaultSampleLoc()
    {
        $hasil = (new WSAServices())->wsaSampleLoc();
        if ($hasil[0] == 'false') {
            return response()->json([
                'Status' => 'Error',
                'Message' => "No Data Avail"
            ], 422);
        }

        return response()->json(
            $hasil[1],
        );
    }

    public function saveTransfer(Request $req)
    {
        try {
            DB::beginTransaction();

            $submitReceiptQxtend = (new QxtendServices())->qxTransferSingleItemWMS(
                $req->itemCode,
                $req->qtyTransfer,
                $req->siteFrom,
                $req->siteTo,
                $req->locFrom,
                $req->locTo,
                $req->lotFrom,
                $req->lotTo,
                $req->buildingFrom,
                $req->buildingTo,
                $req->levelFrom,
                $req->levelTo,
                $req->binFrom,
                $req->binTo
            );
            if ($submitReceiptQxtend == false) {
                DB::rollback();
                return response()->json([
                    'Status' => 'Error',
                    'Message' => "Qxtend Error Connection"
                ], 422);
            }
            if ($submitReceiptQxtend[0] == false) {
                return response()->json([
                    'Status' => 'Error',
                    'Message' => 'Qxtend Error : ' . $submitReceiptQxtend[1]
                ], 422);
            } else {
                $newTransfer = new TransferStock();
                $newTransfer->ts_part = $req->itemCode;
                $newTransfer->ts_part_desc = $req->itemDesc;
                $newTransfer->ts_qty_oh_sample = $req->qtyTransfer;
                $newTransfer->ts_status = 'Created';
                $newTransfer->ts_site_from = $req->siteFrom;
                $newTransfer->ts_site_to = $req->siteTo;
                $newTransfer->ts_loc_from = $req->locFrom;
                $newTransfer->ts_loc_to = $req->locTo;
                $newTransfer->ts_lot_from = $req->lotFrom;
                $newTransfer->ts_lot_to = $req->lotTo;
                $newTransfer->ts_wrh_from = $req->buildingFrom;
                $newTransfer->ts_wrh_to = $req->buildingTo;
                $newTransfer->ts_level_from = $req->levelFrom;
                $newTransfer->ts_level_to = $req->levelTo;
                $newTransfer->ts_bin_from = $req->binFrom;
                $newTransfer->ts_bin_to = $req->binTo;
                $newTransfer->ts_created_by = Auth::user()->id;
                $newTransfer->save();
            }


            DB::commit();
            return response()->json([
                'Status' => 'Sukses',
                'Message' => 'Data Created'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e);
            return response()->json([
                'Status' => 'Error',
                'Message' => "Failed to update data"
            ], 422);
        }
    }
}
