<?php

namespace App\Services;

use App\Models\API\PackingReplenishment\PackingReplenishmentHist;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleHist;
use App\Models\API\ShipperConfirm\ShipperConfirm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmShipmentServices
{
    public function confirmShipment(Request $request, $confirmApproval, $reason, $activeConnection)
    {
        $dataArray = [];
        DB::beginTransaction();

        try {
            $packingReplenishmentApproval = ShipperConfirm::where('id', $confirmApproval['id'])->lockForUpdate()->first();

            if (!$packingReplenishmentApproval) {
                DB::rollBack();
                Log::channel('confirmShipment')->info('ShipperConfirm not found for id: ' . $confirmApproval['id']);

                return false;
            }

            if ($packingReplenishmentApproval->sc_status !== 'Waiting for confirmation') {
                DB::rollBack();
                Log::channel('confirmShipment')->info('Duplicate confirmShipment request ignored. ShipperConfirm id: ' . $confirmApproval['id'] . ', current status: ' . $packingReplenishmentApproval->sc_status);

                return true;
            }

            $anotherApproval = ShipperConfirm::where('prm_id', $confirmApproval['prm_id'])->where('id', '!=', $confirmApproval['id'])->where('sc_status', '=', 'Waiting for confirmation')->lockForUpdate()->first();

            $packingReplenishmentApproval->sc_status = 'Approved';
            $packingReplenishmentApproval->updated_by = Auth::user()->id;
            $packingReplenishmentApproval->sc_reason = $reason;
            $packingReplenishmentApproval->save();

            Log::channel('confirmShipment')->info(json_encode($request->all()));

            if (!$anotherApproval) {
                $dataPRM = $confirmApproval['get_packing_replenishment_master'];

                $packingReplenishmentMstr = PackingReplenishmentMstr::with(['getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster'])->find($dataPRM['id']);

                $packingReplenishmentMstr->prm_status = 'Shipped';
                $packingReplenishmentMstr->save();

                $shipmentScheduleMaster = $packingReplenishmentMstr->getPackingReplenishmentDet[0]->getShipmentScheduleLocation->getShipmentScheduleDet->getShipmentScheduleMaster;

                if ($shipmentScheduleMaster) {
                    $shipmentScheduleMaster->ssm_status = 'Shipped';
                    $shipmentScheduleMaster->save();
                }

                foreach ($packingReplenishmentMstr->getPackingReplenishmentDet as $packingReplenishmentDet) {
                    $currentSite = strtoupper($packingReplenishmentDet->getShipmentScheduleLocation->ssl_site);
                    $currentItem = strtoupper($packingReplenishmentDet->getShipmentScheduleLocation->getShipmentScheduleDet->ssd_sod_part);
                    $currentLot = strtoupper($packingReplenishmentDet->getShipmentScheduleLocation->ssl_lotserial);
                    $picked = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_qty_pick;

                    $key = $currentSite . '|' . $currentItem . '|' . $currentLot;

                    if (!isset($dataArray[$key])) {
                        $dataArray[$key] = [
                            'site' => $currentSite,
                            'item' => $currentItem,
                            'lot' => $currentLot,
                            'pick' => 0,
                        ];
                    }

                    $dataArray[$key]['pick'] += $picked;

                    $packingReplenishmentHist = new PackingReplenishmentHist();
                    $packingReplenishmentHist->prh_shipper_nbr = $packingReplenishmentMstr->prm_shipper_nbr;
                    $packingReplenishmentHist->prh_so_nbr = $packingReplenishmentDet->getShipmentScheduleLocation->getShipmentScheduleDet->ssd_sod_nbr;
                    $packingReplenishmentHist->prh_so_line = $packingReplenishmentDet->getShipmentScheduleLocation->getShipmentScheduleDet->ssd_sod_line;
                    $packingReplenishmentHist->prh_site = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_site;
                    $packingReplenishmentHist->prh_warehouse = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_warehouse;
                    $packingReplenishmentHist->prh_location = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_location;
                    $packingReplenishmentHist->prh_lotserial = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_lotserial;
                    $packingReplenishmentHist->prh_level = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_level;
                    $packingReplenishmentHist->prh_bin = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_bin;
                    $packingReplenishmentHist->prh_qty_pick = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_qty_pick;
                    $packingReplenishmentHist->prh_status_qad = $packingReplenishmentDet->prd_status_qad;
                    $packingReplenishmentHist->prh_status = $packingReplenishmentMstr->prm_status;
                    $packingReplenishmentHist->prh_action = 'Confirm Shipment';
                    $packingReplenishmentHist->created_by = Auth::user()->name;
                    $packingReplenishmentHist->save();

                    $dataShipmentScheduleDet = $packingReplenishmentDet->getShipmentScheduleLocation->getShipmentScheduleDet;
                    if ($dataShipmentScheduleDet->ssd_sod_qty_pick < $dataShipmentScheduleDet->ssd_sod_qty_ord) {
                        $dataShipmentScheduleDet->ssd_status = 'Shipped (Partial)';
                    } else {
                        $dataShipmentScheduleDet->ssd_status = 'Shipped (Full)';
                    }

                    $dataShipmentScheduleDet->updated_by = Auth::user()->id;
                    $dataShipmentScheduleDet->save();
                }

                $dataArray = array_values($dataArray);

                Log::info('CALL confirmShipment', [
                    'prm_id' => $confirmApproval['prm_id'],
                    'time' => now(),
                ]);

                $qxtendServices = new QxtendServices();
                $qxtend = $qxtendServices->qxShipperConfirm($confirmApproval, $activeConnection);
if ($qxtend[0] == false) {
    DB::rollBack();

    Log::channel('confirmShipment')->info($qxtend[1]);

    return [
        'success' => false,
        'message' => $qxtend[1],
    ];
}

                Log::channel('confirmShipment')->info(
                    json_encode([
                        'confirmApproval' => $confirmApproval,
                        'reason' => $reason,
                    ]),
                );
            }

            DB::commit();

            return true;
        } catch (\Throwable $err) {
            DB::rollBack();
            Log::channel('confirmShipment')->info($err);

            return false;
        }
    }
}
