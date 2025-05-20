<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Department;
use App\Models\Settings\Domain;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DepartmentController extends Controller
{
    public function index (Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $departments = Department::orderBy('department_desc')->get();

        return view('setting.department.index', compact('departments', 'menuMaster'));
    }

    public function create ()
    {
        // Buat ambil sub account
        $domainMaster = Domain::where('id', Session::get('domain'))->first();
        
        $wsaSubAccount = (new WSAServices())->wsaSubAccount($domainMaster->id, $domainMaster->domain);
        $subAccounts = [];

        if ($wsaSubAccount[0] == 'false') {
            toast('No sub account can be found, please contact admin', 'info');
        } else {
            $subAccounts = $wsaSubAccount[1];
        }

        // Buat ambil cost center
        $wsaCostCenter = (new WSAServices())->wsaCostCenter($domainMaster->id, $domainMaster->domain);
        $costCenters = [];

        if ($wsaCostCenter[0] == 'false') {
            toast('No sub account can be found, please contact admin', 'info');
        } else {
            $costCenters = $wsaCostCenter[1];
        }

        return view('setting.department.create', compact('subAccounts', 'costCenters'));
    }

    public function edit ($id)
    {
        $departmentID = $id;

        $department = Department::where('id', $departmentID)->first();

        // Buat ambil sub account
        $domainMaster = Domain::where('id', Session::get('domain'))->first();

        $wsaSubAccount = (new WSAServices())->wsaSubAccount($domainMaster->id, $domainMaster->domain);
        $subAccounts = [];

        if ($wsaSubAccount[0] == 'false') {
            toast('No sub account can be found, please contact admin', 'info');
        } else {
            $subAccounts = $wsaSubAccount[1];
        }

        // Buat ambil cost center
        $wsaCostCenter = (new WSAServices())->wsaCostCenter($domainMaster->id, $domainMaster->domain);
        $costCenters = [];

        if ($wsaCostCenter[0] == 'false') {
            toast('No sub account can be found, please contact admin', 'info');
        } else {
            $costCenters = $wsaCostCenter[1];
        }
        
        return view('setting.department.edit', compact('department', 'subAccounts', 'costCenters'));
    }

    public function store (Request $request)
    {
        $deptCode = $request->departmentCode;
        $deptDesc = $request->departmentDesc;
        $subAccount = $request->subAccount;
        $costCenter = $request->costCenter;

        $currentUser = Auth::user()->id;

        $departmentExists = Department::where('department_code', $deptCode)->first();

        if ($departmentExists) {
            toast('Department code already exists', 'info');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $department = new Department();
            $department->department_code = $deptCode;
            $department->department_desc = $deptDesc;
            $department->sub_account = $subAccount;
            $department->cost_center = $costCenter;
            $department->created_by = $currentUser;
            $department->save();

            DB::commit();

            toast('Department saved successfully', 'success');

            return redirect()->back();
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to save department', 'error');
            return redirect()->back()->withInput();
        }
    }

    public function update (Request $request)
    {
        $id = $request->u_id;
        $departmentCode = $request->departmentCode;
        $departmentDesc = $request->departmentDesc;
        $subAccount = $request->subAccount;
        $costCenter = $request->costCenter;

        $currentUser = Auth::user()->id;

        $department = Department::where('id', $id)->first();

        DB::beginTransaction();

        try {
            $department->department_desc = $departmentDesc;
            $department->sub_account = $subAccount;
            $department->cost_center = $costCenter;
            $department->updated_by = $currentUser;
            if ($department->isDirty()) {
                $department->save();

                DB::commit();

                toast('Department udpated successfully', 'success');
            } else {
                toast('There were no changes', 'info');
            }

            return redirect()->route('departments.index');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update department', 'error');
            return redirect()->back()->withInput();
        }
    }

    public function delete (Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            Department::where('id', $id)->delete();

            DB::commit();
            
            toast('Department deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete department', 'error');
        }

        return redirect()->back();
    }
}
