<?php

namespace App\Services;

use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationMstr;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleMstr;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleDet;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleLoc;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleHist;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationDet;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationHist;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationApproval;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationApprovalHist;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtherShipmentPreparationServices
{
    public function saveOtherShipmentPreparation($approver, $ossmId, $otherShipmentPreparation, $activeConnection)
    {
        DB::beginTransaction();

        try {
            // Buat other shipment preparation mstr + det
            $otherShipmentPreparationMstr = OtherShipmentPreparationMstr::find($ossmId);
            if (!$otherShipmentPreparationMstr) {
                // Generate Running Number Other Shipment preparation
                $runningNumberServices = new RunningNumberServices();
                $ospmNumber = $runningNumberServices->getRunningNumberOtherShipmentPreparation();
                $otherShipmentPreparationMstr = new OtherShipmentPreparationMstr();
                $otherShipmentPreparationMstr->ospm_number = $ospmNumber;
            } else {
                $ospmNumber = $otherShipmentPreparationMstr->ospm_number;
            }
            $otherShipmentPreparationMstr->created_by = Auth::user()->id;
            $otherShipmentPreparationMstr->ospm_status = "Draft";
            $otherShipmentPreparationMstr->save();

            $totalMatch = 0;

            $fieldName = "mji_pack_dock";

            $wsaServices = new WSAServices();
            $locationWSA = $wsaServices->wsaGenCode($fieldName);
            if ($locationWSA[0] == "false") {
                DB::rollBack();

                Log::channel("otherShipmentPreparation")->info("Gen code not found");

                return false;
            }

            $location = $locationWSA[1][0]["t_value"];

            foreach ($otherShipmentPreparation as $key => $shipmentPreparation) {
                // dd($shipmentPreparation);
                // Update total picked qty
                $otherShipmentScheduleDet = OtherShipmentScheduleDet::with(["getOtherShipmentScheduleMaster"])
                    ->where("ossd_sent_to_qad", "No")
                    ->find($shipmentPreparation["id"]);

                if ($otherShipmentScheduleDet) {
                    // Ambil shipment schedule master ID
                    if ($key == 0) {
                        $idOtherShipmentScheduleMstr = $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->id;
                    }

                    $otherShipmentScheduleDet->ossd_qty_pick += $shipmentPreparation["totalPickedQty"];
                    foreach ($shipmentPreparation["locations"] as $locationDetail) {
                        // Baca conversion nya dulu
                        // $wsaConversion = $wsaServices->wsaQtyConversion($packingReplenishment, $activeConnection);
                        // if ($wsaConversion[0] == "true") {
                        //     $qtyTransfer = $locationDetail["qtyPick"] * $wsaConversion[1][0]->t_so_qty_conversion;
                        // } else {
                        //     $qtyTransfer = $locationDetail["qtyPick"];
                        // }

                        $qtyTransfer = $locationDetail["qtyPick"];

                        // Qxtend Transfer single item
                        $qxtendServices = new QxtendServices();
                        $qxtend = $qxtendServices->qxTransferSingleItemOtherShipmentPreparation(
                            $shipmentPreparation,
                            $qtyTransfer,
                            $locationDetail,
                            $location,
                            $activeConnection,
                        );

                        if ($qxtend[0] == false) {
                            DB::commit();

                            Log::channel("otherShipmentPreparation")->info($qxtend[1]);

                            return false;
                        }

                        // Update shipment schedule location
                        $otherShipmentScheduleLocation = OtherShipmentScheduleLoc::where("id", $locationDetail["id"])->first();
                        $otherShipmentScheduleLocation->ossl_qty_pick = $locationDetail["qtyPick"];
                        $otherShipmentScheduleLocation->updated_by = Auth::user()->id;
                        $otherShipmentScheduleLocation->save();

                        // Insert ke history
                        $otherShipmentScheduleHistory = new OtherShipmentScheduleHist();
                        $otherShipmentScheduleHistory->ossh_number = $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_number;
                        $otherShipmentScheduleHistory->ossh_cust_code =
                            $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_cust_code;
                        $otherShipmentScheduleHistory->ossh_cust_desc =
                            $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_cust_desc;
                        $otherShipmentScheduleHistory->ossh_status_mstr =
                            $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_status;
                        $otherShipmentScheduleHistory->ossd_part = $otherShipmentScheduleDet->ossd_part;
                        $otherShipmentScheduleHistory->ossd_desc = $otherShipmentScheduleDet->ossd_desc;
                        $otherShipmentScheduleHistory->ossd_uom = $otherShipmentScheduleDet->ossd_uom;
                        $otherShipmentScheduleHistory->ossd_qty_ord = $otherShipmentScheduleDet->ossd_qty_ord;
                        $otherShipmentScheduleHistory->ossd_qty_pick = $otherShipmentScheduleDet->ossd_qty_pick;
                        $otherShipmentScheduleHistory->ossd_status_det = $otherShipmentScheduleDet->ossd_status;
                        $otherShipmentScheduleHistory->ossd_sent_to_qad = $otherShipmentScheduleDet->ossd_sent_to_qad;
                        $otherShipmentScheduleHistory->ossl_site = $otherShipmentScheduleLocation->ossl_site;
                        $otherShipmentScheduleHistory->ossl_warehouse = $otherShipmentScheduleLocation->ossl_warehouse;
                        $otherShipmentScheduleHistory->ossl_location = $otherShipmentScheduleLocation->ossl_location;
                        $otherShipmentScheduleHistory->ossl_lotserial = $otherShipmentScheduleLocation->ossl_lotserial;
                        $otherShipmentScheduleHistory->ossl_level = $otherShipmentScheduleLocation->ossl_level;
                        $otherShipmentScheduleHistory->ossl_bin = $otherShipmentScheduleLocation->ossl_bin;
                        $otherShipmentScheduleHistory->ossl_qty_to_pick = $otherShipmentScheduleLocation->ossl_qty_to_pick;
                        $otherShipmentScheduleHistory->ossl_qty_pick = $otherShipmentScheduleLocation->ossl_qty_pick;
                        $otherShipmentScheduleHistory->ossl_action = "Create";
                        $otherShipmentScheduleHistory->created_by = Auth::user()->id;
                        $otherShipmentScheduleHistory->save();
                    }

                    $otherShipmentScheduleDet->ossd_sent_to_qad = "Yes";
                    $otherShipmentScheduleDet->save();

                    if ($otherShipmentScheduleDet->ossd_qty_ord == $otherShipmentScheduleDet->ossd_qty_pick) {
                        $totalMatch += 1;
                    }
                }
            }

            $otherShipmentPreparationMstr->ospm_status = "Waiting for approval";
            $otherShipmentPreparationMstr->save();

            // Buat packing replenishment detail + Buat packing replenishment history
            foreach ($otherShipmentPreparation as $shipmentPreparation) {
                foreach ($shipmentPreparation["locations"] as $locationDetail) {
                    $shipmentPreparationDet = OtherShipmentPreparationDet::where("ospm_id", $otherShipmentPreparationMstr->id)
                        ->where("ossl_id", $locationDetail["id"])
                        ->first();
                    // Buat packing replenishment detail
                    if ($shipmentPreparationDet == null) {
                        $shipmentPreparationDet = new OtherShipmentPreparationDet();
                        $shipmentPreparationDet->ospm_id = $otherShipmentPreparationMstr->id;
                        $shipmentPreparationDet->ossl_id = $locationDetail["id"];
                        $shipmentPreparationDet->ospd_status = "Yes";
                        $shipmentPreparationDet->created_by = Auth::user()->id;
                        $shipmentPreparationDet->save();
                    }

                    $otherShipmentPreparationHist = new OtherShipmentPreparationHist();
                    $otherShipmentPreparationHist->osph_number = $ospmNumber;
                    $otherShipmentPreparationHist->osph_item = $shipmentPreparation["ossdPart"];
                    $otherShipmentPreparationHist->osph_site = $locationDetail["site"];
                    $otherShipmentPreparationHist->osph_warehouse = $locationDetail["wh"];
                    $otherShipmentPreparationHist->osph_location = $locationDetail["loc"];
                    $otherShipmentPreparationHist->osph_lotserial = $locationDetail["lot"];
                    $otherShipmentPreparationHist->osph_level = $locationDetail["level"];
                    $otherShipmentPreparationHist->osph_bin = $locationDetail["bin"];
                    $otherShipmentPreparationHist->osph_qty_to_pick = $locationDetail["qtyToPick"];
                    $otherShipmentPreparationHist->osph_qty_pick = $locationDetail["qtyPick"];
                    $otherShipmentPreparationHist->osph_status_qad = "Yes";
                    $otherShipmentPreparationHist->osph_status = $otherShipmentPreparationMstr->ospm_status;
                    $otherShipmentPreparationHist->osph_action = "Create";
                    $otherShipmentPreparationHist->created_by = Auth::user()->id;
                    $otherShipmentPreparationHist->save();
                }
            }

            // Approver langsung saat create packing replenishment
            $otherShipmentPreparationApproval = new OtherShipmentPreparationApproval();
            $otherShipmentPreparationApproval->ospm_id = $otherShipmentPreparationMstr->id;
            $otherShipmentPreparationApproval->ospa_sequence = 1;
            $otherShipmentPreparationApproval->ospa_user_approver = $approver;
            $otherShipmentPreparationApproval->ospa_status = "Waiting for confirmation";
            $otherShipmentPreparationApproval->created_by = Auth::user()->id;
            $otherShipmentPreparationApproval->updated_by = Auth::user()->id;
            $otherShipmentPreparationApproval->save();

            $otherShipmentScheduleMstr = OtherShipmentScheduleMstr::with([
                "getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation",
            ])->find($idOtherShipmentScheduleMstr);

            $otherShipmentScheduleMstr->ossm_status = "Scheduled";
            $otherShipmentScheduleMstr->updated_by = Auth::user()->id;
            $otherShipmentScheduleMstr->save();

            foreach ($otherShipmentScheduleMstr->getOtherShipmentScheduleDetail as $otherShipmentScheduleDet) {
                foreach ($otherShipmentScheduleDet->getOtherShipmentScheduleLocation as $otherShipmentScheduleLocation) {
                    $otherShipmentScheduleHistory = new OtherShipmentScheduleHist();
                    $otherShipmentScheduleHistory->ossh_number = $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_number;
                    $otherShipmentScheduleHistory->ossh_cust_code =
                        $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_cust_code;
                    $otherShipmentScheduleHistory->ossh_cust_desc =
                        $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_cust_desc;
                    $otherShipmentScheduleHistory->ossh_status_mstr =
                        $otherShipmentScheduleDet->getOtherShipmentScheduleMaster->ossm_status;
                    $otherShipmentScheduleHistory->ossd_part = $otherShipmentScheduleDet->ossd_part;
                    $otherShipmentScheduleHistory->ossd_desc = $otherShipmentScheduleDet->ossd_desc;
                    $otherShipmentScheduleHistory->ossd_uom = $otherShipmentScheduleDet->ossd_uom;
                    $otherShipmentScheduleHistory->ossd_qty_ord = $otherShipmentScheduleDet->ossd_qty_ord;
                    $otherShipmentScheduleHistory->ossd_qty_pick = $otherShipmentScheduleDet->ossd_qty_pick;
                    $otherShipmentScheduleHistory->ossd_status_det = $otherShipmentScheduleDet->ossd_status;
                    $otherShipmentScheduleHistory->ossd_sent_to_qad = $otherShipmentScheduleDet->ossd_sent_to_qad;
                    $otherShipmentScheduleHistory->ossl_site = $otherShipmentScheduleLocation->ossl_site;
                    $otherShipmentScheduleHistory->ossl_warehouse = $otherShipmentScheduleLocation->ossl_warehouse;
                    $otherShipmentScheduleHistory->ossl_location = $otherShipmentScheduleLocation->ossl_location;
                    $otherShipmentScheduleHistory->ossl_lotserial = $otherShipmentScheduleLocation->ossl_lotserial;
                    $otherShipmentScheduleHistory->ossl_level = $otherShipmentScheduleLocation->ossl_level;
                    $otherShipmentScheduleHistory->ossl_bin = $otherShipmentScheduleLocation->ossl_bin;
                    $otherShipmentScheduleHistory->ossl_qty_to_pick = $otherShipmentScheduleLocation->ossl_qty_to_pick;
                    $otherShipmentScheduleHistory->ossl_qty_pick = $otherShipmentScheduleLocation->ossl_qty_pick;
                    $otherShipmentScheduleHistory->ossl_action = "Shipper Create";
                    $otherShipmentScheduleHistory->created_by = Auth::user()->id;
                    $otherShipmentScheduleHistory->save();
                }
            }

            DB::commit();

            return true;
        } catch (Exception $err) {
            DB::rollBack();

            Log::channel("otherShipmentPreparation")->info($err);

            return false;
        }
    }

    public function rejectOtherShipmentPreparation($otherShipmentPreparation, $reason, $otherShipmentScheduleNumber)
    {
        DB::beginTransaction();

        try {
            $otherShipmentPreparationApprovalData = OtherShipmentPreparationApproval::where("id", $otherShipmentPreparation["id"])->first();
            $otherShipmentPreparationApprovalData->ospa_status = "Rejected";
            $otherShipmentPreparationApprovalData->ospa_reason = $reason;
            $otherShipmentPreparationApprovalData->updated_by = Auth::user()->id;
            $otherShipmentPreparationApprovalData->save();

            $otherShipmentPreparationApprovalHist = new OtherShipmentPreparationApprovalHist();
            $otherShipmentPreparationApprovalHist->ospm_number =
                $otherShipmentPreparation["get_other_shipment_preparation_mstr"]["ospm_number"];
            $otherShipmentPreparationApprovalHist->ospah_sequence = $otherShipmentPreparationApprovalData->ospa_sequence;
            $otherShipmentPreparationApprovalHist->ospah_user_approver = $otherShipmentPreparationApprovalData->ospa_user_approver;
            $otherShipmentPreparationApprovalHist->ospah_alt_user_approver = $otherShipmentPreparationApprovalData->ospa_alt_user_approver;
            $otherShipmentPreparationApprovalHist->ospah_status = $otherShipmentPreparationApprovalData->ospa_status;
            $otherShipmentPreparationApprovalHist->ospah_reason = $otherShipmentPreparationApprovalData->ospa_reason;
            $otherShipmentPreparationApprovalHist->created_by = Auth::user()->id;
            $otherShipmentPreparationApprovalHist->save();

            $otherShipmentScheduleMstr = OtherShipmentScheduleMstr::where("ossm_number", $otherShipmentScheduleNumber)->first();
            $otherShipmentScheduleMstr->ossm_status = "Rejected";
            $otherShipmentScheduleMstr->updated_by = Auth::user()->id;
            $otherShipmentScheduleMstr->save();

            $otherShipmentPreparationMaster = OtherShipmentPreparationMstr::where(
                "id",
                $otherShipmentPreparation["get_other_shipment_preparation_mstr"]["id"],
            )->first();
            $otherShipmentPreparationMaster->ospm_status = "Rejected";
            $otherShipmentPreparationMaster->save();

            DB::commit();

            return true;
        } catch (Exception $err) {
            DB::rollBack();

            Log::channel("otherShipmentPreparation")->info($err);

            return false;
        }
    }

    public function approveOtherShipmentPreparation($shipmentPreparation, $reason, $otherShipmentScheduleNumber, $activeConnection)
    {
        DB::beginTransaction();

        try {
            $otherShipmentPreparationApproval = OtherShipmentPreparationApproval::where("id", $shipmentPreparation["id"])->first();
            $otherShipmentPreparationApproval->ospa_status = "Approved";
            $otherShipmentPreparationApproval->ospa_reason = $reason;
            $otherShipmentPreparationApproval->updated_by = Auth::user()->id;
            $otherShipmentPreparationApproval->save();

            $otherShipmentPreparationApprovalHist = new OtherShipmentPreparationApprovalHist();
            $otherShipmentPreparationApprovalHist->ospm_number = $shipmentPreparation["get_other_shipment_preparation_mstr"]["ospm_number"];
            $otherShipmentPreparationApprovalHist->ospah_sequence = $otherShipmentPreparationApproval->ospa_sequence;
            $otherShipmentPreparationApprovalHist->ospah_user_approver = $otherShipmentPreparationApproval->ospa_user_approver;
            $otherShipmentPreparationApprovalHist->ospah_alt_user_approver = $otherShipmentPreparationApproval->ospa_alt_user_approver;
            $otherShipmentPreparationApprovalHist->ospah_status = $otherShipmentPreparationApproval->ospa_status;
            $otherShipmentPreparationApprovalHist->ospah_reason = $otherShipmentPreparationApproval->ospa_reason;
            $otherShipmentPreparationApprovalHist->created_by = Auth::user()->id;
            $otherShipmentPreparationApprovalHist->save();

            $otherShipmentScheduleMaster = OtherShipmentScheduleMstr::with([
                "getOtherShipmentScheduleDetail.getOtherShipmentScheduleLocation",
            ])
                ->where("ossm_number", $otherShipmentScheduleNumber)
                ->first();

            $otherShipmentScheduleMaster->ossm_status = "Scheduled";
            $otherShipmentScheduleMaster->updated_by = Auth::user()->id;
            $otherShipmentScheduleMaster->save();

            $otherShipmentPreparationMaster = OtherShipmentPreparationMstr::with(["getOtherShipmentPreparationDet"])
                ->where("id", $shipmentPreparation["get_other_shipment_preparation_mstr"]["id"])
                ->first();
            $otherShipmentPreparationMaster->ospm_status = "Shipper Created";
            $otherShipmentPreparationMaster->save();

            // dd("stop");
            $fieldName = "mji_pack_dock";

            $wsaServices = new WSAServices();
            $locationWSA = $wsaServices->wsaGenCode($fieldName);
            if ($locationWSA[0] == "false") {
                DB::rollBack();

                Log::channel("packingReplenishment")->info("Gen code not found");

                return false;
            }

            $location = $locationWSA[1][0]["t_value"];
            $otherShipmentScheduleDetails = $otherShipmentScheduleMaster->getOtherShipmentScheduleDetail;

            // Qxtend buat issue unplanned (custom schema WMS meiji)
            $qxtendServices = new QxtendServices();
            foreach ($otherShipmentScheduleDetails as $otherShipmentScheduleDetail) {
                $locationDetails = $otherShipmentScheduleDetail->getOtherShipmentScheduleLocation;
                foreach ($locationDetails as $locationDetail) {
                    $qxtend = $qxtendServices->qxShipmentPreparationIssuesUnplanned(
                        $otherShipmentScheduleDetail,
                        $location,
                        $locationDetail,
                        $otherShipmentPreparationMaster->ospm_number,
                        $activeConnection,
                    );

                    if ($qxtend[0] == false) {
                        DB::rollBack();

                        Log::channel("otherShipmentPreparation")->info($qxtend[1]);

                        return false;
                    }
                }
                $otherShipmentScheduleDetail->ossd_status = "Scheduled";
                $otherShipmentScheduleDetail->save();
            }
            DB::commit();

            return true;
        } catch (Exception $err) {
            DB::rollBack();

            Log::channel("otherShipmentPreparation")->info($err);

            return false;
        }
    }
}
