<?php

namespace App\Services;

use App\Models\API\PackingReplenishment\PackingReplenishmentApproval;
use App\Models\API\PackingReplenishment\PackingReplenishmentApprovalHist;
use App\Models\API\PackingReplenishment\PackingReplenishmentDet;
use App\Models\API\PackingReplenishment\PackingReplenishmentMstr;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
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
            $packingReplenishmentMstr = PackingReplenishmentMstr::find($idPrm);
            $isEdit = (bool) $packingReplenishmentMstr;

            if (! $packingReplenishmentMstr) {
                $packingReplenishmentMstr = new PackingReplenishmentMstr();
                $packingReplenishmentMstr->created_by = Auth::user()->id;
            }

            $packingReplenishmentMstr->prm_status = 'Draft';
            $packingReplenishmentMstr->prm_shipper_nbr = $packingReplenishments[0]['sodNbr'] ?? null;
            $packingReplenishmentMstr->save();

            foreach ($packingReplenishments as $packingReplenishment) {
                $ssdId = $isEdit && is_numeric($packingReplenishment['id'] ?? null) ? $packingReplenishment['id'] : null;

                if ($ssdId && ShipmentScheduleDet::where('id', $ssdId)->exists()) {
                    $shipmentScheduleDet = ShipmentScheduleDet::find($ssdId);
                    $shipmentScheduleDet->updated_by = Auth::user()->id;
                } else {
                    $shipmentScheduleDet = new ShipmentScheduleDet();
                    $shipmentScheduleDet->created_by = Auth::user()->id;
                }

                $shipmentScheduleDet->ssd_sod_nbr = $packingReplenishment['sodNbr'];
                $shipmentScheduleDet->ssd_sod_site = $packingReplenishment['sodSite'];
                $shipmentScheduleDet->ssd_sod_shipto = $packingReplenishment['sodShip'];
                $shipmentScheduleDet->ssd_sod_line = $packingReplenishment['sodLine'];
                $shipmentScheduleDet->ssd_sod_part = $packingReplenishment['sodPart'];
                $shipmentScheduleDet->ssd_sod_desc = $packingReplenishment['sodDesc'];
                $shipmentScheduleDet->ssd_sod_qty_ord = $packingReplenishment['totalToPickQty'];
                $shipmentScheduleDet->ssd_sod_qty_pick = $packingReplenishment['totalPickedQty'];
                $shipmentScheduleDet->ssd_status = 'Pending';
                $shipmentScheduleDet->save();
                foreach ($packingReplenishment['locations'] as $location) {
             
                    $qtyPick = $location['qtyPick'] ?? null;
                    if ($qtyPick === null || $qtyPick === '' || (is_numeric($qtyPick) && (float) $qtyPick == 0)) {
                        continue;
                    }

                    $sslId = $isEdit && is_numeric($location['id'] ?? null) ? $location['id'] : null;

                    if ($sslId && ShipmentScheduleLoc::where('id', $sslId)->exists()) {
                        $shipmentScheduleLocation = ShipmentScheduleLoc::find($sslId);
                        $shipmentScheduleLocation->updated_by = Auth::user()->id;
                    } else {
                        $shipmentScheduleLocation = new ShipmentScheduleLoc();
                        $shipmentScheduleLocation->created_by = Auth::user()->id;
                    }

                    $shipmentScheduleLocation->ssd_id = $shipmentScheduleDet->id;
                    $shipmentScheduleLocation->ssl_site = $location['site'];
                    $shipmentScheduleLocation->ssl_warehouse = $location['wh'] ?? '0';
                    $shipmentScheduleLocation->ssl_location = $location['loc'] ?? '0';
                    $shipmentScheduleLocation->ssl_lotserial = $location['lot'];
                    $shipmentScheduleLocation->ssl_level = $location['level'] ?? '0';
                    $shipmentScheduleLocation->ssl_bin = $location['bin'] ?? '0';
                    $shipmentScheduleLocation->ssl_qty_to_pick = is_numeric($location['qtyToPick'] ?? null) ? $location['qtyToPick'] : 0;
                    $shipmentScheduleLocation->ssl_qty_pick = is_numeric($location['qtyPick'] ?? null) ? $location['qtyPick'] : 0;
                    $shipmentScheduleLocation->save();

                    $packingReplenishmentDet = PackingReplenishmentDet::where('prm_id', $packingReplenishmentMstr->id)->where('ssl_id', $shipmentScheduleLocation->id)->first();

                    if (! $packingReplenishmentDet) {
                        $packingReplenishmentDet = new PackingReplenishmentDet();
                        $packingReplenishmentDet->prm_id = $packingReplenishmentMstr->id;
                        $packingReplenishmentDet->ssl_id = $shipmentScheduleLocation->id;
                        $packingReplenishmentDet->prd_created_by = Auth::user()->id;
                    }

                    $packingReplenishmentDet->prd_status_qad = 'No';
                    $packingReplenishmentDet->save();
                }
            }

            $packingReplenishmentApproval = PackingReplenishmentApproval::where('prm_id', $packingReplenishmentMstr->id)->where('pra_sequence', 1)->first();

            if (! $packingReplenishmentApproval) {
                $packingReplenishmentApproval = new PackingReplenishmentApproval();
                $packingReplenishmentApproval->prm_id = $packingReplenishmentMstr->id;
                $packingReplenishmentApproval->pra_sequence = 1;
                $packingReplenishmentApproval->created_by = Auth::user()->id;
                $packingReplenishmentApproval->updated_by = Auth::user()->id;
            } else {
                $packingReplenishmentApproval->updated_by = Auth::user()->id;
            }

            $packingReplenishmentApproval->pra_user_approver = $approver;
            $packingReplenishmentApproval->pra_status = 'Waiting for confirmation';
            $packingReplenishmentApproval->save();

            $packingReplenishmentMstr->prm_status = 'Waiting for approval';
            $packingReplenishmentMstr->save();

            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();
            Log::channel('packingReplenishment')->error($err);

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
            $packingReplenishmentApprovalHist->prah_shipper_number = $packingReplenishment['get_packing_replenishment_mstr']['prm_shipper_nbr'];
            $packingReplenishmentApprovalHist->prah_sequence = $packingReplenishmentApprovalData->pra_sequence;
            $packingReplenishmentApprovalHist->prah_user_approver = $packingReplenishmentApprovalData->pra_user_approver;
            $packingReplenishmentApprovalHist->prah_alt_user_approver = $packingReplenishmentApprovalData->pra_alt_user_approver;
            $packingReplenishmentApprovalHist->prah_status = $packingReplenishmentApprovalData->pra_status;
            $packingReplenishmentApprovalHist->prah_reason = $packingReplenishmentApprovalData->prah_reason;
            $packingReplenishmentApprovalHist->created_by = Auth::user()->name;
            $packingReplenishmentApprovalHist->save();

            $packingReplenishmentMstr = PackingReplenishmentMstr::find($packingReplenishment['get_packing_replenishment_mstr']['id']);

            $packingReplenishmentMstr->prm_status = 'Rejected';
            $packingReplenishmentMstr->save();

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
            $packingReplenishmentApprovalHist->prah_shipper_number = $packingReplenishment['get_packing_replenishment_mstr']['prm_shipper_nbr'];
            $packingReplenishmentApprovalHist->prah_sequence = $packingReplenishmentApprovalData->pra_sequence;
            $packingReplenishmentApprovalHist->prah_user_approver = $packingReplenishmentApprovalData->pra_user_approver;
            $packingReplenishmentApprovalHist->prah_alt_user_approver = $packingReplenishmentApprovalData->pra_alt_user_approver;
            $packingReplenishmentApprovalHist->prah_status = $packingReplenishmentApprovalData->pra_status;
            $packingReplenishmentApprovalHist->prah_reason = $packingReplenishmentApprovalData->prah_reason;
            $packingReplenishmentApprovalHist->created_by = Auth::user()->name;
            $packingReplenishmentApprovalHist->save();

            $shipmentScheduleMaster = ShipmentScheduleDet::with(['getShipmentScheduleLocation'])
                ->where('ssd_sod_nbr', $shipmentScheduleNumber)
                ->get();

            $packingReplenishmentMaster = PackingReplenishmentMstr::with(['getPackingReplenishmentDet'])
                ->where('id', $packingReplenishment['get_packing_replenishment_mstr']['id'])
                ->first();
            $packingReplenishmentMaster->prm_status = 'Shipper Created';
            $packingReplenishmentMaster->save();

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
            $shipmentScheduleDetails = $shipmentScheduleMaster;

            DB::commit();

            return true;
        } catch (Exception $err) {
            DB::rollBack();

            Log::channel('packingReplenishment')->info($err);

            return false;
        }
    }
}
