<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Domain;
use App\Models\Settings\Role;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $roles = Role::orderBy('role_desc')->get();
        return view('setting.role.index', compact('roles', 'menuMaster'));
    }

    public function create()
    {
        return view('setting.role.create');
    }

    public function edit($id)
    {
        $dataRole = Role::where('id', $id)->first();

        return view('setting.role.edit', compact('dataRole'));
    }

    public function store(Request $request)
    {
        $domain_id = $request->domain_id;
        $roleCode = $request->roleCode;
        $roleDesc = $request->roleDesc;

        $currentUser = Auth::user()->id;

        DB::beginTransaction();

        try {
            $role = new Role();
            $role->domain_id = $domain_id;
            $role->role_code = $roleCode;
            $role->role_desc = $roleDesc;
            $role->created_by = $currentUser;
            $role->save();

            DB::commit();

            toast('Role saved successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to save role', 'error');
        }

        return redirect()->back()->withInput();
    }

    public function update(Request $request)
    {
        $domain_id = $request->domain_id;
        $id = $request->u_id;
        $roleDesc = $request->roleDesc;

        $role = Role::where('id', $id)->first();

        DB::beginTransaction();

        try {
            $role->domain_id = $domain_id;
            $role->role_desc = $roleDesc;
            if ($role->isDirty()) {
                $role->save();

                DB::commit();
                toast('Role updated successfully', 'success');
            } else {
                toast('No changes were made', 'info');
            }

            return redirect()->route('roles.index');
        } catch (\Exception $err) {
            DB::rollBack();
            toast('Failed to update role', 'error');

            return redirect()->back();
        }
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            Role::where('id', $id)->delete();

            DB::commit();

            toast('Role deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete role', 'error');
        }

        return redirect()->back();
    }
}
