<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\OtherShipmentPreparationPrefix;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OtherShipmentPreparationPrefixController extends Controller
{
    public function index(Request $request)
    {
        $serverURL = new ServerURL();
        $menuMaster = $serverURL->currentURL($request);
        $prefixes = OtherShipmentPreparationPrefix::get();

        return view("setting.otherShipmentPreparationPrefix.index", compact("menuMaster", "prefixes"));
    }

    public function create()
    {
        return view("setting.otherShipmentPreparationPrefix.create");
    }

    public function store(Request $request)
    {
        $otherShipmentPreparationPrefix = $request->otherShipmentPreparationPrefix;
        $runningNbrOtherPreparationSchedule = $request->runningNbrOtherShipmentPreparation;

        DB::beginTransaction();

        try {
            $shipmentSchedule = new OtherShipmentPreparationPrefix();
            $shipmentSchedule->other_shipment_preparation_prefix = $otherShipmentPreparationPrefix;
            $shipmentSchedule->other_shipment_preparation_running_nbr = $runningNbrOtherPreparationSchedule;
            $shipmentSchedule->created_by = Auth::user()->id;
            $shipmentSchedule->save();

            DB::commit();

            toast("Successfully created other shipment preparation prefix", "success");
        } catch (\Exception $err) {
            DB::rollBack();
            dd($err);

            toast("Failed to create other shipment preparation prefix", "error");
        }

        return redirect()->back()->withInput();
    }

    public function delete(Request $request)
    {
        $id = $request->d_id;

        DB::beginTransaction();

        try {
            OtherShipmentPreparationPrefix::find($id)->delete();

            DB::commit();

            toast("Successfully delete other shipment preparation prefix", "success");
        } catch (\Exception $err) {
            DB::rollBack();

            toast("Failed to delete other shipment preparation prefix", "error");
        }

        return redirect()->back();
    }

    public function edit($id)
    {
        $prefix = OtherShipmentPreparationPrefix::find($id);

        return view("setting.otherShipmentPreparationPrefix.edit", compact("prefix"));
    }

    public function update(Request $request, $id)
    {
        $id = $request->u_id;
        $otherShipmentPreparationPrefix = $request->otherShipmentPreparationPrefix;
        $runningNbrOtherShipmentPreparation = $request->runningNbrOtherShipmentPreparation;

        DB::beginTransaction();

        try {
            $prefix = OtherShipmentPreparationPrefix::find($id);
            $prefix->other_shipment_preparation_prefix = $otherShipmentPreparationPrefix;
            $prefix->other_shipment_preparation_running_nbr = $runningNbrOtherShipmentPreparation;
            $prefix->updated_by = Auth::user()->id;
            $prefix->save();

            DB::commit();

            toast("Successfully update other shipment preparation prefix", "success");
        } catch (\Exception $err) {
            DB::rollBack();

            toast("Failed to update other shipment preparation prefix", "error");
        }

        return redirect()->back();
    }
}
