<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Icon;
use App\Models\Settings\Menu;
use App\Models\Settings\MenuStructure;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MenuStructureController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $menusStructure = MenuStructure::with(['getMenu', 'getMenuParent', 'getIcon'])
            ->orderBy('menu_sequence')
            ->get();

        return view('setting.menustructure.index', compact('menusStructure', 'menuMaster'));
    }

    public function create(Request $request)
    {
        $menus = Menu::orderBy('menu_name')->get();
        $menuParents = Menu::whereNull('menu_route')->get();
        $icons = Icon::orderBy('icon_name')->get();

        return view('setting.menustructure.create', compact('menus', 'menuParents', 'icons'));
    }

    public function edit($id)
    {
        $menuStructure = MenuStructure::where('id', $id)->first();
        $menus = Menu::orderBy('menu_name')->get();
        $icons = Icon::orderBy('icon_name')->get();

        return view('setting.menustructure.edit', compact('menuStructure', 'menus', 'icons'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $iconID = $request->icon_id;
        $menuID = $request->menu_id;
        $menuParentID = $request->menu_parent_id;
        // $menuSequence = $request->menuSequence;
        $currentUser = Auth::user()->id;
        
        // cek kombinasi menu nya sudah ada atau belum
        $menuStructureExists = MenuStructure::where('menu_id', $menuID)
            ->where('menu_parent_id', $menuParentID)
            ->first();

        if ($menuStructureExists) {
            toast('Menu structure already exists', 'info');

            return redirect()->back()->withInput();
        }

        // Cek menu sequence terakhir
        $lastMenuSequence = MenuStructure::orderBy('menu_sequence', 'desc')->first();
        
        if (!$lastMenuSequence) {
            $menuSequence = 1;
        } else {
            $menuSequence = $lastMenuSequence->menu_sequence + 1;
        }


        DB::beginTransaction();

        try {
            $menuStructure = new MenuStructure();
            $menuStructure->menu_id = $menuID;
            $menuStructure->menu_icon_id  = $iconID;
            $menuStructure->menu_parent_id = $menuParentID;
            $menuStructure->menu_sequence = $menuSequence;
            $menuStructure->created_by = $currentUser;

            $menuStructure->save();

            DB::commit();
            toast('Menu structure saved successfully', 'success');

            return redirect()->back();

        } catch (\Exception $err) {
            DB::rollBack();
            // dd($err);
            toast('Failed to save menu structure', 'error');

            return redirect()->back()->withInput();
        }
        return redirect()->back();
    }

    public function update(Request $request)
    {
        // dd($request->all());
        $id = $request->u_id;
        $icon_id = $request->icon_id;
        $menu_id = $request->menu_id;
        $menu_parent_id = $request->menu_parent_id;
        $currentUser = Auth::user()->id;

        // Untuk cek menu nya sudah ada atau belum
        $menuStructureExists = MenuStructure::where('menu_id', $menu_id)->where('menu_parent_id', $menu_parent_id)
            ->where('id', '!=', $id)->first();

        if ($menuStructureExists) {
            toast('Menu structure already exists', 'info');
            
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $menuStructure = MenuStructure::where('id', $id)->first();
            $menuStructure->menu_id = $menu_id;
            $menuStructure->menu_icon_id = $icon_id;
            $menuStructure->menu_parent_id = $menu_parent_id;
            if ($menuStructure->isDirty()) {
                $menuStructure->updated_by = $currentUser;
                $menuStructure->save();

                DB::commit();
                toast('Menu structure updated successfully', 'success');
            } else {
                toast('No changes were made', 'info');
            }

            return redirect()->route('menuStructure.index');
        } catch (\Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to update menu structure', 'error');

            return redirect()->back()->withInput();
        }
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            MenuStructure::where('id', $id)->delete();

            DB::commit();

            toast('Menu structure deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete menu structure', 'error');
        }

        return redirect()->back();
    }

    public function moveUp(Request $request)
    {
        $id = $request->upMenuID;
        $currentUser = Auth::user()->id;

        DB::beginTransaction();

        try {
            $currentMenu = MenuStructure::where('id', $id)->first();
            $increment = 1;
            do {
                $prevMenu = MenuStructure::where('menu_sequence', $currentMenu->menu_sequence - $increment)->first();
                $increment++;
            } while (!$prevMenu);

            $prevSequence = $currentMenu->menu_sequence - 1;
            $currentMenu->menu_sequence = $prevSequence;
            $currentMenu->updated_by = $currentUser;
            $currentMenu->save();

            $prevMenu->menu_sequence = $prevSequence + 1;
            $prevMenu->updated_by = $currentUser;
            $prevMenu->save();

            DB::commit();
            toast('Menu structure moved successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();
            toast('Failed to move menu structure', 'error');
        }

        return redirect()->back();
    }

    public function moveDown(Request $request)
    {
        $id = $request->downMenuID;
        $currentUser = Auth::user()->id;

        DB::beginTransaction();

        try {
            $currentMenu = MenuStructure::where('id', $id)->first();
            $increment = 1;
            do {
                $prevMenu = MenuStructure::where('menu_sequence', $currentMenu->menu_sequence + $increment)->first();
                $increment++;
            } while (!$prevMenu);

            $prevSequence = $currentMenu->menu_sequence + 1;
            $currentMenu->menu_sequence = $prevSequence;
            $currentMenu->updated_by = $currentUser;
            $currentMenu->save();

            $prevMenu->menu_sequence = $prevSequence - 1;
            $prevMenu->updated_by = $currentUser;
            $prevMenu->save();

            DB::commit();
            toast('Menu structure moved successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();
            toast('Failed to move menu structure', 'error');
        }

        return redirect()->back();
    }
}
