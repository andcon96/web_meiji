<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\ApprovalReceipt;
use App\Models\Settings\User;
use App\Services\ServerURL;
use Illuminate\Http\Request;

class ApprovalReceiptMasterController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $data = ApprovalReceipt::with(['getUserApprove', 'getUserApproveAlt'])->get();

        return view('setting.approvalReceipt.index', compact('data', 'menuMaster'));
    }

    public function create(Request $request)
    {
        $data = ApprovalReceipt::get();
        $dataDetail = $data->toArray();

        $user = User::get();

        return view('setting.approvalReceipt.edit', compact('data', 'dataDetail', 'user'));
    }

    public function store(Request $request)
    {
        $dataloop = $request->menuLocationDetail;
        foreach ($dataloop as $datas) {
            $cekData = ApprovalReceipt::firstOrNew(['id' => $datas['id']]);
            $cekData->ar_user_approve = $datas['ar_user_approve'];
            $cekData->ar_user_approve_alt = $datas['ar_user_approve_alt'];
            $cekData->ar_sequence = $datas['ar_sequence'];
            $cekData->save();
        }


        toast('Approval updated successfully', 'success');
        return back();
    }
}
