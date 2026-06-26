<?php

namespace App\Services;

use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use App\Models\API\PackingReplenishment\PackingReplenishmentApprovalHist;
use App\Models\API\PackingReplenishment\PackingReplenishmentDet;
use App\Models\API\PackingReplenishment\PackingReplenishmentHist;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleHist;
use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\API\ShipperConfirm\ShipperConfirm;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackingReplenishmentServices
{
    public function savePackingReplenishment($approver, $idPrm, $packingReplenishments)
    {
        DB::beginTransaction();

        try {
            // Buat Packing Replenishment Mstr
            $packingReplenishmentMstr = PackingReplenishmentMstr::find($idPrm);
            if (! $packingReplenishmentMstr) {
                $packingReplenishmentMstr = new PackingReplenishmentMstr();
            }
            $packingReplenishmentMstr->created_by = Auth::user()->id;
            $packingReplenishmentMstr->prm_status = 'Draft';
            $packingReplenishmentMstr->save();

            $idShipmentScheduleMstr = null;

            foreach ($packingReplenishments as $key => $packingReplenishment) {
                $shipmentScheduleDet = ShipmentScheduleDet::with(['getShipmentScheduleMaster'])
                    ->where('ssd_sent_to_qad', 'No')
                    ->find($packingReplenishment['id']);

                if ($shipmentScheduleDet) {
                    if ($key == 0) {
                        $idShipmentScheduleMstr = $shipmentScheduleDet->getShipmentScheduleMaster->id;
                    }

                    $shipmentScheduleDet->ssd_sod_qty_pick += (float) $packingReplenishment['totalPickedQty'];

                    foreach ($packingReplenishment['locations'] as $locationDetail) {
                        // Update shipment schedule location
                        $shipmentScheduleLocation = ShipmentScheduleLoc::where('id', $locationDetail['id'])->first();
                        if ($shipmentScheduleLocation) {
                            $shipmentScheduleLocation->ssl_qty_pick = (float) $locationDetail['qtyPick'];
                            $shipmentScheduleLocation->updated_by = Auth::user()->id;
                            $shipmentScheduleLocation->save();
                        }

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
                        $shipmentScheduleHistory->ssh_site = $shipmentScheduleLocation?->ssl_site;
                        $shipmentScheduleHistory->ssh_warehouse = $shipmentScheduleLocation?->ssl_warehouse;
                        $shipmentScheduleHistory->ssh_location = $shipmentScheduleLocation?->ssl_location;
                        $shipmentScheduleHistory->ssh_lotserial = $shipmentScheduleLocation?->ssl_lotserial;
                        $shipmentScheduleHistory->ssh_level = $shipmentScheduleLocation?->ssl_level;
                        $shipmentScheduleHistory->ssh_bin = $shipmentScheduleLocation?->ssl_bin;
                        $shipmentScheduleHistory->ssh_qty_to_pick = $shipmentScheduleLocation?->ssl_qty_to_pick;
                        $shipmentScheduleHistory->ssh_action = 'Create';
                        $shipmentScheduleHistory->created_by = Auth::user()->id;
                        $shipmentScheduleHistory->save();
                    }

                    $shipmentScheduleDet->ssd_sent_to_qad = 'Yes';
                    $shipmentScheduleDet->save();
                }
            }

            $packingReplenishmentMstr->prm_status = 'Waiting for approval';
            $packingReplenishmentMstr->save();

            // Buat packing replenishment detail + history
            // Buat packing replenishment detail + history
            foreach ($packingReplenishments as $packingReplenishment) {
                foreach ($packingReplenishment['locations'] as $locationDetail) {
                    $packingReplenishmentDet = PackingReplenishmentDet::where('prm_id', $packingReplenishmentMstr->id)
                        ->where('ssl_id', $locationDetail['id'])
                        ->first();

                    if ($packingReplenishmentDet == null) {
                        $packingReplenishmentDet = new PackingReplenishmentDet();
                        $packingReplenishmentDet->prm_id = $packingReplenishmentMstr->id;
                        $packingReplenishmentDet->ssl_id = $locationDetail['id'];
                        $packingReplenishmentDet->prd_status_qad = 'Yes';
                        $packingReplenishmentDet->prd_created_by = Auth::user()->id;
                        $packingReplenishmentDet->save();
                    }

                    // FIX: tambahkan "new PackingReplenishmentHist()" dan perbaiki prh_shipper_nbr + prh_so_nbr
                    $packingReplenishmentHist = new PackingReplenishmentHist();
                    $packingReplenishmentHist->prh_shipper_nbr = null;
                    $packingReplenishmentHist->prh_so_nbr = $packingReplenishment['sodNbr'] ?? '';
                    $packingReplenishmentHist->prh_so_line = $packingReplenishment['sodLine'] ?? '';
                    $packingReplenishmentHist->prh_site = $locationDetail['site'] ?? '';
                    $packingReplenishmentHist->prh_warehouse = $locationDetail['wh'] ?? '';
                    $packingReplenishmentHist->prh_location = $locationDetail['loc'] ?? '';
                    $packingReplenishmentHist->prh_lotserial = $locationDetail['lot'] ?? '';
                    $packingReplenishmentHist->prh_level = $locationDetail['level'] ?? '';
                    $packingReplenishmentHist->prh_bin = $locationDetail['bin'] ?? '';
                    $packingReplenishmentHist->prh_qty_pick = (float) $locationDetail['qtyPick'];
                    $packingReplenishmentHist->prh_status_qad = 'Yes';
                    $packingReplenishmentHist->prh_status = $packingReplenishmentMstr->prm_status;
                    $packingReplenishmentHist->created_by = Auth::user()->name;
                    $packingReplenishmentHist->save();
                }
            }
            // Approver langsung saat create packing replenishment
            $packingReplenishmentApproval = new PackingReplenishmentApproval();
            $packingReplenishmentApproval->prm_id = $packingReplenishmentMstr->id;
            $packingReplenishmentApproval->pra_sequence = 1;
            $packingReplenishmentApproval->pra_user_approver = $approver;
            $packingReplenishmentApproval->pra_status = 'Waiting for confirmation';
            $packingReplenishmentApproval->created_by = Auth::user()->id;
            $packingReplenishmentApproval->updated_by = Auth::user()->id;
            $packingReplenishmentApproval->save();

            if ($idShipmentScheduleMstr) {
                $shipmentScheduleMstr = ShipmentScheduleMstr::with([
                    'getShipmentScheduleDetail.getShipmentScheduleLocation',
                ])->find($idShipmentScheduleMstr);

                if ($shipmentScheduleMstr) {
                    $shipmentScheduleMstr->ssm_status = 'Scheduled';
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
            }

            DB::commit();

            return true;

        } catch (\Exception $err) {
            DB::rollBack();
            Log::channel('packingReplenishment')->info($err);

            return false;
        }
    }

    public function rejectPackingReplenishment($packingReplenishment, $reason, $shipmentScheduleNumber)
    {
        DB::beginTransaction();

        try {
            $packingReplenishmentApprovalData = PackingReplenishmentApproval::where('id', $packingReplenishment['id'])->first();
            $packingReplenishmentApprovalData->pra_status = 'Rejected';
            $packingReplenishmentApprovalData->pra_reason = $reason;
            $packingReplenishmentApprovalData->updated_by = Auth::user()->id;
            $packingReplenishmentApprovalData->save();

            $packingReplenishmentApprovalHist = new PackingReplenishmentApprovalHist();
            $packingReplenishmentApprovalHist->prah_shipper_number =
                $packingReplenishment['get_packing_replenishment_mstr']['prm_shipper_nbr'];
            $packingReplenishmentApprovalHist->prah_sequence = $packingReplenishmentApprovalData->pra_sequence;
            $packingReplenishmentApprovalHist->prah_user_approver = $packingReplenishmentApprovalData->pra_user_approver;
            $packingReplenishmentApprovalHist->prah_alt_user_approver = $packingReplenishmentApprovalData->pra_alt_user_approver;
            $packingReplenishmentApprovalHist->prah_status = $packingReplenishmentApprovalData->pra_status;
            $packingReplenishmentApprovalHist->prah_reason = $packingReplenishmentApprovalData->prah_reason;
            $packingReplenishmentApprovalHist->created_by = Auth::user()->name;
            $packingReplenishmentApprovalHist->save();

            $shipmentScheduleMaster = ShipmentScheduleMstr::where('ssm_number', $shipmentScheduleNumber)->first();
            $shipmentScheduleMaster->ssm_status = 'Rejected';
            $shipmentScheduleMaster->updated_by = Auth::user()->id;
            $shipmentScheduleMaster->save();

            $packingReplenishmentMaster = PackingReplenishmentMstr::where(
                'id',
                $packingReplenishment['get_packing_replenishment_mstr']['id'],
            )->first();
            $packingReplenishmentMaster->prm_status = 'Rejected';
            $packingReplenishmentMaster->save();

            DB::commit();

            return true;
        } catch (Exception $err) {
            DB::rollBack();

            Log::channel('packingReplenishment')->info($err);

            return false;
        }
    }

    public function approvePackingReplenishment($packingReplenishment, $reason, $shipmentScheduleNumber, $activeConnection)
    {
        DB::beginTransaction();

        try {
            $packingReplenishmentApprovalData = PackingReplenishmentApproval::where('id', $packingReplenishment['id'])->first();
            $packingReplenishmentApprovalData->pra_status = 'Approved';
            $packingReplenishmentApprovalData->pra_reason = $reason;
            $packingReplenishmentApprovalData->updated_by = Auth::user()->id;
            $packingReplenishmentApprovalData->save();

            $packingReplenishmentApprovalHist = new PackingReplenishmentApprovalHist();
            $packingReplenishmentApprovalHist->prah_shipper_number =
                $packingReplenishment['get_packing_replenishment_mstr']['prm_shipper_nbr'];
            $packingReplenishmentApprovalHist->prah_sequence = $packingReplenishmentApprovalData->pra_sequence;
            $packingReplenishmentApprovalHist->prah_user_approver = $packingReplenishmentApprovalData->pra_user_approver;
            $packingReplenishmentApprovalHist->prah_alt_user_approver = $packingReplenishmentApprovalData->pra_alt_user_approver;
            $packingReplenishmentApprovalHist->prah_status = $packingReplenishmentApprovalData->pra_status;
            $packingReplenishmentApprovalHist->prah_reason = $packingReplenishmentApprovalData->prah_reason;
            $packingReplenishmentApprovalHist->created_by = Auth::user()->name;
            $packingReplenishmentApprovalHist->save();

            $shipmentScheduleMaster = ShipmentScheduleMstr::with(['getShipmentScheduleDetail.getShipmentScheduleLocation'])
                ->where('ssm_number', $shipmentScheduleNumber)
                ->first();
            $shipmentScheduleMaster->ssm_status = 'Scheduled';
            $shipmentScheduleMaster->updated_by = Auth::user()->id;
            $shipmentScheduleMaster->save();

            $packingReplenishmentMaster = PackingReplenishmentMstr::with(['getPackingReplenishmentDet'])
                ->where('id', $packingReplenishment['get_packing_replenishment_mstr']['id'])
                ->first();
            $packingReplenishmentMaster->prm_status = 'Shipper Created';
            $packingReplenishmentMaster->save();

            // dd("stop");

            // Insert data ke shipper confirm table
            $shipperConfirm = new ShipperConfirm();
            $shipperConfirm->prm_id = $packingReplenishmentMaster->id;
            $shipperConfirm->sc_sequence = 1;
            $shipperConfirm->sc_user_approver = Auth::user()->id;
            $shipperConfirm->sc_status = 'Waiting for confirmation';
            $shipperConfirm->created_by = Auth::user()->id;
            $shipperConfirm->save();

            $fieldName = 'mji_pack_dock';

            $wsaServices = new WSAServices();
            $locationWSA = $wsaServices->wsaGenCode($fieldName);
            if ($locationWSA[0] == 'false') {
                DB::rollBack();

                Log::channel('packingReplenishment')->info('Gen code not found');

                return false;
            }

            $location = $locationWSA[1][0]['t_value'];
            $shipmentScheduleDetails = $shipmentScheduleMaster->getShipmentScheduleDetail;

            // Qxtend buat sales order shipper maintenance
            $qxtendServices = new QxtendServices();
            $qxtend = $qxtendServices->qxSalesOrderShipper(
                'create',
                $location,
                $shipmentScheduleDetails,
                $packingReplenishmentMaster->id,
                $activeConnection,
            );

            if ($qxtend[0] == false) {
                DB::commit();

                Log::channel('packingReplenishment')->info($qxtend[1]);

                return false;
            }

            $shipperNumber = null;

            // Ambil nomor shipper, update ke packing replenishment master buat nomor shipper nya
            $getShipperNumber = $wsaServices->wsaGetShipperNumber(
                $shipmentScheduleDetails[0]->ssd_sod_site,
                $packingReplenishmentMaster->id,
                $activeConnection,
            );

            if ($getShipperNumber[0] == 'false') {
                Log::channel('packingReplenishment')->info(
                    'Gagal mengambil data untuk packing replenishment: '.$packingReplenishmentMaster->id,
                );
            }

            $shipperNumber = substr($getShipperNumber[1][0]->t_shipper_nbr, 1);
            $packingReplenishmentMaster->prm_shipper_nbr = $shipperNumber;
            $packingReplenishmentMaster->save();
            DB::commit();

            return true;
        } catch (Exception $err) {
            DB::rollBack();

            Log::channel('packingReplenishment')->info($err);

            return false;
        }
    }
}
