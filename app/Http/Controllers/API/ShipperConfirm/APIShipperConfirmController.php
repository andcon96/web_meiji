<?php

namespace App\Http\Controllers\API\ShipperConfirm;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResources;
use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use Illuminate\Http\Request;

class APIShipperConfirmController extends Controller
{
    public function index(Request $request)
    {
        $data = PackingReplenishmentApproval::query()->with([
            'getPackingReplenishmentMstr.getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster',
            'getCreatedBy:id,name,username'
        ]);

        if ($request->search) {
            $filter = $request->search;
            $data->whereHas('getPackingReplenishmentMstr', function ($q) use ($filter) {
                $q->where('prm_shipper_nbr', 'LIKE', '%' . $filter . '%')
                    ->where('prm_status', 'Shipper Created')
                    ->orderBy('prm_shipper_nbr', 'desc');
            });
        }

        $data = $data->where('pra_status', 'Waiting for confirmation')->paginate(10);


        return GeneralResources::collection($data);
    }
}
