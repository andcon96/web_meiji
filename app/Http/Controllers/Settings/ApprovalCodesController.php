<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Imports\loadApproval;
use App\Models\Settings\ApprovalCodeDet;
use App\Models\Settings\ApprovalCodeMstr;
use App\Models\Settings\Department;
use App\Models\Settings\Domain;
use App\Models\Settings\Menu;
use App\Models\Settings\Role;
use App\Models\Settings\User;
use App\Services\ServerURL;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class ApprovalCodesController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);

        $approvalCodes = ApprovalCodeMstr::with(['getDomain', 'getApprovalCodeDetail', 'getMenu'])->orderBy('acm_code')->get();

        $menus = Menu::where('has_approval', 'Yes')->get();

        return view('setting.approvalCode.index', compact('menuMaster', 'approvalCodes', 'menus'));
    }

    public function create(Request $request)
    {
        $departments = Department::orderBy('department_desc')->get();
        $menus = Menu::where('has_approval', 'Yes')->orderBy('menu_name')->get();
        $domains = Domain::orderBy('domain')->get();
        $users = User::orderBy('name')->get();
        $roles = Role::orderBy('role_desc')->get();

        return view('setting.approvalCode.create', compact('domains', 'menus', 'users', 'roles', 'departments'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $domain_id = $request->approvalCodeDomain;
        $department_id = $request->department;
        $menu_id = $request->menu;
        $approval_code = $request->approvalCode;
        $approval_desc = $request->approvalDesc;
        $sequences = $request->sequence;
        $roles = $request->approval_role;
        $users = $request->approval_user;
        $notif_roles = $request->notif_role;
        $notif_users = $request->notif_user;
        $amount_limit = $request->amount_limit;
        $viewAccounts = $request->viewAccounts;
        $isBuyer = $request->isBuyer;
        $canEdit = $request->canEdit;
        $fromTransaction = $request->fromTransaction;

        // Check if approval for this domain and department already exists
        $approvalExists = ApprovalCodeMstr::where('domain_id', $domain_id)
            ->where('department_id', $department_id)
            ->where('menu_id', $menu_id)
            ->where('acm_code', $approval_code)
            ->first();

        if ($approvalExists) {
            toast('Approval master already exists', 'info');
            return redirect()->back();
        }

        DB::beginTransaction();

        try {
            // Create approval master setup
            $approvalMaster = new ApprovalCodeMstr();
            $approvalMaster->domain_id = $domain_id;
            $approvalMaster->department_id = $department_id;
            $approvalMaster->menu_id = $menu_id;
            $approvalMaster->acm_code = $approval_code;
            $approvalMaster->acm_desc = $approval_desc;
            $approvalMaster->save();

            // Create approval detail setup

            foreach ($sequences as $key => $sequence) {
                $formattedRole = '';
                $formattedUser = '';
                $formattedNotifRole = '';
                $formattedNotifUser = '';

                if ($roles != null && array_key_exists($key, $roles)) {
                    $formattedRole = implode(';', $roles[$key]['option']);
                }

                if ($users != null && array_key_exists($key, $users)) {
                    $formattedUser = implode(';', $users[$key]['option']);
                }

                if ($notif_roles != null && array_key_exists($key, $notif_roles)) {
                    $formattedNotifRole = implode(';', $notif_roles[$key]['option']);
                }

                if ($notif_users != null && array_key_exists($key, $notif_users)) {
                    $formattedNotifUser = implode(';', $notif_users[$key]['option']);
                }

                $approvalDet = new ApprovalCodeDet();
                $approvalDet->acm_id = $approvalMaster->id;
                $approvalDet->acd_sequence = $sequence;
                $approvalDet->acd_approval_role = $formattedRole;
                $approvalDet->acd_approval_user = $formattedUser;
                $approvalDet->acd_notify_role = $formattedNotifRole;
                $approvalDet->acd_notify_user = $formattedNotifUser;
                if (isset($amount_limit) && array_key_exists($key, $amount_limit)) {
                    $approvalDet->amount_limit = $amount_limit[$key];
                }
                if (isset($viewAccounts) && array_key_exists($key, $viewAccounts)) {
                    $approvalDet->acd_view_accounts = $viewAccounts[$key];
                } else {
                    $approvalDet->acd_view_accounts = 'No';
                }
                if (isset($isBuyer) && array_key_exists($key, $isBuyer)) {
                    $approvalDet->acd_is_buyer = $isBuyer[$key];
                } else {
                    $approvalDet->acd_is_buyer = 'No';
                }
                if (isset($canEdit) && array_key_exists($key, $canEdit)) {
                    $approvalDet->acd_can_edit = $canEdit[$key];
                } else {
                    $approvalDet->acd_can_edit = 'No';
                }
                if (isset($fromTransaction) && array_key_exists($key, $fromTransaction)) {
                    $approvalDet->acd_from_transaction = $fromTransaction[$key];
                } else {
                    $approvalDet->acd_from_transaction = 'No';
                }
                $approvalDet->created_by = Auth::user()->id;
                $approvalDet->save();
            }

            DB::commit();
            toast('Approval code saved successfully', 'success');

            return redirect()->back();
        } catch (Exception $err) {
            DB::rollBack();
            // dd($err);
            toast('Failed to save approval master', 'error');
            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $approvalCode = ApprovalCodeMstr::with(['getDomain', 'getMenu', 'getDepartment', 'getApprovalCodeDetail.getRole'])->where('id', $id)->first();

        return view('setting.approvalCode.show', compact('approvalCode'));
    }

    public function edit($id)
    {
        $departments = Department::orderBy('department_desc')->get();
        $domains = Domain::orderBy('domain_desc')->get();
        $menus = Menu::where('has_approval', 'Yes')->orderBy('menu_name')->get();
        $users = User::orderBy('name')->get();
        $roles = Role::orderBy('role_desc')->get();
        $approvalCode = ApprovalCodeMstr::with(['getDomain', 'getDepartment', 'getApprovalCodeDetail.getRole'])->where('id', $id)->first();

        return view('setting.approvalCode.edit', compact(
            'departments', 'domains', 'users', 'roles', 'approvalCode', 'menus'
        ));
    }

    public function update(Request $request)
    {
        // dump($request->all());
        // dd($request->all());
        $id = $request->u_id;
        $domain_id = $request->approvalCodeDomain;
        $department_id = $request->department;
        $menu_id = $request->menu;
        $approval_code = $request->approvalCode;
        $approval_desc = $request->approvalDesc;
        $sequences = $request->sequence;
        $roles = $request->approval_role;
        $users = $request->approval_user;
        $notif_roles = $request->notif_role;
        $notif_users = $request->notif_user;
        $amount_limit = $request->amount_limit;
        $viewAccounts = $request->viewAccounts;
        $isBuyer = $request->isBuyer;
        $canEdit = $request->canEdit;
        $fromTransaction = $request->fromTransaction;
        $idDetail = $request->idDetail;

        // Check if approval for this domain and department already exists
        $approvalExists = ApprovalCodeMstr::where('domain_id', $domain_id)
            ->where('department_id', $department_id)
            ->where('menu_id', $menu_id)
            ->where('acm_code', $approval_code)
            ->where('id', '!=', $id)
            ->first();

        if ($approvalExists) {
            toast('Approval master already exists', 'info');
            return redirect()->back();
        }

        DB::beginTransaction();

        try {
            $approvalMaster = ApprovalCodeMstr::where('id', $id)->first();
            $approvalMaster->domain_id = $domain_id;
            $approvalMaster->department_id = $department_id;
            $approvalMaster->menu_id = $menu_id;
            $approvalMaster->acm_code = $approval_code;
            $approvalMaster->acm_desc = $approval_desc;
            if ($approvalMaster->isDirty()) {
                $approvalMaster->save();
            }

            $newIDDetail = array_filter($idDetail, function ($value) {
                return $value !== 'New';
            });

            // Check the deleted user / role in existsing data
            ApprovalCodeDet::where('acm_id', $id)->whereNotIn('id', $newIDDetail)->delete();

            // Add new line
            foreach ($sequences as $key => $sequence) {
                $formattedRole = '';
                $formattedUser = '';
                $formattedNotifRole = '';
                $formattedNotifUser = '';

                if ($roles != null && array_key_exists($key, $roles)) {
                    $formattedRole = implode(';', $roles[$key]['option']);
                }

                if ($users != null && array_key_exists($key, $users)) {
                    $formattedUser = implode(';', $users[$key]['option']);
                }

                if ($notif_roles != null && array_key_exists($key, $notif_roles)) {
                    $formattedNotifRole = implode(';', $notif_roles[$key]['option']);
                }

                if ($notif_users != null && array_key_exists($key, $notif_users)) {
                    $formattedNotifUser = implode(';', $notif_users[$key]['option']);
                }

                $approvalDet = ApprovalCodeDet::where('id', $idDetail[$key])->first();
                if ($approvalDet) {
                    $approvalDet->updated_by = Auth::user()->id;
                } else {
                    $approvalDet = new ApprovalCodeDet();
                    $approvalDet->acm_id = $approvalMaster->id;
                    $approvalDet->created_by = Auth::user()->id;
                }

                $approvalDet->acd_sequence = $sequence;
                $approvalDet->acd_approval_role = $formattedRole;
                $approvalDet->acd_approval_user = $formattedUser;
                $approvalDet->acd_notify_role = $formattedNotifRole;
                $approvalDet->acd_notify_user = $formattedNotifUser;
                if (isset($amount_limit[$key])) {
                    $approvalDet->amount_limit = $amount_limit[$key];
                }
                if (isset($viewAccounts[$key])) {
                    $approvalDet->acd_view_accounts = $viewAccounts[$key];
                } else {
                    $approvalDet->acd_view_accounts = 'No';
                }

                if (isset($isBuyer) && array_key_exists($key, $isBuyer)) {
                    $approvalDet->acd_is_buyer = $isBuyer[$key];
                } else {
                    $approvalDet->acd_is_buyer = 'No';
                }
                if (isset($canEdit) && array_key_exists($key, $canEdit)) {
                    $approvalDet->acd_can_edit = $canEdit[$key];
                } else {
                    $approvalDet->acd_can_edit = 'No';
                }
                if (isset($fromTransaction) && array_key_exists($key, $fromTransaction)) {
                    $approvalDet->acd_from_transaction = $fromTransaction[$key];
                } else {
                    $approvalDet->acd_from_transaction = 'No';
                }
                $approvalDet->save();
            }

            DB::commit();
            toast('Approval code updated successfully', 'success');

            return redirect()->route('approvalCodes.index');
        } catch (\Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to update approval master', 'error');

            return redirect()->back()->withInput();
        }
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            // Delete the approval code detail first then delete the approval code master
            ApprovalCodeDet::where('acm_id', $id)->delete();

            ApprovalCodeMstr::where('id', $id)->delete();

            DB::commit();

            toast('Approval code deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();
            toast('Failed to delete approval code', 'error');
        }

        return redirect()->back();
    }

    public function loadApproval(Request $request)
    {
        // Retrieve the uploaded file
        $menu_id = $request->loadMenuSelect;
        $file = $request->file('file'); // Make sure to use the correct input name
        $domain_id = Session::get('domain');

        // Store the uploaded file in the 'uploads' directory in 'storage/app'
        $filePath = $file->store('uploads');

        // Use the stored path for Excel import
        $data = Excel::toArray([], storage_path('app/' . $filePath));

        // Extract headers and rows to be sorted
        $rows = array_slice($data[0], 1);
        $groupedByDept = collect($rows)->groupBy(3);
        // dd($groupedByDept);

        $domainMaster = Domain::where('id', $domain_id)->first();

        DB::beginTransaction();

        try {
            foreach ($groupedByDept as $key => $dept) {
                // dd($dept);
                $departmentMaster = Department::where('department_code', $key)->first();

                $approvalCodeMstr = ApprovalCodeMstr::where('menu_id', $menu_id)
                    ->where('department_id', $departmentMaster->id)
                    ->first();

                if (!$approvalCodeMstr) {
                    $acm_code = 'PR' . substr($departmentMaster->department_code, 0, 3);
                    $approvalCodeMstr = new ApprovalCodeMstr();
                    $approvalCodeMstr->domain_id = $domain_id;
                    $approvalCodeMstr->department_id = $departmentMaster->id;
                    $approvalCodeMstr->menu_id = $menu_id;
                    $approvalCodeMstr->acm_code = $acm_code;
                    $approvalCodeMstr->acm_desc = 'PR approval for department ' . $departmentMaster->department_code . ' in ' . $domainMaster->domain;
                    $approvalCodeMstr->save();
                    // dd($approvalCodeMstr);
                }

                // Find the department
                $approvalCodeDet = ApprovalCodeDet::where('acm_id', $approvalCodeMstr->id)->get();
                // dd(!$approvalCodeDet);
                if ($approvalCodeDet->count() == 0) {
                    $sequences = $dept->pluck(1);
                    $users = $dept->pluck(2);
                    $amounts = $dept->pluck(0);
                    $viewAccounts = $dept->pluck(4);
                    $buyers = $dept->pluck(5);
                    $userMaster = User::whereIn(DB::raw('LOWER(username)'), collect($users)->map(function ($user) {
                        return strtolower($user);
                    }))->pluck('id');
                    // dump($users, $userMaster);
                    foreach ($users as $userKey => $user) {
                        // dd($user, $userKey, $userMaster[$userKey]);
                        // if (!isset($userMaster[$userKey])) {
                        //     dd($userMaster, $users, $key);
                        // }
                        $approvalCodeDet = new ApprovalCodeDet();
                        $approvalCodeDet->acm_id = $approvalCodeMstr->id;
                        $approvalCodeDet->acd_sequence = $sequences[$userKey];
                        $approvalCodeDet->acd_approval_user = $userMaster[$userKey];
                        $approvalCodeDet->acd_notify_user = $userMaster[$userKey];
                        $approvalCodeDet->amount_limit = $amounts[$userKey];
                        $approvalCodeDet->acd_view_accounts = $viewAccounts[$userKey];
                        $approvalCodeDet->acd_is_buyer = $buyers[$userKey];
                        $approvalCodeDet->created_by = Auth::user()->id;
                        $approvalCodeDet->save();
                        // dump($userKey, $sequences[$userKey]);
                    }
                }
            }

            DB::commit();

            toast('Load successful', 'success');
        } catch (Exception $err) {
            DB::rollBack();
            // dd($err);

            toast('Failed to upload approval setup', 'error');
        }
        return redirect()->back();
    }

}
