<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Role;
use App\Models\Settings\User;
use Illuminate\Http\Request;
use App\Services\ServerURL;
use Illuminate\Support\Facades\Auth;

class RoleAndroidMenuController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $roles = Role::get();

        return view('setting.roleAndroidMenu.index', compact('roles', 'menuMaster'));
    }

    public function updateRoleAccess(Request $request)
    {
        $accessData = 'PO;TS;';
        foreach ($request->data as $datas) {
            $accessData .= $datas . ';';
        }

        $role = Role::findOrFail($request->roleId);
        $role->role_android_acc = $accessData;
        $role->save();


        toast('Successfully Update Role Access', 'success');
        return back();
    }
}
