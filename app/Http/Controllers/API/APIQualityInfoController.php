<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\ReceiptDetail;
use App\Models\API\ReceiptDetailUserSeenBy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class APIQualityInfoController extends Controller
{
    public function index(Request $req)
    {
        $data = ReceiptDetail::query()
            ->with(['getMaster.getPurchaseOrderMaster'])
            ->withCount(['getUserSeenBy' => function ($query) {
                $query->where('rdup_user', Auth::user()->id);
            }])
            ->where('rd_status', 'Approved')
            ->orderBy('id', 'DESC')
            ->paginate(10);

        return GeneralResources::collection($data);
    }

    public function store(Request $req)
    {
        $idReceiptDetail = $req->id;

        $checkSeen = ReceiptDetailUserSeenBy::where('rdup_rd_id', $idReceiptDetail)->where('rdup_user', Auth::user()->id)->first();
        if (!$checkSeen) {
            $newData = new ReceiptDetailUserSeenBy();
            $newData->rdup_rd_id = $idReceiptDetail;
            $newData->rdup_user = Auth::user()->id;
            $newData->save();
        }

        return response()->json([
            'Status' => 'Success',
            'Message' => 'Data Updated',
        ], 200);
    }
}
