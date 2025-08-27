<?php

namespace App\Services;

use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleHist;
use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentScheduleServices
{
    public function saveShipmentSchedule($customerCode, $customerName, $salesOrders)
    {
        DB::beginTransaction();

        try {
            // Generate Running Number Shipment Schedule
            $ssm_number = (new RunningNumberServices())->getRunningNumberShipmentSchedule();

            // Create shipment schedule master
            $shipmentScheduleMstr = new ShipmentScheduleMstr();
            $shipmentScheduleMstr->ssm_number = $ssm_number;
            $shipmentScheduleMstr->ssm_cust_code = $customerCode;
            $shipmentScheduleMstr->ssm_cust_desc = $customerName;
            $shipmentScheduleMstr->ssm_status = 'New';
            $shipmentScheduleMstr->created_by = Auth::user()->id;
            $shipmentScheduleMstr->save();

            // Create shipment schedule detail + insert to history
            foreach ($salesOrders as $order) {
                $shipmentScheduleDet = new ShipmentScheduleDet();
                $shipmentScheduleDet->ssm_id = $shipmentScheduleMstr->id;
                $shipmentScheduleDet->ssd_sod_nbr = $order['so_id'];
                $shipmentScheduleDet->ssd_sod_site = $order['site'];
                $shipmentScheduleDet->ssd_sod_shipto = $order['ship'];
                $shipmentScheduleDet->ssd_sod_line = $order['line'];
                $shipmentScheduleDet->ssd_sod_part = $order['part'];
                $shipmentScheduleDet->ssd_sod_desc = $order['desc'];
                $shipmentScheduleDet->ssd_sod_qty_ord = $order['qty'];
                $shipmentScheduleDet->ssd_status = 'New';
                $shipmentScheduleDet->created_by = Auth::user()->id;
                $shipmentScheduleDet->save();

                // Create shipment schedule detail locations + insert to history
                foreach ($order['selected_locations'] as $location) {
                    $shipmentScheduleLocation = new ShipmentScheduleLoc();
                    $shipmentScheduleLocation->ssd_id = $shipmentScheduleDet->id;
                    $shipmentScheduleLocation->ssl_site = $location['site'];
                    $shipmentScheduleLocation->ssl_warehouse = $location['warehouse'];
                    $shipmentScheduleLocation->ssl_location = $location['location'];
                    $shipmentScheduleLocation->ssl_lotserial = $location['lot'];
                    $shipmentScheduleLocation->ssl_level = $location['level'];
                    $shipmentScheduleLocation->ssl_bin = $location['bin'];
                    $shipmentScheduleLocation->ssl_qty_to_pick = $location['qty_to_pick'];
                    $shipmentScheduleLocation->created_by = Auth::user()->id;
                    $shipmentScheduleLocation->save();

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
                    $shipmentScheduleHistory->ssh_action = 'Create';
                    $shipmentScheduleHistory->created_by = Auth::user()->name;
                    $shipmentScheduleHistory->save();
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $err) {
            Log::channel('shipmentSchedule')->info($err);

            DB::rollBack();

            return false;
        }
    }

    public function deleteShipmentSchedule($shipmentScheduleMstr)
    {
        DB::beginTransaction();

        try {
            foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentDetail) {
                foreach ($shipmentDetail->getShipmentScheduleLocation as $locationDetail) {
                    // Catat ke history kalau shipment schedule nya di hapus
                    $shipmentScheduleHistory = new ShipmentScheduleHist();
                    $shipmentScheduleHistory->ssh_number = $shipmentScheduleMstr->ssm_number;
                    $shipmentScheduleHistory->ssh_cust_code = $shipmentScheduleMstr->ssm_cust_code;
                    $shipmentScheduleHistory->ssh_cust_desc = $shipmentScheduleMstr->ssm_cust_desc;
                    $shipmentScheduleHistory->ssh_status_mstr = $shipmentScheduleMstr->ssm_status;
                    $shipmentScheduleHistory->ssh_sod_nbr = $shipmentDetail->ssd_sod_nbr;
                    $shipmentScheduleHistory->ssh_sod_site = $shipmentDetail->ssd_sod_site;
                    $shipmentScheduleHistory->ssh_sod_shipto = $shipmentDetail->ssd_sod_shipto;
                    $shipmentScheduleHistory->ssh_sod_line = $shipmentDetail->ssd_sod_line;
                    $shipmentScheduleHistory->ssh_sod_part = $shipmentDetail->ssd_sod_part;
                    $shipmentScheduleHistory->ssh_sod_desc = $shipmentDetail->ssd_sod_desc;
                    $shipmentScheduleHistory->ssh_sod_qty_ord = $shipmentDetail->ssd_sod_qty_ord;
                    $shipmentScheduleHistory->ssh_status_det = $shipmentDetail->ssd_status;
                    $shipmentScheduleHistory->ssh_site = $locationDetail->ssl_site;
                    $shipmentScheduleHistory->ssh_warehouse = $locationDetail->ssl_warehouse;
                    $shipmentScheduleHistory->ssh_location = $locationDetail->ssl_location;
                    $shipmentScheduleHistory->ssh_lotserial = $locationDetail->ssl_lotserial;
                    $shipmentScheduleHistory->ssh_level = $locationDetail->ssl_level;
                    $shipmentScheduleHistory->ssh_bin = $locationDetail->ssl_bin;
                    $shipmentScheduleHistory->ssh_action = 'Delete';
                    $shipmentScheduleHistory->created_by = Auth::user()->name;
                    $shipmentScheduleHistory->save();

                    $locationDetail->delete();
                }

                $shipmentDetail->delete();
            }

            $shipmentScheduleMstr->delete();

            DB::commit();

            return true;
        } catch (\Exception $err) {
            Log::channel('shipmentSchedule')->info($err);

            DB::rollBack();

            return false;
        }
    }

    public function updateShipmentSchedule($idShipmentScheduleMstr, $salesOrders)
    {
        DB::beginTransaction();

        $shipmentScheduleMstr = ShipmentScheduleMstr::find($idShipmentScheduleMstr);
        $tempSSD_ID = [];
        $tempSite = [];
        $tempLocation = [];
        $tempLot = [];
        $tempWhs = [];
        $tempLevel = [];
        $tempBin = [];

        try {
            // Cek ke tiap so + line, kalau ada update, kalau engga create new line
            foreach ($salesOrders as $salesOrder) {
                $shipmentScheduleDet = ShipmentScheduleDet::where('ssm_id', $idShipmentScheduleMstr)
                    ->where('ssd_sod_nbr', $salesOrder['so_id'])
                    ->where('ssd_sod_line', $salesOrder['line'])
                    ->first();

                if ($shipmentScheduleDet) {
                    $shipmentScheduleDet->ssm_id = $idShipmentScheduleMstr;
                    $shipmentScheduleDet->ssd_sod_nbr = $salesOrder['so_id'];
                    $shipmentScheduleDet->ssd_sod_site = $salesOrder['site'];
                    $shipmentScheduleDet->ssd_sod_shipto = $salesOrder['ship'];
                    $shipmentScheduleDet->ssd_sod_line = $salesOrder['line'];
                    $shipmentScheduleDet->ssd_sod_part = $salesOrder['part'];
                    $shipmentScheduleDet->ssd_sod_desc = $salesOrder['desc'];
                    $shipmentScheduleDet->ssd_sod_qty_ord = $salesOrder['qty'];
                    $shipmentScheduleDet->updated_by  = Auth::user()->id;
                    $shipmentScheduleDet->save();
                } else {
                    $shipmentScheduleDet = new ShipmentScheduleDet();
                    $shipmentScheduleDet->ssm_id = $idShipmentScheduleMstr;
                    $shipmentScheduleDet->ssd_sod_nbr = $salesOrder['so_id'];
                    $shipmentScheduleDet->ssd_sod_site = $salesOrder['site'];
                    $shipmentScheduleDet->ssd_sod_shipto = $salesOrder['ship'];
                    $shipmentScheduleDet->ssd_sod_line = $salesOrder['line'];
                    $shipmentScheduleDet->ssd_sod_part = $salesOrder['part'];
                    $shipmentScheduleDet->ssd_sod_desc = $salesOrder['desc'];
                    $shipmentScheduleDet->ssd_sod_qty_ord = $salesOrder['qty'];
                    $shipmentScheduleDet->ssd_status = 'New';
                    $shipmentScheduleDet->created_by  = Auth::user()->id;
                    $shipmentScheduleDet->save();
                }

                array_push($tempSSD_ID, $shipmentScheduleDet->id);
                array_push($tempSite, $shipmentScheduleDet->ssd_sod_site);

                // Tiap SO line bisa punya banyak location detail, disini cek lagi ada lokasi baru atau engga.
                foreach ($salesOrder['selected_locations'] as $detailLocation) {
                    $action = 'Create';
                    $shipmentScheduleLocation = ShipmentScheduleLoc::where('ssd_id', $shipmentScheduleDet->id)
                        ->where('ssl_location', $detailLocation['location'])
                        ->where('ssl_lotserial', $detailLocation['lot'])
                        ->where('ssl_level', $detailLocation['level'])
                        ->where('ssl_warehouse', $detailLocation['warehouse'])
                        ->where('ssl_bin', $detailLocation['bin'])
                        ->where('ssl_site', $detailLocation['site'])
                        ->first();

                    if (!$shipmentScheduleLocation) {
                        $shipmentScheduleLocation = new ShipmentScheduleLoc();
                        $shipmentScheduleLocation->ssd_id = $shipmentScheduleDet->id;
                        $shipmentScheduleLocation->created_by = Auth::user()->id;
                    } else {
                        $action = 'Update';
                        $shipmentScheduleLocation->updated_by = Auth::user()->id;
                    }

                    array_push($tempLocation, $detailLocation['location']);
                    array_push($tempLot, $detailLocation['lot']);
                    array_push($tempWhs, $detailLocation['warehouse']);
                    array_push($tempLevel, $detailLocation['level']);
                    array_push($tempBin, $detailLocation['bin']);

                    $shipmentScheduleLocation->ssl_site = $detailLocation['site'];
                    $shipmentScheduleLocation->ssl_warehouse = $detailLocation['warehouse'];
                    $shipmentScheduleLocation->ssl_location = $detailLocation['location'];
                    $shipmentScheduleLocation->ssl_lotserial = $detailLocation['lot'];
                    $shipmentScheduleLocation->ssl_level = $detailLocation['level'];
                    $shipmentScheduleLocation->ssl_bin = $detailLocation['bin'];
                    $shipmentScheduleLocation->ssl_qty_to_pick = $detailLocation['qty_to_pick'];
                    $shipmentScheduleLocation->save();

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
                    $shipmentScheduleHistory->ssh_action = $action;
                    $shipmentScheduleHistory->created_by = Auth::user()->name;
                    $shipmentScheduleHistory->save();
                }
            }

            // Check for the deleted lines
            $allShipmentScheduleDet = ShipmentScheduleDet::with(['getShipmentScheduleMaster', 'getShipmentScheduleLocation'])->where('ssm_id', $idShipmentScheduleMstr)->orderBy('ssd_sod_nbr')->get();

            foreach ($allShipmentScheduleDet as $key => $shipmentScheduleDet) {
                $soNbrCollection = $shipmentScheduleDet->ssd_sod_nbr;
                $soLineCollection = $shipmentScheduleDet->ssd_sod_line;
                $exists = array_filter($salesOrders, function ($item) use ($soNbrCollection, $soLineCollection) {
                    return $item['so_id'] === $soNbrCollection && $item['line'] === $soLineCollection;
                });
                // Kalau gaada catat ke history kalau dihapus, terus delete dari schedule det
                if (empty($exists)) {
                    foreach ($shipmentScheduleDet->getShipmentScheduleLocation as $locationDetail) {
                        $shipmentScheduleHistory = new ShipmentScheduleHist();
                        $shipmentScheduleHistory->ssh_number = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_number;
                        $shipmentScheduleHistory->ssh_cust_code = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_cust_code;
                        $shipmentScheduleHistory->ssh_cust_desc = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_cust_desc;
                        $shipmentScheduleHistory->ssh_status_mstr = $shipmentScheduleDet->getShipmentScheduleMaster->ssm_status;
                        $shipmentScheduleHistory->ssh_sod_nbr = $shipmentScheduleDet->ssd_sod_nbr;
                        $shipmentScheduleHistory->ssh_sod_site = $shipmentScheduleDet->ssd_sod_site;
                        $shipmentScheduleHistory->ssh_sod_shipto = $shipmentScheduleDet->ssd_sod_shipto;
                        $shipmentScheduleHistory->ssh_sod_line = $shipmentScheduleDet->ssd_sod_line;
                        $shipmentScheduleHistory->ssh_sod_part = $shipmentScheduleDet->ssd_sod_part;
                        $shipmentScheduleHistory->ssh_sod_desc = $shipmentScheduleDet->ssd_sod_desc;
                        $shipmentScheduleHistory->ssh_sod_qty_ord = $shipmentScheduleDet->ssd_sod_qty_ord;
                        $shipmentScheduleHistory->ssh_status_det = $shipmentScheduleDet->ssd_status;
                        $shipmentScheduleHistory->ssh_site = $locationDetail->ssl_site;
                        $shipmentScheduleHistory->ssh_warehouse = $locationDetail->ssl_warehouse;
                        $shipmentScheduleHistory->ssh_location = $locationDetail->ssl_location;
                        $shipmentScheduleHistory->ssh_lotserial = $locationDetail->ssl_lotserial;
                        $shipmentScheduleHistory->ssh_level = $locationDetail->ssl_level;
                        $shipmentScheduleHistory->ssh_bin = $locationDetail->ssl_bin;
                        $shipmentScheduleHistory->ssh_qty_to_pick = $locationDetail->ssl_qty_to_pick;
                        $shipmentScheduleHistory->ssh_action = 'Deleted';
                        $shipmentScheduleHistory->created_by = Auth::user()->name;
                        $shipmentScheduleHistory->save();

                        $locationDetail->delete();
                    }

                    $shipmentScheduleDet->delete();
                }
            }

            // Buat ngehapus unselected
            $uncheckedLocation = ShipmentScheduleLoc::whereIn('ssd_id', $tempSSD_ID)
                ->where(function ($q) use ($tempLocation, $tempLot, $tempWhs, $tempLevel, $tempBin) {
                    $q->whereNotIn('ssl_location', $tempLocation)
                        ->orWhereNotIn('ssl_lotserial', $tempLot)
                        ->orWhereNotIn('ssl_level', $tempLevel)
                        ->orWhereNotIn('ssl_warehouse', $tempWhs)
                        ->orWhereNotIn('ssl_bin', $tempBin);
                })
                ->get();

            if ($uncheckedLocation->count() > 0) {
                foreach ($uncheckedLocation as $unchecked) {
                    $action = 'Deleted';
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
                    $shipmentScheduleHistory->ssh_site = $unchecked->ssl_site;
                    $shipmentScheduleHistory->ssh_warehouse = $unchecked->ssl_warehouse;
                    $shipmentScheduleHistory->ssh_location = $unchecked->ssl_location;
                    $shipmentScheduleHistory->ssh_lotserial = $unchecked->ssl_lotserial;
                    $shipmentScheduleHistory->ssh_level = $unchecked->ssl_level;
                    $shipmentScheduleHistory->ssh_bin = $unchecked->ssl_bin;
                    $shipmentScheduleHistory->ssh_qty_to_pick = $unchecked->ssl_qty_to_pick;
                    $shipmentScheduleHistory->ssh_action = $action;
                    $shipmentScheduleHistory->created_by = Auth::user()->name;
                    $shipmentScheduleHistory->save();

                    $unchecked->delete();
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();
            Log::channel('shipmentSchedule')->info($err);

            return false;
        }
    }
}
