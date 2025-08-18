<?php

namespace App\Services;

use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use App\Models\API\PackingReplenishment\PackingReplenishmentDet;
use App\Models\API\PackingReplenishment\PackingReplenishmentHist;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleHist;
use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PackingReplenishmentServices
{
    public function savePackingReplenishment($packingReplenishments, $activeConnection)
    {
        DB::beginTransaction();

        try {
            // Buat Packing Replenishment Mstr + Det
            $packingReplenishmentMstr = new PackingReplenishmentMstr();
            $packingReplenishmentMstr->created_by = Auth::user()->id;
            $packingReplenishmentMstr->prm_status = 'Draft';
            $packingReplenishmentMstr->save();

            $totalMatch = 0;
            $totalData = count($packingReplenishments);

            foreach ($packingReplenishments as $key => $packingReplenishment) {
                // Update total picked qty
                $shipmentScheduleDet = ShipmentScheduleDet::with(['getShipmentScheduleMaster'])->where('ssd_sent_to_qad', 'No')->find($packingReplenishment['id']);

                if ($shipmentScheduleDet) {
                    // Ambil shipment schedule master ID
                    if ($key == 0) {
                        $idShipmentScheduleMstr = $shipmentScheduleDet->getShipmentScheduleMaster->id;
                    }

                    $shipmentScheduleDet->ssd_sod_qty_pick += $packingReplenishment['totalPickedQty'];
                    foreach ($packingReplenishment['locations'] as $locationDetail) {
                        // Qxtend Transfer single item
                        $qxtend = (new QxtendServices())->qxTransferSingleItemPackingReplenishment($packingReplenishment, $locationDetail, $activeConnection);

                        if ($qxtend[0] == false) {
                            DB::commit();

                            Log::channel('packingReplenishment')->info($qxtend[1]);

                            return false;
                        }
                    }

                    $shipmentScheduleDet->ssd_sent_to_qad = 'Yes';
                    $shipmentScheduleDet->save();

                    if ($shipmentScheduleDet->ssd_sod_qty_ord == $shipmentScheduleDet->ssd_sod_qty_pick) {
                        $totalMatch += 1;
                    }

                    // Update shipment schedule location
                    $shipmentScheduleLocation = ShipmentScheduleLoc::where('id', $locationDetail['id'])->first();
                    $shipmentScheduleLocation->ssl_qty_pick = $locationDetail['qtyPick'];
                    $shipmentScheduleLocation->updated_by = Auth::user()->id;
                    $shipmentScheduleLocation->save();


                    // Insert ke history
                    $shipmentScheduleHistory = new ShipmentScheduleHist();
                    $shipmentScheduleHistory->ssh_number = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_number;
                    $shipmentScheduleHistory->ssh_cust_code = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_cust_code;
                    $shipmentScheduleHistory->ssh_cust_desc = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_cust_desc;
                    $shipmentScheduleHistory->ssh_status_mstr = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_status;
                    $shipmentScheduleHistory->ssh_sod_nbr = $shipmentScheduleDet->ssd_sod_nbr;
                    $shipmentScheduleHistory->ssh_sod_line = $shipmentScheduleDet->ssd_sod_line;
                    $shipmentScheduleHistory->ssh_sod_part = $shipmentScheduleDet->ssd_sod_part;
                    $shipmentScheduleHistory->ssh_sod_desc = $shipmentScheduleDet->ssd_sod_desc;
                    $shipmentScheduleHistory->ssh_sod_qty_ord = $shipmentScheduleDet->ssd_sod_qty_ord;
                    $shipmentScheduleHistory->ssh_status_det = $shipmentScheduleDet->ssd_status;
                    $shipmentScheduleHistory->ssh_site = $shipmentScheduleLocation->ssl_site;
                    $shipmentScheduleHistory->ssh_warehouse = $shipmentScheduleLocation->ssl_warehouse;
                    $shipmentScheduleHistory->ssh_location = $shipmentScheduleLocation->ssl_location;
                    $shipmentScheduleHistory->ssh_lotserial = $shipmentScheduleLocation->ssl_lotserial;
                    $shipmentScheduleHistory->ssh_level = $shipmentScheduleLocation->ssl_level;
                    $shipmentScheduleHistory->ssh_bin = $shipmentScheduleLocation->ssl_bin;
                    $shipmentScheduleHistory->ssh_qty_to_pick = $shipmentScheduleLocation->ssl_qty_to_pick;
                    $shipmentScheduleHistory->ssh_action = 'Create';
                    $shipmentScheduleHistory->created_by = Auth::user()->id;
                    $shipmentScheduleHistory->save();
                }
            }

            // Qxtend buat sales order shipper maintenance
            $qxtend = (new QxtendServices())->qxSalesOrderShipper('create', $packingReplenishments, $packingReplenishmentMstr->id, $activeConnection);

            if ($qxtend[0] == false) {
                DB::commit();

                Log::channel('packingReplenishment')->info($qxtend[1]);

                return false;
            }

            // Ambil nomor shipper, update ke packing replenishment master buat nomor shipper nya
            $getShipperNumber = (new WSAServices())->wsaGetShipperNumber($packingReplenishments[0]['sodSite'], $packingReplenishmentMstr->id, $activeConnection);

            if ($getShipperNumber[0] == 'false') {
                Log::channel('packingReplenishment')->info('Gagal mengambil data untuk packing replenishment: ' . $packingReplenishmentMstr->id);
            }

            $shipperNumber = substr($getShipperNumber[1][0]->t_shipper_nbr, 1);
            $packingReplenishmentMstr->prm_shipper_nbr = $shipperNumber;
            $packingReplenishmentMstr->prm_status = 'Shipper Created';
            $packingReplenishmentMstr->save();

            // Buat packing replenishment detail + Buat packing replenishment history
            foreach ($packingReplenishments as $packingReplenishment) {
                foreach ($packingReplenishment['locations'] as $locationDetail) {
                    // Buat packing replenishment detail
                    $packingReplenishmentDet = new PackingReplenishmentDet();
                    $packingReplenishmentDet->prm_id = $packingReplenishmentMstr->id;
                    $packingReplenishmentDet->ssl_id = $locationDetail['id'];
                    $packingReplenishmentDet->prd_status_qad = 'Yes';
                    $packingReplenishmentDet->prd_created_by = Auth::user()->id;
                    $packingReplenishmentDet->save();

                    $packingReplenishmentHist = new PackingReplenishmentHist();
                    $packingReplenishmentHist->prh_shipper_nbr = $shipperNumber;
                    $packingReplenishmentHist->prh_so_nbr = $packingReplenishment['sodNbr'];
                    $packingReplenishmentHist->prh_so_line = $packingReplenishment['sodLine'];
                    $packingReplenishmentHist->prh_site = $locationDetail['site'];
                    $packingReplenishmentHist->prh_warehouse = $locationDetail['wh'];
                    $packingReplenishmentHist->prh_location = $locationDetail['loc'];
                    $packingReplenishmentHist->prh_lotserial = $locationDetail['lot'];
                    $packingReplenishmentHist->prh_level = $locationDetail['level'];
                    $packingReplenishmentHist->prh_bin = $locationDetail['bin'];
                    $packingReplenishmentHist->prh_qty_pick = $locationDetail['qtyPick'];
                    $packingReplenishmentHist->prh_status_qad = 'Yes';
                    $packingReplenishmentHist->prh_status = $packingReplenishmentMstr->prm_status;
                    $packingReplenishmentHist->created_by = Auth::user()->name;
                    $packingReplenishmentHist->save();
                }
            }

            // Cari approval

            // Buat approval
            $packingReplenishmentApproval = new PackingReplenishmentApproval();
            $packingReplenishmentApproval->prm_id = $packingReplenishmentMstr->id;
            $packingReplenishmentApproval->pra_sequence = 1;
            $packingReplenishmentApproval->pra_user_approver = Auth::user()->id;
            $packingReplenishmentApproval->pra_status = 'Waiting for confirmation';
            $packingReplenishmentApproval->created_by = Auth::user()->id;
            $packingReplenishmentApproval->updated_by = Auth::user()->id;
            $packingReplenishmentApproval->save();

            // Bandingin order qty & qty pick nya kalau sama ganti status biar gabisa buat shipment lagi
            if ($totalData == $totalMatch) {
                $shipmentScheduleMstr = ShipmentScheduleMstr::with([
                    'getShipmentScheduleDetail.getShipmentScheduleLocation'
                ])->find($idShipmentScheduleMstr);

                $shipmentScheduleMstr->ssm_status = 'Full Scheduled';
                $shipmentScheduleMstr->updated_by = Auth::user()->id;
                $shipmentScheduleMstr->save();

                foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentScheduleDet) {
                    foreach ($shipmentScheduleDet->getShipmentScheduleLocation as $shipmentScheduleLocation) {
                        $shipmentScheduleHistory = new ShipmentScheduleHist();
                        $shipmentScheduleHistory->ssh_number = $shipmentScheduleMstr->ssm_number;
                        $shipmentScheduleHistory->ssh_cust_code = $shipmentScheduleMstr->ssm_cust_code;
                        $shipmentScheduleHistory->ssh_cust_desc = $shipmentScheduleMstr->ssm_cust_desc;
                        $shipmentScheduleHistory->ssh_status_mstr = $shipmentScheduleMstr->ssm_status;
                        $shipmentScheduleHistory->ssh_sod_nbr = $shipmentScheduleDet->ssd_sod_nbr;
                        $shipmentScheduleHistory->ssh_sod_site = $shipmentScheduleDet->ssd_sod_site;
                        $shipmentScheduleHistory->ssh_sod_shipto = $shipmentScheduleDet->ssd_sod_shipto;
                        $shipmentScheduleHistory->ssh_sod_line = $shipmentScheduleDet->ssd_sod_line;
                        $shipmentScheduleHistory->ssh_sod_part = $shipmentScheduleDet->ssd_sod_part;
                        $shipmentScheduleHistory->ssh_sod_desc = $shipmentScheduleDet->ssd_sod_desc;
                        $shipmentScheduleHistory->ssh_sod_qty_ord = $shipmentScheduleDet->ssd_sod_qty_ord;
                        $shipmentScheduleHistory->ssh_status_det = $shipmentScheduleDet->ssd_status;
                        $shipmentScheduleHistory->ssh_site = $shipmentScheduleLocation->ssl_site;
                        $shipmentScheduleHistory->ssh_warehouse = $shipmentScheduleLocation->ssl_warehouse;
                        $shipmentScheduleHistory->ssh_location = $shipmentScheduleLocation->ssl_location;
                        $shipmentScheduleHistory->ssh_lotserial = $shipmentScheduleLocation->ssl_lotserial;
                        $shipmentScheduleHistory->ssh_level = $shipmentScheduleLocation->ssl_level;
                        $shipmentScheduleHistory->ssh_bin = $shipmentScheduleLocation->ssl_bin;
                        $shipmentScheduleHistory->ssh_qty_to_pick = $shipmentScheduleLocation->ssl_qty_to_pick;
                        $shipmentScheduleHistory->ssh_action = 'Shipper Create';
                        $shipmentScheduleHistory->created_by = Auth::user()->id;
                        $shipmentScheduleHistory->save();
                    }
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();

            Log::channel('packingReplenishment')->info($err);

            return false;
        }
    }
}
