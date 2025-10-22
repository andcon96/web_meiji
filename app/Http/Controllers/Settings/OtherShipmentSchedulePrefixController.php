<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ServerURL;
use App\Models\Settings\OtherShipmentSchedulePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OtherShipmentSchedulePrefixController extends Controller
{
    public function index(Request $request)
    {
        $serverURL = new ServerURL();
        $menuMaster = $serverURL->currentURL($request);
        $prefixes = OtherShipmentSchedulePrefix::get();

        return view("setting.otherShipmentSchedulePrefix.index", compact("menuMaster", "prefixes"));
    }

    public function create()
    {
        return view("setting.otherShipmentSchedulePrefix.create");
    }

    public function store(Request $request)
    {
        $otherShipmentSchedulePrefix = $request->otherShipmentSchedulePrefix;
        $runningNbrOtherShipmentSchedule = $request->runningNbrOtherShipmentSchedule;

        DB::beginTransaction();

        try {
            $shipmentSchedule = new OtherShipmentSchedulePrefix();
            $shipmentSchedule->other_ship_schedule_prefix = $otherShipmentSchedulePrefix;
            $shipmentSchedule->other_ship_schedule_running_nbr = $runningNbrOtherShipmentSchedule;
            $shipmentSchedule->created_by = Auth::user()->id;
            $shipmentSchedule->save();

            DB::commit();

            toast("Successfully created other shipment schedule prefix", "success");
        } catch (\Exception $err) {
            DB::rollBack();
            dd($err);

            toast("Failed to create other shipment schedule prefix", "error");
        }

        return redirect()->back()->withInput();
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            OtherShipmentSchedulePrefix::find($id)->delete();

            DB::commit();

            toast("Successfully delete other shipment schedule prefix", "success");
        } catch (\Exception $err) {
            DB::rollBack();

            toast("Failed to delete other shipment schedule prefix", "error");
        }

        return redirect()->back();
    }

    public function edit($id)
    {
        $prefix = OtherShipmentSchedulePrefix::find($id);

        return view("setting.otherShipmentSchedulePrefix.edit", compact("prefix"));
    }

    public function update(Request $request, $id)
    {
        $id = $request->u_id;
        $otherShipmentSchedulePrefix = $request->otherShipmentSchedulePrefix;
        $runningNbrOtherShipmentSchedule = $request->runningNbrOtherShipmentSchedule;

        DB::beginTransaction();

        try {
            $prefix = OtherShipmentSchedulePrefix::find($id);
            $prefix->other_ship_schedule_prefix = $otherShipmentSchedulePrefix;
            $prefix->other_ship_schedule_running_nbr = $runningNbrOtherShipmentSchedule;
            $prefix->updated_by = Auth::user()->id;
            $prefix->save();

            DB::commit();

            toast("Successfully update other shipment schedule prefix", "success");
        } catch (\Exception $err) {
            DB::rollBack();

            toast("Failed to update other shipment schedule prefix", "error");
        }

        return redirect()->back();
    }
}
