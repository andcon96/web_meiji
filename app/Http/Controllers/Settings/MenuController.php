<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Menu;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $menus = Menu::orderBy('menu_name')->get();

        return view('setting.menus.index', compact('menus', 'menuMaster'));
    }

    public function create(Request $request)
    {
        return view('setting.menus.create');
    }

    public function edit($id)
    {
        $menu = Menu::where('id', $id)->first();
        return view('setting.menus.edit', compact('menu'));
    }

    public function store(Request $request)
    {
        $menuName = $request->menuName;
        $menuRoute = $request->menuRoute;
        $hasApproval = $request->hasApproval;
        $currentUser = Auth::user()->id;

        // Cek nama menu nya sudah ada atau belum
        $menuExists = Menu::where('menu_name', $menuName)->first();
        if ($menuExists) {
            toast('Menu already exists', 'info');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $menu = new Menu();
            $menu->menu_name = $menuName;
            $menu->menu_route = $menuRoute;
            $menu->has_approval = $hasApproval;
            $menu->created_by = $currentUser;
            $menu->save();

            DB::commit();
            toast('Menu saved successfully', 'success');

            return redirect()->back();
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to save menu', 'error');

            return redirect()->back()->withInput();
        }


        return redirect()->back();
    }

    public function update(Request $request)
    {
        // dd($request->all());
        $id = $request->u_id;
        $menuName = $request->menuName;
        $menuRoute = $request->menuRoute;
        $hasApproval = $request->hasApproval;
        $currentUser = Auth::user()->id;

        // Buat cek kalau nama menu nya udah ada atau belum
        $menuExists = Menu::where('menu_name', $menuName)->where('id', '!=', $id)->first();
        if ($menuExists) {
            toast('Menu already exists', 'info');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $menu = Menu::where('id', $id)->first();
            $menu->menu_name = $menuName;
            $menu->menu_route = $menuRoute;
            $menu->has_approval = $hasApproval;
            if ($menu->isDirty()) {
                $menu->updated_by = $currentUser;
                $menu->save();

                DB::commit();

                toast('Menu updated successfully', 'success');
            } else {
                toast('No changes were made', 'info');
            }
            return redirect()->route('menus.index');

        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update menu', 'error');

            return redirect()->back();
        }
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            Menu::where('id', $id)->delete();

            DB::commit();

            toast('Menu deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete menu', 'error');
        }

        return redirect()->back();
    }
}
