<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\PicklistPrefix;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PicklistPrefixController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $prefixes = PicklistPrefix::get();

        return view('setting.PicklistPrefix.index', compact('menuMaster', 'prefixes'));
    }

    public function create()
    {
        return view('setting.PicklistPrefix.create');
    }

    public function store(Request $request)
    {
        
        $PicklistPrefix = $request->PicklistPrefix;
        $PicklistNumber = $request->PicklistNumber;
        $yearPrefix = $request->YearPrefix;
        $monthPrefix = $request->MonthPrefix;
        $dayPrefix = $request->DayPrefix ?? '';

        DB::beginTransaction();

        try {
            $Picklist = new PicklistPrefix();
            $Picklist->prefix_wo = $PicklistPrefix;
            $Picklist->prefix_year_wo = $yearPrefix;
            $Picklist->prefix_month_wo = $monthPrefix;
            $Picklist->prefix_day_wo = $dayPrefix;
            $Picklist->running_nbr_wo = $PicklistNumber;
            
            $Picklist->save();

            DB::commit();

            toast('Successfully created Picklist prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();
            dd($err);
            toast('Failed to create shiper prefix', 'error');
        }

        return redirect()->back()->withInput();
    }

    public function edit($id)
    {
        $prefix = PicklistPrefix::find($id);

        return view('setting.PicklistPrefix.edit', compact('prefix'));
    }

    public function update(Request $request)
    {
        $id = $request->u_id;
        $PicklistPrefix = $request->PicklistPrefix;
        $PicklistNumber = $request->PicklistNumber;
        $yearPrefix = $request->YearPrefix;
        $monthPrefix = $request->MonthPrefix;
        $dayPrefix = $request->DayPrefix ?? '';
        $prefix = PicklistPrefix::find($id);
        DB::beginTransaction();

        try {
            $prefix->prefix_wo = $PicklistPrefix;
            $prefix->prefix_year_wo = $yearPrefix;
            $prefix->prefix_month_wo = $monthPrefix;
            $prefix->prefix_day_wo = $dayPrefix;
            $prefix->running_nbr_wo = $PicklistNumber;
            
            $prefix->save();

            DB::commit();

            toast('Successfully update Picklist prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update Picklist prefix', 'error');
        }

        return redirect()->back();
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            PicklistPrefix::find($id)->delete();

            DB::commit();

            toast('Successfully delete shiper prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete shiper prefix', 'error');
        }

        return redirect()->back();
    }
}
