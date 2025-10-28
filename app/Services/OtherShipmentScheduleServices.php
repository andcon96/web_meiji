<?php

namespace App\Services;

use App\Models\API\PackingReplenishment\PackingReplenishmentDet;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleDet;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleHist;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleLoc;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleMstr;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationDet;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtherShipmentScheduleServices
{
    public function saveOtherShipmentSchedule($customerCode, $customerName, $items)
    {
        DB::beginTransaction();

        try {
            // Generate Running Number Shipment Schedule
            $runningNumberServices = new RunningNumberServices();
            $ossm_number = $runningNumberServices->getRunningNumberOtherShipmentSchedule();

            // Create shipment schedule master
            $otherShipmentScheduleMstr = new OtherShipmentScheduleMstr();
            $otherShipmentScheduleMstr->ossm_number = $ossm_number;
            $otherShipmentScheduleMstr->ossm_cust_code = $customerCode;
            $otherShipmentScheduleMstr->ossm_cust_desc = $customerName;
            $otherShipmentScheduleMstr->ossm_status = "New";
            $otherShipmentScheduleMstr->created_by = Auth::user()->id;
            $otherShipmentScheduleMstr->save();

            // Create shipment schedule detail + insert to history
            foreach ($items as $item) {
                $otherShipmentScheduleDet = new OtherShipmentScheduleDet();
                $otherShipmentScheduleDet->ossm_id = $otherShipmentScheduleMstr->id;
                $otherShipmentScheduleDet->ossd_part = $item["itemPart"];
                $otherShipmentScheduleDet->ossd_uom = $item["UM"];
                $otherShipmentScheduleDet->ossd_desc = $item["itemDesc"];
                $otherShipmentScheduleDet->ossd_qty_ord = $item["totalQty"];
                $otherShipmentScheduleDet->ossd_qty_pick = 0.0;
                $otherShipmentScheduleDet->ossd_status = "New";
                $otherShipmentScheduleDet->ossd_sent_to_qad = "No";
                $otherShipmentScheduleDet->created_by = Auth::user()->id;
                $otherShipmentScheduleDet->save();

                // Create shipment schedule detail locations + insert to history
                foreach ($item["selectedLocations"] as $location) {
                    $data = explode("_", $location);
                    $itemCode = $data[0];
                    $locationCode = $data[1];
                    $lotCode = $data[2];
                    $warehouseCode = $data[3];
                    $binCode = $data[4];
                    $levelCode = $data[5];

                    $qtyPick = $item["locationQuantities"][$location];

                    $otherShipmentScheduleLocation = new OtherShipmentScheduleLoc();
                    $otherShipmentScheduleLocation->ossd_id = $otherShipmentScheduleDet->id;
                    $otherShipmentScheduleLocation->ossl_site = "2100";
                    $otherShipmentScheduleLocation->ossl_warehouse = $warehouseCode;
                    $otherShipmentScheduleLocation->ossl_location = $locationCode;
                    $otherShipmentScheduleLocation->ossl_lotserial = $lotCode;
                    $otherShipmentScheduleLocation->ossl_level = $levelCode;
                    $otherShipmentScheduleLocation->ossl_bin = $binCode;
                    $otherShipmentScheduleLocation->ossl_qty_to_pick = $qtyPick;
                    $otherShipmentScheduleLocation->ossl_qty_pick = 0.0;
                    $otherShipmentScheduleLocation->created_by = Auth::user()->id;
                    $otherShipmentScheduleLocation->save();

                    $otherShipmentScheduleHistory = new OtherShipmentScheduleHist();
                    $otherShipmentScheduleHistory->ossh_number = $otherShipmentScheduleMstr->ossm_number;
                    $otherShipmentScheduleHistory->ossh_cust_code = $otherShipmentScheduleMstr->ossm_cust_code;
                    $otherShipmentScheduleHistory->ossh_cust_desc = $otherShipmentScheduleMstr->ossm_cust_desc;
                    $otherShipmentScheduleHistory->ossh_status_mstr = $otherShipmentScheduleMstr->ossm_status;
                    $otherShipmentScheduleHistory->ossd_part = $otherShipmentScheduleDet->ossd_part;
                    $otherShipmentScheduleHistory->ossd_desc = $otherShipmentScheduleDet->ossd_desc;
                    $otherShipmentScheduleHistory->ossd_uom = $otherShipmentScheduleDet->ossd_uom;
                    $otherShipmentScheduleHistory->ossd_qty_ord = $otherShipmentScheduleDet->ossd_qty_ord;
                    $otherShipmentScheduleHistory->ossd_qty_pick = $otherShipmentScheduleDet->ossd_qty_ord;
                    $otherShipmentScheduleHistory->ossd_status_det = $otherShipmentScheduleDet->ossd_status;
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
            }

            DB::commit();
            // dd("stop");

            return true;
        } catch (Exception $err) {
            Log::channel("otherShipmentSchedule")->info($err);

            DB::rollBack();

            return false;
        }
    }

    public function deleteOtherShipmentSchedule($otherShipmentScheduleMstr)
    {
        DB::beginTransaction();

        try {
            foreach ($otherShipmentScheduleMstr->getOtherShipmentScheduleDetail as $otherShipmentDetail) {
                foreach ($otherShipmentDetail->getOtherShipmentScheduleLocation as $locationDetail) {
                    // Catat ke history kalau shipment schedule nya di hapus
                    $otherShipmentScheduleHistory = new OtherShipmentScheduleHist();
                    $otherShipmentScheduleHistory->ossh_number = $otherShipmentScheduleMstr->ossm_number;
                    $otherShipmentScheduleHistory->ossh_cust_code = $otherShipmentScheduleMstr->ossm_cust_code;
                    $otherShipmentScheduleHistory->ossh_cust_desc = $otherShipmentScheduleMstr->ossm_cust_desc;
                    $otherShipmentScheduleHistory->ossh_status_mstr = $otherShipmentScheduleMstr->ossm_status;
                    $otherShipmentScheduleHistory->ossd_part = $otherShipmentDetail->ossd_part;
                    $otherShipmentScheduleHistory->ossd_desc = $otherShipmentDetail->ossd_desc;
                    $otherShipmentScheduleHistory->ossd_uom = $otherShipmentDetail->ossd_uom;
                    $otherShipmentScheduleHistory->ossd_qty_ord = $otherShipmentDetail->ossd_qty_ord;
                    $otherShipmentScheduleHistory->ossd_qty_pick = $otherShipmentDetail->ossd_qty_ord;
                    $otherShipmentScheduleHistory->ossd_status_det = $otherShipmentDetail->ossd_status;
                    $otherShipmentScheduleHistory->ossl_site = $locationDetail->ossl_site;
                    $otherShipmentScheduleHistory->ossl_warehouse = $locationDetail->ossl_warehouse;
                    $otherShipmentScheduleHistory->ossl_location = $locationDetail->ossl_location;
                    $otherShipmentScheduleHistory->ossl_lotserial = $locationDetail->ossl_lotserial;
                    $otherShipmentScheduleHistory->ossl_level = $locationDetail->ossl_level;
                    $otherShipmentScheduleHistory->ossl_bin = $locationDetail->ossl_bin;
                    $otherShipmentScheduleHistory->ossl_qty_to_pick = $locationDetail->ossl_qty_to_pick;
                    $otherShipmentScheduleHistory->ossl_qty_pick = $locationDetail->ossl_qty_pick;
                    $otherShipmentScheduleHistory->ossl_action = "Delete";
                    $otherShipmentScheduleHistory->created_by = Auth::user()->id;
                    $otherShipmentScheduleHistory->save();

                    $locationDetail->delete();
                }

                $otherShipmentDetail->delete();
            }

            $otherShipmentScheduleMstr->delete();

            DB::commit();

            return true;
        } catch (Exception $err) {
            Log::channel("otherShipmentSchedule")->info($err);

            DB::rollBack();

            return false;
        }
    }

    public function updateOtherShipmentSchedule($idOtherShipmentScheduleMstr, $items)
    {
        DB::beginTransaction();

        try {
            $tempSSD_ID = [];
            $tempLocation = [];
            $tempLot = [];
            $tempWhs = [];
            $tempLevel = [];
            $tempBin = [];

            $otherShipmentScheduleMaster = OtherShipmentScheduleMstr::find($idOtherShipmentScheduleMstr);
            if (!$otherShipmentScheduleMaster) {
                throw new Exception("Shipment Schedule Master not found");
            }

            // Reset status if Rejected
            if ($otherShipmentScheduleMaster->ossm_status == "Rejected") {
                $otherShipmentScheduleMaster->ossm_status = "Re-submit";
                $otherShipmentScheduleMaster->updated_by = Auth::user()->id;
                $otherShipmentScheduleMaster->save();
            }

            $existingDetails = OtherShipmentScheduleDet::where("ossm_id", $idOtherShipmentScheduleMstr)->get();
            $incomingParts = collect($items)->pluck("itemPart")->toArray(); // 👈 all item parts from request

            // 1️⃣ Delete details that are no longer in $items
            foreach ($existingDetails as $detail) {
                if (!in_array($detail->ossd_part, $incomingParts)) {
                    // Find the shipment preparation
                    $locationList = OtherShipmentScheduleLoc::where("ossd_id", $detail->id)->get();
                    foreach ($locationList as $deletedLoc) {
                        // Find the shipment preparation
                        $shipmentPreparation = OtherShipmentPreparationDet::where("ossl_id", $deletedLoc->id)->first();
                        $shipmentPreparation->delete();
                    }
                    // delete related locations first
                    OtherShipmentScheduleLoc::where("ossd_id", $detail->id)->delete();
                    $detail->delete();
                }
            }

            // 2️⃣ Process (insert/update) remaining items
            foreach ($items as $item) {
                // Find or create detail line
                $otherShipmentScheduleDet = OtherShipmentScheduleDet::where("ossm_id", $idOtherShipmentScheduleMstr)
                    ->where("ossd_part", $item["itemPart"])
                    ->first();

                if (!$otherShipmentScheduleDet) {
                    $otherShipmentScheduleDet = new OtherShipmentScheduleDet();
                    $otherShipmentScheduleDet->ossm_id = $idOtherShipmentScheduleMstr;
                    $otherShipmentScheduleDet->ossd_status = "New";
                    $otherShipmentScheduleDet->created_by = Auth::user()->id;
                } else {
                    $otherShipmentScheduleDet->updated_by = Auth::user()->id;
                }

                $otherShipmentScheduleDet->ossd_part = $item["itemPart"];
                $otherShipmentScheduleDet->ossd_uom = $item["UM"];
                $otherShipmentScheduleDet->ossd_desc = $item["itemDesc"];
                $otherShipmentScheduleDet->ossd_qty_ord = $item["totalQty"];
                $otherShipmentScheduleDet->ossd_sent_to_qad = "No";
                $otherShipmentScheduleDet->ossd_qty_pick = 0;
                $otherShipmentScheduleDet->save();

                $tempSSD_ID[] = $otherShipmentScheduleDet->id;

                // 3️⃣ Process selected locations
                foreach ($item["selectedLocations"] as $locationKey) {
                    $parts = explode("_", $locationKey);

                    $itemPart = $parts[0] ?? null;
                    $location = $parts[1] ?? null;
                    $lotserial = $parts[2] ?? null;
                    $warehouse = $parts[3] ?? null;
                    $bin = $parts[4] ?? null;
                    $level = $parts[5] ?? null;

                    $qtyToPick = $item["locationQuantities"][$locationKey] ?? 0;

                    $shipmentLocation = OtherShipmentScheduleLoc::where("ossd_id", $otherShipmentScheduleDet->id)
                        ->where("ossl_location", $location)
                        ->where("ossl_lotserial", $lotserial)
                        ->where("ossl_warehouse", $warehouse)
                        ->where("ossl_bin", $bin)
                        ->where("ossl_level", $level)
                        ->first();

                    if (!$shipmentLocation) {
                        $shipmentLocation = new OtherShipmentScheduleLoc();
                        $shipmentLocation->ossd_id = $otherShipmentScheduleDet->id;
                        $shipmentLocation->created_by = Auth::user()->id;
                    } else {
                        $shipmentLocation->updated_by = Auth::user()->id;
                    }

                    $shipmentLocation->ossl_site = "2100";
                    $shipmentLocation->ossl_location = $location;
                    $shipmentLocation->ossl_lotserial = $lotserial;
                    $shipmentLocation->ossl_warehouse = $warehouse;
                    $shipmentLocation->ossl_bin = $bin;
                    $shipmentLocation->ossl_level = $level;
                    $shipmentLocation->ossl_qty_to_pick = $qtyToPick;
                    $shipmentLocation->ossl_qty_pick = 0;
                    $shipmentLocation->save();

                    // track for later deletion
                    $tempLocation[] = $location;
                    $tempLot[] = $lotserial;
                    $tempWhs[] = $warehouse;
                    $tempLevel[] = $level;
                    $tempBin[] = $bin;
                }
            }

            // 4️⃣ Delete unselected locations
            $unchecked = OtherShipmentScheduleLoc::whereIn("ossd_id", $tempSSD_ID)
                ->where(function ($q) use ($tempLocation, $tempLot, $tempWhs, $tempLevel, $tempBin) {
                    $q->whereNotIn("ossl_location", $tempLocation)
                        ->orWhereNotIn("ossl_lotserial", $tempLot)
                        ->orWhereNotIn("ossl_level", $tempLevel)
                        ->orWhereNotIn("ossl_warehouse", $tempWhs)
                        ->orWhereNotIn("ossl_bin", $tempBin);
                })
                ->get();

            foreach ($unchecked as $u) {
                $u->delete();
            }

            DB::commit();
            return true;
        } catch (Exception $err) {
            DB::rollBack();
            Log::error("Update Other Shipment Schedule Failed: " . $err->getMessage());
            dd($err);
            return false;
        }
    }
}
