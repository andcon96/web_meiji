<?php

namespace App\Services;

use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use App\Models\API\PackingReplenishment\PackingReplenishmentApprovalHist;
use App\Models\API\PackingReplenishment\PackingReplenishmentHist;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleHist;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ConfirmShipmentServices
{
    public function confirmShipment($confirmApproval, $reason, $activeConnection)
    {
        $dataArray = [];
        DB::beginTransaction();

        try {
            // Cek approval, masih perlu approval atau tidak

            // Qxtend ke QAD Pre-shipper/shipper confirm
            $qxtend = (new QxtendServices())->qxShipperConfirm($confirmApproval, $activeConnection);
            if ($qxtend[0] == false) {
                DB::commit();

                Log::channel('confirmShipment')->info($qxtend[1]);

                return false;
            }

            // Update status + catat ke history
            $packingReplenishmentApproval = PackingReplenishmentApproval::find($confirmApproval['id']);
            $packingReplenishmentApproval->pra_status = 'Approved';
            $packingReplenishmentApproval->updated_by = Auth::user()->id;
            $packingReplenishmentApproval->pra_reason = $reason;
            $packingReplenishmentApproval->save();

            $packingReplenishmentApprovalHist = new PackingReplenishmentApprovalHist();
            $packingReplenishmentApprovalHist->prah_shipper_number = $confirmApproval['get_packing_replenishment_mstr']['prm_shipper_nbr'];
            $packingReplenishmentApprovalHist->prah_sequence = $packingReplenishmentApproval->pra_sequence;
            $packingReplenishmentApprovalHist->prah_user_approver = $packingReplenishmentApproval->pra_user_approver;
            $packingReplenishmentApprovalHist->prah_alt_user_approver = $packingReplenishmentApproval->pra_alt_user_approver;
            $packingReplenishmentApprovalHist->prah_status = $packingReplenishmentApproval->pra_status;
            $packingReplenishmentApprovalHist->prah_reason = $packingReplenishmentApproval->prah_reason;
            $packingReplenishmentApprovalHist->created_by = Auth::user()->name;
            $packingReplenishmentApprovalHist->save();

            // Update packing replenishment jadi shipped + catat ke history
            $dataPRM = $confirmApproval['get_packing_replenishment_mstr'];

            $packingReplenishmentMstr = PackingReplenishmentMstr::with(['getPackingReplenishmentDet.getShipmentScheduleLocation.getShipmentScheduleDet.getShipmentScheduleMaster'])->find($dataPRM['id']);
            $packingReplenishmentMstr->prm_status = 'Shipped';
            $packingReplenishmentMstr->save();

            $packingReplenishmentMstr->getPackingReplenishmentDet[0]->getShipmentScheduleLocation->getShipmentScheduleDet->getShipmentScheduleMaster->ssm_status = 'Shipped';
            $packingReplenishmentMstr->getPackingReplenishmentDet[0]->getShipmentScheduleLocation->getShipmentScheduleDet->getShipmentScheduleMaster->save();

            foreach ($packingReplenishmentMstr->getPackingReplenishmentDet as $packingReplenishmentDet) {
                $currentSite = strtoupper($packingReplenishmentDet->getShipmentScheduleLocation->ssl_site);
                $currentItem = strtoupper($packingReplenishmentDet->getShipmentScheduleLocation->getShipmentScheduleDet->ssd_sod_part);
                $currentLot = strtoupper($packingReplenishmentDet->getShipmentScheduleLocation->ssl_lotserial);
                $picked = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_qty_pick;

                // use item+lot as a key
                $key = $currentSite . '|' . $currentItem . '|' . $currentLot;

                if (!isset($dataArray[$key])) {
                    $dataArray[$key] = [
                        'site' => $currentSite,
                        'item' => $currentItem,
                        'lot'  => $currentLot,
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

                // Bandingkan Qty to pick sama order qty
                // kalau sama ganti status shipment schedule jadi Shipped (Full) + catat ke history
                // Kalau kurang dari order qty ganti status shipment schedule jadi Shipped (Partial) + catat ke history
                $dataShipmentScheduleDet = $packingReplenishmentDet->getShipmentScheduleLocation->getShipmentScheduleDet;
                if ($dataShipmentScheduleDet->ssd_sod_qty_pick < $dataShipmentScheduleDet->ssd_sod_qty_ord) {
                    $dataShipmentScheduleDet->ssd_status = 'Shipped (Partial)';
                } else {
                    $dataShipmentScheduleDet->ssd_status = 'Shipped (Full)';
                }

                $dataShipmentScheduleDet->updated_by = Auth::user()->id;
                $dataShipmentScheduleDet->save();

                $shipmentScheduleHistory = new ShipmentScheduleHist();
                $shipmentScheduleHistory->ssh_number = $dataShipmentScheduleDet->getShipmentScheduleMaster->ssm_number;
                $shipmentScheduleHistory->ssh_cust_code = $dataShipmentScheduleDet->getShipmentScheduleMaster->ssm_cust_code;
                $shipmentScheduleHistory->ssh_cust_desc = $dataShipmentScheduleDet->getShipmentScheduleMaster->ssm_cust_desc;
                $shipmentScheduleHistory->ssh_status_mstr = $dataShipmentScheduleDet->getShipmentScheduleMaster->ssm_status;
                $shipmentScheduleHistory->ssh_sod_nbr = $dataShipmentScheduleDet->ssd_sod_nbr;
                $shipmentScheduleHistory->ssh_sod_site = $dataShipmentScheduleDet->ssd_sod_site;
                $shipmentScheduleHistory->ssh_sod_shipto = $dataShipmentScheduleDet->ssd_sod_shipto;
                $shipmentScheduleHistory->ssh_sod_line = $dataShipmentScheduleDet->ssd_sod_line;
                $shipmentScheduleHistory->ssh_sod_part = $dataShipmentScheduleDet->ssd_sod_part;
                $shipmentScheduleHistory->ssh_sod_desc = $dataShipmentScheduleDet->ssd_sod_desc;
                $shipmentScheduleHistory->ssh_sod_qty_ord = $dataShipmentScheduleDet->ssd_sod_qty_ord;
                $shipmentScheduleHistory->ssh_status_det = $dataShipmentScheduleDet->ssd_status;
                $shipmentScheduleHistory->ssh_site = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_site;
                $shipmentScheduleHistory->ssh_warehouse = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_warehouse;
                $shipmentScheduleHistory->ssh_location = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_location;
                $shipmentScheduleHistory->ssh_lotserial = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_lotserial;
                $shipmentScheduleHistory->ssh_level = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_level;
                $shipmentScheduleHistory->ssh_bin = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_bin;
                $shipmentScheduleHistory->ssh_qty_to_pick = $packingReplenishmentDet->getShipmentScheduleLocation->ssl_qty_to_pick;
                $shipmentScheduleHistory->ssh_action = 'Confirm shipment';
                $shipmentScheduleHistory->created_by = Auth::user()->name;
                $shipmentScheduleHistory->save();
            }

            $dataArray = array_values($dataArray);

            foreach ($dataArray as $data) {
                // Tembak qty oh di xxinv_det
                (new WSAServices())->wsaUpdateQtyOHCustom($data);
            }

            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();

            Log::channel('confirmShipment')->info($err);

            return false;
        }
    }
}
