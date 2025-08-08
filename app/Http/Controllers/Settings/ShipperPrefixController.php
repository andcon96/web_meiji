<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\ShipperPrefix;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShipperPrefixController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $prefixes = ShipperPrefix::get();

        return view('setting.shipperPrefix.index', compact('menuMaster', 'prefixes'));
    }

    public function create()
    {
        return view('setting.shipperPrefix.create');
    }

    public function store(Request $request)
    {
        $shipperPrefix = $request->shipperPrefix;
        $runningNbrShipper = $request->runningNbrShipmentSchedule;

        DB::beginTransaction();

        try {
            $shipper = new ShipperPrefix();
            $shipper->shipper_year = date('y');
            $shipper->shipper_month = date('m');
            $shipper->shipper_prefix = $shipperPrefix;
            $shipper->shipper_number = $runningNbrShipper;
            $shipper->created_by = Auth::user()->id;
            $shipper->save();

            DB::commit();

            toast('Successfully created shipper prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to create shiper prefix', 'error');
        }

        return redirect()->back()->withInput();
    }

    public function edit($id)
    {
        $prefix = ShipperPrefix::find($id);

        return view('setting.shipperPrefix.edit', compact('prefix'));
    }

    public function update(Request $request)
    {
        $id = $request->u_id;
        $shipperPrefix = $request->shipperPrefix;
        $shipperNumber = $request->shipperNumber;
        $prefix = ShipperPrefix::find($id);
        DB::beginTransaction();

        try {
            $prefix->shipper_prefix = $shipperPrefix;
            $prefix->shipper_number = $shipperNumber;
            $prefix->updated_by = Auth::user()->id;
            $prefix->save();

            DB::commit();

            toast('Successfully update shipper prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update shipper prefix', 'error');
        }

        return redirect()->back();
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            ShipperPrefix::find($id)->delete();

            DB::commit();

            toast('Successfully delete shiper prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete shiper prefix', 'error');
        }

        return redirect()->back();
    }
}
