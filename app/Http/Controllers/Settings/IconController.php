<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Icon;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IconController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $icons = Icon::orderBy('icon_name')->get();

        return view('setting.icon.index', compact('icons', 'menuMaster'));
    }

    public function create(Request $request)
    {
        return view('setting.icon.create');
    }

    public function edit($id)
    {
        $icon = Icon::where('id', $id)->first();

        return view('setting.icon.edit', compact('icon'));
    }

    public function store(Request $request)
    {
        $iconName = $request->iconName;
        $iconDesc = $request->iconDesc;
        $iconValue = $request->iconValue;

        // Cek icon nya sudah pernah ada atau belum
        $iconExists = Icon::where('icon_value', $iconValue)->first();
        if ($iconExists) {
            toast('Icon already exists', 'info');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $icon = new Icon();
            $icon->icon_name = $iconName;
            $icon->icon_desc = $iconDesc;
            $icon->icon_value = $iconValue;
            $icon->save();

            DB::commit();
            toast('Icon saved successfully', 'success');

            return redirect()->back();
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to save icon', 'error');

            return redirect()->back();
        }
    }

    public function update(Request $request)
    {
        // dd($request->all());
        $id = $request->u_id;
        $iconName = $request->iconName;
        $iconDesc = $request->iconDesc;
        $iconValue = $request->iconValue;

        // Cek icon selain id ini sudah ada atau belum
        $iconExists = Icon::where('id', '!=', $id)
            ->where('icon_value', $iconValue)->first();

        if ($iconExists) {
            toast('Icon already exists', 'info');

            return redirect()->back();
        }

        DB::beginTransaction();

        try {
            $icon = Icon::where('id', $id)->first();
            $icon->icon_name = $iconName;
            $icon->icon_desc = $iconDesc;
            $icon->icon_value = $iconValue;
            $icon->save();

            DB::commit();
            toast('Icon updated successfully', 'success');

            return redirect()->route('icons.index');
        } catch (\Exception $err) {
            DB::rollBack();
            toast('Failed to update icon', 'error');

            return redirect()->back()->withInput();
        }
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            Icon::where('id', $id)->delete();

            DB::commit();

            toast('Icon deleted successfully', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete icon', 'error');
        }

        return redirect()->back();
    }
}
