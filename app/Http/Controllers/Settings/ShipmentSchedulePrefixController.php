<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\ShipmentSchedulePrefix;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShipmentSchedulePrefixController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $prefixes = ShipmentSchedulePrefix::get();

        return view('setting.shipmentSchedulePrefix.index', compact('menuMaster', 'prefixes'));
    }

    public function create()
    {
        return view('setting.shipmentSchedulePrefix.create');
    }

    public function store(Request $request)
    {
        $shipmentSchedulePrefix = $request->shipmentSchedulePrefix;
        $runningNbrShipmentSchedule = $request->runningNbrShipmentSchedule;

        DB::beginTransaction();

        try {
            $shipmentSchedule = new ShipmentSchedulePrefix();
            $shipmentSchedule->ship_schedule_prefix = $shipmentSchedulePrefix;
            $shipmentSchedule->ship_schedule_running_nbr = $runningNbrShipmentSchedule;
            $shipmentSchedule->created_by = Auth::user()->id;
            $shipmentSchedule->save();

            DB::commit();

            toast('Successfully created shipment schedule prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to create shipment schedule prefix', 'error');
        }

        return redirect()->back()->withInput();
    }

    public function edit($id)
    {
        $prefix = ShipmentSchedulePrefix::find($id);

        return view('setting.shipmentSchedulePrefix.edit', compact('prefix'));
    }

    public function update(Request $request)
    {
        $id = $request->u_id;
        $shipmentSchedulePrefix = $request->shipmentSchedulePrefix;
        $runningNbrShipmentSchedule = $request->runningNbrShipmentSchedule;

        DB::beginTransaction();

        try {
            $prefix = ShipmentSchedulePrefix::find($id);
            $prefix->ship_schedule_prefix = $shipmentSchedulePrefix;
            $prefix->ship_schedule_running_nbr = $runningNbrShipmentSchedule;
            $prefix->updated_by = Auth::user()->id;
            $prefix->save();

            DB::commit();

            toast('Successfully update shipment schedule prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update shipment schedule prefix', 'error');
        }

        return redirect()->back();
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            ShipmentSchedulePrefix::find($id)->delete();

            DB::commit();

            toast('Successfully delete shipment schedule prefix', 'success');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to delete shipment schedule prefix', 'error');
        }

        return redirect()->back();
    }
}
