<?php

namespace App\Http\Controllers;

use App\Exports\SSDExport;
use App\Http\Controllers\Controller;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class ShipmentReportController extends Controller
{
    public function index(Request $req)
    {
        $menuMaster = (new ServerURL())->currentURL($req);
        return view('shipmentScheduleReport.index', compact('menuMaster'));
    }

    /* public function getAllSSD()
    {
        $rows = ShipmentScheduleDet::with(['getShipmentScheduleMaster'])->get()
        ->map(function($row){
            $row->setAttribute('sold_to', $row->getShipmentScheduleMaster?->ssm_cust_code);

            return $row->only([
                'id',
                'ssd_sod_nbr',
                'sold_to',
                'ssd_sod_part',
                'ssd_uom',
                'ssd_sod_qty_ord',
                'ssd_sod_lot',
            ]);
        });

        return DataTables::of($rows)->make(true);
    } */

    public function getAllSSM()
    {
        $rows = ShipmentScheduleMstr::with(['getShipmentScheduleDetail'])
        ->where('ssm_status', 'Scheduled')
        ->get()
        ->map(function($row){
            $row->getShipmentScheduleDetail->map(function($det) use ($row){
                $det->setAttribute('sold_to', $row->ssm_cust_code);
                return $det;
            });

            return $row;
        });

        return DataTables::of($rows)->make(true);
    }

    public function SSDExport(Request $req){
        return Excel::download(new SSDExport(json_decode($req->ssdrows)), $req->nbr_mstr.'.xlsx');
    }
}