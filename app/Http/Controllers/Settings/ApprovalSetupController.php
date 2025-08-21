<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\ApprovalSetupDet;
use App\Models\Settings\ApprovalSetupMstr;
use App\Models\Settings\Menu;
use App\Models\Settings\User;
use App\Services\ServerURL;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalSetupController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $data = ApprovalSetupMstr::with(['getMenu'])->get();

        return view('setting.approvalSetup.index', compact('data', 'menuMaster'));
    }

    public function create()
    {
        $menus = Menu::orderBy('menu_name')->get();
        $user = User::orderBy('name')->get();
        return view('setting.approvalSetup.create', compact('menus', 'user'));
    }

    public function edit($id)
    {
        $menus = Menu::orderBy('menu_name')->get();
        $user = User::orderBy('name')->get();
        $data = ApprovalSetupMstr::with(['getApprovalSetupDet', 'getMenu'])->find($id);
        $dataDetail = $data->getApprovalSetupDet->toArray();

        return view('setting.approvalSetup.edit', compact('menus', 'user', 'data', 'dataDetail'));
    }

    public function store(Request $request)
    {
        $menu = $request->menu;
        $userApprover = $request->userApprover;

        DB::beginTransaction();

        try {
            $approvalSetupMstr = new ApprovalSetupMstr();
            $approvalSetupMstr->menu_id = $menu;
            $approvalSetupMstr->created_by = Auth::user()->id;
            $approvalSetupMstr->save();

            foreach ($userApprover as $approver) {
                $approvalSetupDet = new ApprovalSetupDet();
                $approvalSetupDet->asm_id = $approvalSetupMstr->id;
                $approvalSetupDet->asd_approval_user = implode(';', $approver['ar_user_approve']);
                $approvalSetupDet->asd_approval_sequence = $approver['ar_sequence'];
                $approvalSetupDet->created_by = Auth::user()->id;
                $approvalSetupDet->save();
            }

            toast('Success', 'Successfully created approval setup');
            DB::commit();
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);

            toast('Error', 'Failed to create approval setup');
        }

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $menu = $request->menu;
        $userApprover = $request->userApprover;

        DB::beginTransaction();

        try {
            $approvalSetupMstr = ApprovalSetupMstr::find($id);
            $approvalSetupMstr->menu_id = $menu;
            $approvalSetupMstr->updated_by = Auth::user()->id;
            $approvalSetupMstr->save();

            foreach ($userApprover as $approver) {
                if ($approver['id'] == '' || $approver['id'] == null) {
                    $approvalSetupDet = new ApprovalSetupDet();
                    $approvalSetupDet->asm_id = $id;
                    $approvalSetupDet->created_by = Auth::user()->id;
                } else {
                    $approvalSetupDet = ApprovalSetupDet::where('id', $approver['id'])->first();
                    $approvalSetupDet->updated_by = Auth::user()->id;
                }
                $approvalSetupDet->asd_approval_user = implode(';', $approver['asd_approval_user']);
                $approvalSetupDet->asd_approval_sequence = $approver['asd_approval_sequence'];
                $approvalSetupDet->save();
            }

            DB::commit();

            toast('Success', 'Successfully updated approval setup');
        } catch (Exception $err) {
            DB::rollBack();
            dd($err);

            toast('Error', 'Failed to update approval setup');
        }

        return redirect()->back();
    }
}
