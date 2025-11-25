<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Role;
use App\Models\Settings\User;
use Illuminate\Http\Request;
use App\Services\ServerURL;
use Illuminate\Support\Facades\Auth;
use App\Models\Settings\MenuAccess;
use App\Models\Settings\Menu;
class RoleAccessWebController extends Controller
{
    public function index(Request $request)
    {
        $menuroutelist = ['OtherShipmentScheduleReport','ShipmentScheduleReport','getReceiptBook'];
        $menuMaster = (new ServerURL())->currentURL($request);
        $menuList = Menu::whereIn('menu_route', $menuroutelist)->get();

        $roles = Role::with('getMenuAccess')
       
        ->get();
        // foreach ($roles as $role) {
        //     dd($role->getMenuAccess);
        // }

        return view('setting.roleWebMenu.index', compact('roles', 'menuMaster','menuList'));
    }

    public function updateRoleAccess(Request $request)
    {
        // dd($request->all(),Auth::user());
        foreach ($request->data as $datas){
        
        
        
            $userdata = Auth::user();
            $menuAccess = MenuAccess::where('role_id',$userdata->role_id)->where('menu_id',$datas)->first();
            if(!$menuAccess){
                $menuAccess = new MenuAccess();
                $menuAccess->role_id = $userdata->role_id;
                $menuAccess->menu_id = $datas;
                $menuAccess->save();
            
            }
        }


        toast('Successfully Update Role Access', 'success');
        return back();
    }
}
