<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\MenuAccess;
use App\Models\Settings\MenuStructure;
use App\Models\Settings\Role;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class MenuAccessController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);

        $roles = Role::where('domain_id', Session::get('domain'))->orderBy('role_code')->get();

        return view('setting.menuaccess.index', compact('menuMaster', 'roles'));
    }

    public function edit($id)
    {
        $role = Role::where('id', $id)->first();

        $menuStructures = MenuStructure::tree()->get();
        $menuTree = $menuStructures->toTree();

        $menuAccess = MenuAccess::where('role_id', $id)->pluck('menu_id')->toArray();

        // dd($menuStructures);

        return view('setting.menuaccess.edit', compact('role', 'menuTree', 'menuAccess'));
    }

    public function createOrUpdate(Request $request)
    {
        $idRole = $request->u_id;
        $menus = $request->menus;

        DB::beginTransaction();

        try {
            if ($menus != null)
            foreach ($menus as $menu_id) {
                $menuAccess = MenuAccess::where('role_id', $idRole)
                    ->where('menu_id', $menu_id)
                    ->first();

                if (!$menuAccess) {
                    $menuAccess = new MenuAccess();
                    $menuAccess->role_id = $idRole;
                    $menuAccess->menu_id = $menu_id;
                    $menuAccess->save();
                }
            }

            // Kalau gaada menu yang diceklis
            if ($menus == null) {
                // Buat hapus yang tadinya ada menu akses
                MenuAccess::where('role_id', $idRole)
                    ->delete();
            } else {
                // Buat hapus yang tadinya ada menu akses
                MenuAccess::where('role_id', $idRole)
                    ->whereNotIn('menu_id', $menus)
                    ->delete();
            }

            DB::commit();

            toast('Menu access updated successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to update menu access for this role', 'error');
        }

        return redirect()->back();
    }
}
