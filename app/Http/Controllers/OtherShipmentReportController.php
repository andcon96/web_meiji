<?php

namespace App\Http\Controllers;

use App\Exports\OSSDExport;
use App\Http\Controllers\Controller;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleMstr;
use App\Services\ServerURL;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class OtherShipmentReportController extends Controller
{
    public function index(Request $req)
    {
        $menuMaster = (new ServerURL())->currentURL($req);
        return view('otherShipmentScheduleReport.index', compact('menuMaster'));
    }

    public function getAllOSSM()
    {
        $rows = OtherShipmentScheduleMstr::with(['getOtherShipmentScheduleDetail'])
        ->where('ossm_status', 'Scheduled')
        ->get()
        ->map(function($row){
            $row->getOtherShipmentScheduleDetail->map(function($det) use ($row){
                $det->setAttribute('sold_to', $row->ossm_cust_code);
                $det->setAttribute('order', $row->ossm_number);
                $det->getOtherShipmentScheduleLocation;
                return $det;
            });

            return $row;
        });

        return DataTables::of($rows)->make(true);
    }

    public function OSSDExport(Request $req){

        return Excel::download(new OSSDExport(json_decode($req->ossdrows)), $req->nbr_mstr.'.xlsx');

    }
}