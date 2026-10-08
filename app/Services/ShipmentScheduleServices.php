<?php

namespace App\Services;

use App\Models\API\ShipmentSchedule\ShipmentScheduleApproval;
use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleHist;
use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\API\ShipperConfirm\ShipperConfirm;
use App\Models\API\TransactionHistory;
use App\Models\API\xxinvDet;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentScheduleServices
{
    public function saveShipmentSchedule($approver, $idPrm, $shipmentSchedule)
    {
        DB::beginTransaction();

        try {

            $shipmentScheduleMstr = ShipmentScheduleMstr::find($idPrm);
            $isEdit = (bool) $shipmentScheduleMstr;

            if (! $shipmentScheduleMstr) {
                $shipmentScheduleMstr = new shipmentScheduleMstr();
                $shipmentScheduleMstr->created_by = Auth::user()->id;
            }

            $shipmentScheduleMstr->ssm_status = 'Waiting for approval';
            $shipmentScheduleMstr->ssm_number = $shipmentSchedule[0]['sodNbr'] ?? null;
            $shipmentScheduleMstr->save();

            foreach ($shipmentSchedule as $order) {
                $shipmentScheduleDet = new ShipmentScheduleDet();
                $shipmentScheduleDet->ssm_id = $shipmentScheduleMstr->id;

                $shipmentScheduleDet->ssd_sod_nbr = $order['sodNbr'];
                $shipmentScheduleDet->created_by = Auth::user()->id;
                $shipmentScheduleDet->ssd_sod_site = $order['sodSite'];
                $shipmentScheduleDet->ssd_sod_shipto = $order['sodShip'];
                $shipmentScheduleDet->ssd_sod_line = $order['sodLine'];
                $shipmentScheduleDet->ssd_sod_part = $order['sodPart'];
                $shipmentScheduleDet->ssd_sod_desc = $order['sodDesc'];
                $shipmentScheduleDet->ssd_sod_lot = $order['sodLot'] ?? null;
                $shipmentScheduleDet->ssd_sod_qty_ord = $order['totalToPickQty'];
                $shipmentScheduleDet->ssd_sod_qty_pick = $order['totalPickedQty'];
                $shipmentScheduleDet->ssd_status = 'Pending';
                // $shipmentScheduleDet->ssm_id = '1';

                $shipmentScheduleDet->save();

                foreach ($order['locations'] as $location) {
                    $shipmentScheduleLocation = new ShipmentScheduleLoc();
                    $shipmentScheduleLocation->created_by = Auth::user()->id;
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

                    // $this->writeScheduleHistory(
                    //     $shipmentScheduleMstr,
                    //     $shipmentScheduleDet,
                    //     $shipmentScheduleLocation,
                    //     'Create'
                    // );
                }
            }
            $packingReplenishmentApproval = new ShipmentScheduleApproval();
            $packingReplenishmentApproval->ssm_id = $shipmentScheduleMstr->id;
            $packingReplenishmentApproval->ssa_status =  'Waiting for confirmation';
            $packingReplenishmentApproval->ssa_sequence = 1;
            $packingReplenishmentApproval->ssa_user_approver = $approver;
            $packingReplenishmentApproval->created_by = Auth::user()->id;
            $packingReplenishmentApproval->updated_by = Auth::user()->id;
            $packingReplenishmentApproval->save();
            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();
            Log::channel('shipmentSchedule')->error($err);

            return false;
        }
    }

    public function rejectShipment($idSsm, $reason)
    {
        DB::beginTransaction();

        try {
            $shipmentScheduleMstr = ShipmentScheduleMstr::with('getShipmentScheduleDetail.shipmentScheduleLoc')
                ->findOrFail($idSsm);

            $approval = $this->getApprovalForAction($shipmentScheduleMstr);

            $approval->ssa_status = 'Pending';
            $approval->ssa_reason = $reason;
            $approval->updated_by = Auth::user()->id;
            $approval->save();

            foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentScheduleDet) {
                foreach ($shipmentScheduleDet->shipmentScheduleLoc as $shipmentScheduleLocation) {
                    $qtyPick = (float) ($shipmentScheduleLocation->ssl_qty_pick ?? 0);

                    if ($qtyPick <= 0) {
                        continue;
                    }

                    // Kembalikan stok: shp -> wrh
                    $this->moveInventory($shipmentScheduleDet->ssd_sod_part, $shipmentScheduleLocation, -$qtyPick);

                    $this->writeTransactionHistory(
                        $shipmentScheduleDet,
                        $shipmentScheduleLocation,
                        'Shipment Reject',
                        $reason,
                        $qtyPick
                    );

                    $shipmentScheduleLocation->ssl_qty_pick = 0;
                    $shipmentScheduleLocation->updated_by = Auth::user()->id;
                    $shipmentScheduleLocation->save();

                    // $this->writeScheduleHistory(
                    //     $shipmentScheduleMstr,
                    //     $shipmentScheduleDet,
                    //     $shipmentScheduleLocation,
                    //     'Reject Packing',
                    //     $reason
                    // );
                }

                $shipmentScheduleDet->ssd_sod_qty_pick = 0;
                $shipmentScheduleDet->ssd_status = 'Pending';
                $shipmentScheduleDet->updated_by = Auth::user()->id;
                $shipmentScheduleDet->save();
            }

            $shipmentScheduleMstr->ssm_status = 'Draft';
            $shipmentScheduleMstr->updated_by = Auth::user()->id;
            $shipmentScheduleMstr->save();

            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();
            Log::channel('shipmentSchedule')->error($err);

            return false;
        }
    }
    public function approveShipment($idSsm, $reason = null)
    {
        DB::beginTransaction();

        try {
            $shipmentScheduleMstr = ShipmentScheduleMstr::with('getShipmentScheduleDetail.shipmentScheduleLoc')
                ->findOrFail($idSsm);

            $approval = $this->getApprovalForAction($shipmentScheduleMstr);

            $approval->ssa_status = 'Approved';
            $approval->ssa_reason = $reason;
            $approval->updated_by = Auth::user()->id;
            $approval->save();

            foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentScheduleDet) {
                foreach ($shipmentScheduleDet->shipmentScheduleLoc as $shipmentScheduleLocation) {
                    $qtyPick = (float) ($shipmentScheduleLocation->ssl_qty_pick ?? 0);

                    if ($qtyPick <= 0) {
                        continue;
                    }

                    // $this->writeTransactionHistory(
                    //     $shipmentScheduleDet,
                    //     $shipmentScheduleLocation,
                    //     'Shipment Approve',
                    //     'Shipment Approve',
                    //     $qtyPick
                    // );

                    // $this->writeScheduleHistory(
                    //     $shipmentScheduleMstr,
                    //     $shipmentScheduleDet,
                    //     $shipmentScheduleLocation,
                    //     'Approve Packing',
                    //     $reason
                    // );
                }

                $shipmentScheduleDet->ssd_status = 'Approved';
                $shipmentScheduleDet->updated_by = Auth::user()->id;
                $shipmentScheduleDet->save();
            }

            $shipmentScheduleMstr->ssm_status = 'Shipper Created';
            $shipmentScheduleMstr->updated_by = Auth::user()->id;
            $shipmentScheduleMstr->save();

            $shipperConfirm = new ShipperConfirm();
            $shipperConfirm->ssm_id = $shipmentScheduleMstr->id;
            $shipperConfirm->sc_sequence = 1;
            $shipperConfirm->sc_user_approver = Auth::user()->id;
            $shipperConfirm->sc_status = 'Waiting for confirmation';
            $shipperConfirm->created_by = Auth::user()->id;
            $shipperConfirm->save();

            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();
            Log::channel('shipmentSchedule')->error($err);

            return false;
        }
    }



    private function toNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace(',', '', trim((string) $value));

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function getApprovalForAction(ShipmentScheduleMstr $mstr): ShipmentScheduleApproval
    {
        if ($mstr->ssm_status !== 'Waiting for approval') {
            throw new \Exception('Status shipment schedule bukan Waiting for approval.');
        }

        $approval = ShipmentScheduleApproval::where('ssm_id', $mstr->id)
            ->where('ssa_sequence', 1)
            ->firstOrFail();

        $allowed = [(string) $approval->ssa_user_approver];
        if ($approval->ssa_alt_user_approver) {
            $allowed[] = (string) $approval->ssa_alt_user_approver;
        }

        if (! in_array((string) Auth::user()->id, $allowed, true)) {
            throw new \Exception('User bukan approver.');
        }

        return $approval;
    }
    private function moveInventory($part, ShipmentScheduleLoc $loc, float $qty): void
    {
        $inventory = xxinvDet::where('xxinv_part', $part)
            ->where('xxinv_lot', $loc->ssl_lotserial)
            ->where('xxinv_bin', $loc->ssl_bin ?? '0')
            ->where('xxinv_level', $loc->ssl_level ?? '0')
            ->first();

        if (! $inventory) {
            throw new \Exception('Inventory tidak ditemukan.');
        }

        $inventory->xxinv_qty_wrh = max(0, (float) $inventory->xxinv_qty_wrh - $qty);
        $inventory->xxinv_qty_shp = max(0, (float) $inventory->xxinv_qty_shp + $qty);
        $inventory->save();
    }


    private function writeTransactionHistory(
        ShipmentScheduleDet $det,
        ShipmentScheduleLoc $loc,
        string $activity,
        string $remark,
        float $qty
    ): void {
        $trx = new TransactionHistory();
        $trx->tr_nbr = $det->ssd_sod_nbr ?? '';
        $trx->tr_order = '';
        $trx->tr_program = 'Shipment Module';
        $trx->tr_activity = $activity;
        $trx->tr_user = Auth::user()->username ?? '';
        $trx->tr_part = $det->ssd_sod_part ?? '';
        $trx->tr_uom = $det->ssd_uom ?? '';
        $trx->tr_line = $det->ssd_sod_line ?? 0;
        $trx->tr_lot = $loc->ssl_lotserial ?? '';
        $trx->tr_qty = $qty;
        $trx->tr_date = now();
        $trx->tr_reference = '';
        $trx->tr_site = $loc->ssl_site ?? '2100';
        $trx->tr_location = $loc->ssl_location ?? '';
        $trx->tr_warehouse = $loc->ssl_warehouse ?? '';
        $trx->tr_level = $loc->ssl_level ?? '0';
        $trx->tr_bin = $loc->ssl_bin ?? '0';
        $trx->tr_remark = $remark;
        $trx->save();
    }

    private function writeScheduleHistory(
        ShipmentScheduleMstr $mstr,
        ShipmentScheduleDet $det,
        ShipmentScheduleLoc $loc,
        string $action,
        ?string $reason = null
    ): void {
        $hist = new ShipmentScheduleHist();
        $hist->ssh_number = $mstr->ssm_number;
        $hist->ssh_cust_code = $mstr->ssm_cust_code;
        $hist->ssh_cust_desc = $mstr->ssm_cust_desc;
        $hist->ssh_status_mstr = $mstr->ssm_status;
        $hist->ssh_sod_nbr = $det->ssd_sod_nbr;
        $hist->ssh_sod_site = $det->ssd_sod_site;
        $hist->ssh_sod_shipto = $det->ssd_sod_shipto;
        $hist->ssh_sod_line = $det->ssd_sod_line;
        $hist->ssh_sod_part = $det->ssd_sod_part;
        $hist->ssh_sod_desc = $det->ssd_sod_desc;
        $hist->ssh_uom = $det->ssd_uom;
        $hist->ssh_sod_qty_ord = $det->ssd_sod_qty_ord;
        $hist->ssh_sod_qty_pick = $det->ssd_sod_qty_pick;
        $hist->ssh_sod_lot = $det->ssd_sod_lot;
        $hist->ssh_status_det = $det->ssd_status;
        $hist->ssh_site = $loc->ssl_site;
        $hist->ssh_warehouse = $loc->ssl_warehouse;
        $hist->ssh_location = $loc->ssl_location;
        $hist->ssh_lotserial = $loc->ssl_lotserial;
        $hist->ssh_level = $loc->ssl_level;
        $hist->ssh_bin = $loc->ssl_bin;
        $hist->ssh_qty_to_pick = $loc->ssl_qty_to_pick;
        $hist->ssh_action = $action;
        $hist->ssh_reason = $reason;
        $hist->created_by = Auth::user()->name;
        $hist->save();
    }
    public function updateShipmentSchedule($approver, $idSsm, $shipmentSchedule)
    {
        DB::beginTransaction();

        try {
            $shipmentScheduleMstr = ShipmentScheduleMstr::with([
                'getShipmentScheduleDetail.shipmentScheduleLoc'
            ])->findOrFail($idSsm);

            if (!in_array($shipmentScheduleMstr->ssm_status, [
                'Draft',
                'Rejected',
                'Re-submit'
            ])) {
                throw new \Exception(
                    "Shipment schedule dengan status {$shipmentScheduleMstr->ssm_status} tidak dapat diedit."
                );
            }

            $shipmentScheduleMstr->ssm_status = 'Waiting for approval';
            $shipmentScheduleMstr->updated_by = Auth::user()->id;
            $shipmentScheduleMstr->save();

            $keptDetailIds = [];

            foreach ($shipmentSchedule as $order) {
                $sodNbr = $order['sodNbr'] ?? null;
                $sodLine = $order['sodLine'] ?? null;

                $shipmentScheduleDet = ShipmentScheduleDet::where(
                    'ssm_id',
                    $shipmentScheduleMstr->id
                )
                    ->where('ssd_sod_nbr', $sodNbr)
                    ->where('ssd_sod_line', $sodLine)
                    ->first();

                if (!$shipmentScheduleDet) {
                    $shipmentScheduleDet = new ShipmentScheduleDet();
                    $shipmentScheduleDet->ssm_id = $shipmentScheduleMstr->id;
                    $shipmentScheduleDet->created_by = Auth::user()->id;
                } else {
                    $shipmentScheduleDet->updated_by = Auth::user()->id;
                }

                $shipmentScheduleDet->ssd_sod_nbr = $sodNbr;
                $shipmentScheduleDet->ssd_sod_site = $order['sodSite'] ?? null;
                $shipmentScheduleDet->ssd_sod_shipto = $order['sodShip'] ?? null;
                $shipmentScheduleDet->ssd_sod_line = $sodLine;
                $shipmentScheduleDet->ssd_sod_part = $order['sodPart'] ?? null;
                $shipmentScheduleDet->ssd_sod_desc = $order['sodDesc'] ?? null;
                $shipmentScheduleDet->ssd_sod_lot = $order['sodLot'] ?? null;
                $shipmentScheduleDet->ssd_uom = $order['ssdUom']
                    ?? $order['sodUom']
                    ?? null;
                $shipmentScheduleDet->ssd_sod_qty_ord =
                    $this->toNumber($order['totalToPickQty'] ?? 0);

                $shipmentScheduleDet->ssd_status = 'Pending';
                $shipmentScheduleDet->save();

                $keptDetailIds[] = $shipmentScheduleDet->id;

                $keptLocationIds = [];

                foreach ($order['locations'] ?? [] as $location) {
                    $site = $location['site'] ?? '0';
                    $warehouse = $location['wh'] ?? '0';
                    $loc = $location['loc'] ?? '0';
                    $lot = $location['lot'] ?? '';
                    $level = $location['level'] ?? '0';
                    $bin = $location['bin'] ?? '0';

                    $newQtyPick = $this->toNumber(
                        $location['qtyPick'] ?? 0
                    );

                    $newQtyToPick = $this->toNumber(
                        $location['qtyToPick'] ?? 0
                    );

                    $shipmentScheduleLocation =
                        ShipmentScheduleLoc::where(
                            'ssd_id',
                            $shipmentScheduleDet->id
                        )
                        ->where('ssl_site', $site)
                        ->where('ssl_warehouse', $warehouse)
                        ->where('ssl_location', $loc)
                        ->where('ssl_lotserial', $lot)
                        ->where('ssl_level', $level)
                        ->where('ssl_bin', $bin)
                        ->first();

                    if (!$shipmentScheduleLocation) {
                        if ($newQtyPick <= 0) {
                            continue;
                        }

                        $shipmentScheduleLocation = new ShipmentScheduleLoc();
                        $shipmentScheduleLocation->ssd_id =
                            $shipmentScheduleDet->id;
                        $shipmentScheduleLocation->created_by =
                            Auth::user()->id;

                        $oldQtyPick = 0;
                    } else {
                        $shipmentScheduleLocation->updated_by =
                            Auth::user()->id;

                        $oldQtyPick = $this->toNumber(
                            $shipmentScheduleLocation->ssl_qty_pick
                        );
                    }

                    $delta = $newQtyPick - $oldQtyPick;

                    $shipmentScheduleLocation->ssl_site = $site;
                    $shipmentScheduleLocation->ssl_warehouse = $warehouse;
                    $shipmentScheduleLocation->ssl_location = $loc;
                    $shipmentScheduleLocation->ssl_lotserial = $lot;
                    $shipmentScheduleLocation->ssl_level = $level;
                    $shipmentScheduleLocation->ssl_bin = $bin;
                    $shipmentScheduleLocation->ssl_qty_to_pick =
                        $newQtyToPick;
                    $shipmentScheduleLocation->ssl_qty_pick =
                        $newQtyPick;

                    $shipmentScheduleLocation->save();

                    $keptLocationIds[] = $shipmentScheduleLocation->id;

                    if ($delta != 0) {
                        $this->moveInventory(
                            $shipmentScheduleDet->ssd_sod_part,
                            $shipmentScheduleLocation,
                            $delta
                        );

                        $this->writeTransactionHistory(
                            $shipmentScheduleDet,
                            $shipmentScheduleLocation,
                            'Shipment Preparation Edit',
                            'Shipment Preparation Edit',
                            $delta
                        );
                    }
                }

                $oldLocations = ShipmentScheduleLoc::where(
                    'ssd_id',
                    $shipmentScheduleDet->id
                )
                    ->when(
                        count($keptLocationIds) > 0,
                        function ($query) use ($keptLocationIds) {
                            $query->whereNotIn('id', $keptLocationIds);
                        }
                    )
                    ->get();

                foreach ($oldLocations as $oldLocation) {
                    $oldQty = $this->toNumber(
                        $oldLocation->ssl_qty_pick
                    );

                    if ($oldQty > 0) {
                        $this->moveInventory(
                            $shipmentScheduleDet->ssd_sod_part,
                            $oldLocation,
                            -$oldQty
                        );

                        $this->writeTransactionHistory(
                            $shipmentScheduleDet,
                            $oldLocation,
                            'Shipment Preparation Edit',
                            'Remove Location',
                            $oldQty
                        );
                    }

                    $oldLocation->delete();
                }

                $totalPicked = ShipmentScheduleLoc::where(
                    'ssd_id',
                    $shipmentScheduleDet->id
                )->sum('ssl_qty_pick');

                $shipmentScheduleDet->ssd_sod_qty_pick = $totalPicked;
                $shipmentScheduleDet->save();
            }

            $oldDetails = ShipmentScheduleDet::with(
                'shipmentScheduleLoc'
            )
                ->where('ssm_id', $shipmentScheduleMstr->id)
                ->when(
                    count($keptDetailIds) > 0,
                    function ($query) use ($keptDetailIds) {
                        $query->whereNotIn('id', $keptDetailIds);
                    }
                )
                ->get();

            foreach ($oldDetails as $oldDetail) {
                foreach ($oldDetail->shipmentScheduleLoc as $oldLocation) {
                    $oldQty = $this->toNumber(
                        $oldLocation->ssl_qty_pick
                    );

                    if ($oldQty > 0) {
                        $this->moveInventory(
                            $oldDetail->ssd_sod_part,
                            $oldLocation,
                            -$oldQty
                        );

                        $this->writeTransactionHistory(
                            $oldDetail,
                            $oldLocation,
                            'Shipment Preparation Edit',
                            'Remove Detail',
                            $oldQty
                        );
                    }

                    $oldLocation->delete();
                }

                $oldDetail->delete();
            }

            $approval = ShipmentScheduleApproval::where(
                'ssm_id',
                $shipmentScheduleMstr->id
            )
                ->where('ssa_sequence', 1)
                ->first();

            if (!$approval) {
                $approval = new ShipmentScheduleApproval();
                $approval->ssm_id = $shipmentScheduleMstr->id;
                $approval->ssa_sequence = 1;
                $approval->created_by = Auth::user()->id;
            }

            $approval->ssa_user_approver = $approver;
            $approval->ssa_status = 'Waiting for confirmation';
            $approval->ssa_reason = null;
            $approval->updated_by = Auth::user()->id;
            $approval->save();

            DB::commit();

            return true;
        } catch (\Exception $err) {
            DB::rollBack();

            Log::channel('shipmentSchedule')->error($err);

            return false;
        }
    }
}

//     public function deleteShipmentSchedule($shipmentScheduleMstr)
//     {
//         DB::beginTransaction();

//         try {
//             foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentDetail) {
//                 foreach ($shipmentDetail->getShipmentScheduleLocation as $locationDetail) {
//                     $this->writeScheduleHistory(
//                         $shipmentScheduleMstr,
//                         $shipmentDetail,
//                         $locationDetail,
//                         'Delete'
//                     );

//                     $locationDetail->delete();
//                 }

//                 $shipmentDetail->delete();
//             }

//             // Approval ikut dihapus supaya tidak bentrok dengan FK (onDelete restrict)
//             ShipmentScheduleApproval::where('ssm_id', $shipmentScheduleMstr->id)->delete();

//             $shipmentScheduleMstr->delete();

//             DB::commit();

//             return true;
//         } catch (\Exception $err) {
//             DB::rollBack();
//             Log::channel('shipmentSchedule')->error($err);

//             return false;
//         }
//     }

//     public function updateShipmentSchedule($idShipmentScheduleMstr, $salesOrders)
//     {
//         DB::beginTransaction();

//         try {
//             $shipmentScheduleMstr = ShipmentScheduleMstr::findOrFail($idShipmentScheduleMstr);

//             // Kalau rejected, balikin jadi Re-submit
//             if ($shipmentScheduleMstr->ssm_status == 'Rejected') {
//                 $shipmentScheduleMstr->ssm_status = 'Re-submit';
//                 $shipmentScheduleMstr->updated_by = Auth::user()->id;
//                 $shipmentScheduleMstr->save();
//             }

//             $keptDetIds = [];
//             $keptLocIdsByDet = [];

//             // Cek tiap SO + line: kalau ada update, kalau tidak ada create baru
//             foreach ($salesOrders as $salesOrder) {
//                 $shipmentScheduleDet = ShipmentScheduleDet::where('ssm_id', $idShipmentScheduleMstr)
//                     ->where('ssd_sod_nbr', $salesOrder['so_id'])
//                     ->where('ssd_sod_line', $salesOrder['line'])
//                     ->first();

//                 if ($shipmentScheduleDet) {
//                     $shipmentScheduleDet->updated_by = Auth::user()->id;
//                 } else {
//                     $shipmentScheduleDet = new ShipmentScheduleDet();
//                     $shipmentScheduleDet->ssm_id = $idShipmentScheduleMstr;
//                     $shipmentScheduleDet->ssd_status = 'New';
//                     $shipmentScheduleDet->created_by = Auth::user()->id;
//                 }

//                 $shipmentScheduleDet->ssd_sod_nbr = $salesOrder['so_id'];
//                 $shipmentScheduleDet->ssd_sod_site = $salesOrder['site'];
//                 $shipmentScheduleDet->ssd_sod_shipto = $salesOrder['ship'];
//                 $shipmentScheduleDet->ssd_sod_line = $salesOrder['line'];
//                 $shipmentScheduleDet->ssd_sod_part = $salesOrder['part'];
//                 $shipmentScheduleDet->ssd_sod_desc = $salesOrder['desc'];
//                 $shipmentScheduleDet->ssd_uom = $salesOrder['uom'];
//                 $shipmentScheduleDet->ssd_sod_qty_ord = $salesOrder['qty'];
//                 $shipmentScheduleDet->ssd_sod_qty_pick = 0;
//                 $shipmentScheduleDet->ssd_sent_to_qad = 'No';
//                 $shipmentScheduleDet->save();

//                 $keptDetIds[] = $shipmentScheduleDet->id;
//                 $keptLocIdsByDet[$shipmentScheduleDet->id] = [];

//                 // Tiap SO line bisa punya banyak location
//                 foreach ($salesOrder['selected_locations'] as $detailLocation) {
//                     $action = 'Create';

//                     $shipmentScheduleLocation = ShipmentScheduleLoc::where('ssd_id', $shipmentScheduleDet->id)
//                         ->where('ssl_location', $detailLocation['location'])
//                         ->where('ssl_lotserial', $detailLocation['lot'])
//                         ->where('ssl_level', $detailLocation['level'])
//                         ->where('ssl_warehouse', $detailLocation['warehouse'])
//                         ->where('ssl_bin', $detailLocation['bin'])
//                         ->where('ssl_site', $detailLocation['site'])
//                         ->first();

//                     if (! $shipmentScheduleLocation) {
//                         $shipmentScheduleLocation = new ShipmentScheduleLoc();
//                         $shipmentScheduleLocation->ssd_id = $shipmentScheduleDet->id;
//                         $shipmentScheduleLocation->created_by = Auth::user()->id;
//                     } else {
//                         $action = 'Update';
//                         $shipmentScheduleLocation->updated_by = Auth::user()->id;
//                     }

//                     $shipmentScheduleLocation->ssl_site = $detailLocation['site'];
//                     $shipmentScheduleLocation->ssl_warehouse = $detailLocation['warehouse'];
//                     $shipmentScheduleLocation->ssl_location = $detailLocation['location'];
//                     $shipmentScheduleLocation->ssl_lotserial = $detailLocation['lot'];
//                     $shipmentScheduleLocation->ssl_level = $detailLocation['level'];
//                     $shipmentScheduleLocation->ssl_bin = $detailLocation['bin'];
//                     $shipmentScheduleLocation->ssl_qty_to_pick = $detailLocation['qty_to_pick'];
//                     $shipmentScheduleLocation->save();

//                     $keptLocIdsByDet[$shipmentScheduleDet->id][] = $shipmentScheduleLocation->id;

//                     $this->writeScheduleHistory(
//                         $shipmentScheduleMstr,
//                         $shipmentScheduleDet,
//                         $shipmentScheduleLocation,
//                         $action
//                     );
//                 }
//             }

//             // Hapus line yang sudah tidak ada di request
//             $removedDets = ShipmentScheduleDet::with('getShipmentScheduleLocation')
//                 ->where('ssm_id', $idShipmentScheduleMstr)
//                 ->whereNotIn('id', $keptDetIds)
//                 ->get();

//             foreach ($removedDets as $removedDet) {
//                 foreach ($removedDet->getShipmentScheduleLocation as $locationDetail) {
//                     $this->writeScheduleHistory(
//                         $shipmentScheduleMstr,
//                         $removedDet,
//                         $locationDetail,
//                         'Deleted'
//                     );

//                     $locationDetail->delete();
//                 }

//                 $removedDet->delete();
//             }

//             // Hapus lokasi yang di-uncheck pada line yang masih ada
//             foreach ($keptLocIdsByDet as $detId => $locIds) {
//                 $shipmentScheduleDet = ShipmentScheduleDet::find($detId);

//                 $uncheckedLocations = ShipmentScheduleLoc::where('ssd_id', $detId)
//                     ->whereNotIn('id', $locIds)
//                     ->get();

//                 foreach ($uncheckedLocations as $unchecked) {
//                     $this->writeScheduleHistory(
//                         $shipmentScheduleMstr,
//                         $shipmentScheduleDet,
//                         $unchecked,
//                         'Deleted'
//                     );

//                     $unchecked->delete();
//                 }
//             }

//             DB::commit();

//             return true;
//         } catch (Exception $err) {
//             DB::rollBack();
//             Log::channel('shipmentSchedule')->error($err);

//             return false;
//         }
//     }

//     public function submitPacking($approver, $shipperNumber, $items)
//     {
//         DB::beginTransaction();

//         try {
//             $shipperNumber = trim((string) $shipperNumber);

//             if ($shipperNumber === '' || empty($items) || empty($approver)) {
//                 throw new \Exception('Shipper number, approver, dan detail wajib diisi.');
//             }

//             $shipmentScheduleMstr = ShipmentScheduleMstr::where('ssm_shipper_nbr', $shipperNumber)->first();

//             if (! $shipmentScheduleMstr) {
//                 $runningNumberServices = new RunningNumberServices();

//                 $shipmentScheduleMstr = new ShipmentScheduleMstr();
//                 $shipmentScheduleMstr->ssm_number = $runningNumberServices->getRunningNumberShipmentSchedule();
//                 $shipmentScheduleMstr->ssm_shipper_nbr = $shipperNumber;
//                 $shipmentScheduleMstr->ssm_status = 'Draft';
//                 $shipmentScheduleMstr->created_by = Auth::user()->id;
//                 $shipmentScheduleMstr->save();
//             } elseif ($shipmentScheduleMstr->ssm_status !== 'Draft') {
//                 throw new \Exception(
//                     "Shipper {$shipperNumber} berstatus {$shipmentScheduleMstr->ssm_status}, tidak bisa disubmit ulang."
//                 );
//             }

//             foreach ($items as $item) {
//                 // Detail dicocokkan lewat shipper + line + part (id dari Flutter tidak dipakai)
//                 $shipmentScheduleDet = ShipmentScheduleDet::where('ssm_id', $shipmentScheduleMstr->id)
//                     ->where('ssd_sod_nbr', $shipperNumber)
//                     ->where('ssd_sod_line', $item['sodLine'])
//                     ->where('ssd_sod_part', $item['sodPart'])
//                     ->first();

//                 if (! $shipmentScheduleDet) {
//                     $shipmentScheduleDet = new ShipmentScheduleDet();
//                     $shipmentScheduleDet->ssm_id = $shipmentScheduleMstr->id;
//                     $shipmentScheduleDet->created_by = Auth::user()->id;
//                 } else {
//                     $shipmentScheduleDet->updated_by = Auth::user()->id;
//                 }

//                 $shipmentScheduleDet->ssd_sod_nbr = $shipperNumber;
//                 $shipmentScheduleDet->ssd_sod_site = $item['sodSite'];
//                 $shipmentScheduleDet->ssd_sod_shipto = $item['sodShip'] ?? '';
//                 $shipmentScheduleDet->ssd_sod_line = $item['sodLine'];
//                 $shipmentScheduleDet->ssd_sod_part = $item['sodPart'];
//                 $shipmentScheduleDet->ssd_sod_desc = $item['sodDesc'] ?? null;
//                 $shipmentScheduleDet->ssd_sod_lot = $item['sodLot'] ?? null;
//                 $shipmentScheduleDet->ssd_sod_qty_ord = $this->toNumber($item['totalToPickQty'] ?? null);
//                 $shipmentScheduleDet->ssd_status = 'Pending';
//                 $shipmentScheduleDet->ssd_sent_to_qad = 'No';
//                 $shipmentScheduleDet->save();

//                 $totalPicked = 0.0;

//                 foreach ($item['locations'] ?? [] as $location) {
//                     $newQty = $this->toNumber($location['qtyPick'] ?? null);
//                     $totalPicked += $newQty;

//                     $site = $location['site'];
//                     $wh = $location['wh'] ?? '0';
//                     $loc = $location['loc'] ?? '0';
//                     $lot = $location['lot'];
//                     $level = $location['level'] ?? '0';
//                     $bin = $location['bin'] ?? '0';

//                     $shipmentScheduleLocation = ShipmentScheduleLoc::where('ssd_id', $shipmentScheduleDet->id)
//                         ->where('ssl_site', $site)
//                         ->where('ssl_warehouse', $wh)
//                         ->where('ssl_location', $loc)
//                         ->where('ssl_lotserial', $lot)
//                         ->where('ssl_level', $level)
//                         ->where('ssl_bin', $bin)
//                         ->first();

//                     // Lokasi belum ada dan qty 0 -> lewati
//                     if (! $shipmentScheduleLocation && $newQty == 0) {
//                         continue;
//                     }

//                     if (! $shipmentScheduleLocation) {
//                         $shipmentScheduleLocation = new ShipmentScheduleLoc();
//                         $shipmentScheduleLocation->ssd_id = $shipmentScheduleDet->id;
//                         $shipmentScheduleLocation->created_by = Auth::user()->id;
//                     } else {
//                         $shipmentScheduleLocation->updated_by = Auth::user()->id;
//                     }

//                     // Pakai selisih supaya submit ulang tidak memotong inventory dua kali
//                     $oldQty = (float) ($shipmentScheduleLocation->ssl_qty_pick ?? 0);
//                     $delta = $newQty - $oldQty;

//                     $shipmentScheduleLocation->ssl_site = $site;
//                     $shipmentScheduleLocation->ssl_warehouse = $wh;
//                     $shipmentScheduleLocation->ssl_location = $loc;
//                     $shipmentScheduleLocation->ssl_lotserial = $lot;
//                     $shipmentScheduleLocation->ssl_level = $level;
//                     $shipmentScheduleLocation->ssl_bin = $bin;
//                     $shipmentScheduleLocation->ssl_qty_to_pick = $this->toNumber($location['qtyToPick'] ?? null);
//                     $shipmentScheduleLocation->ssl_qty_pick = $newQty;
//                     $shipmentScheduleLocation->save();

//                     if ($delta != 0) {
//                         $this->moveInventory(
//                             $shipmentScheduleDet->ssd_sod_part,
//                             $shipmentScheduleLocation,
//                             $delta
//                         );

//                         $this->writeTransactionHistory(
//                             $shipmentScheduleDet,
//                             $shipmentScheduleLocation,
//                             'Shipment Preparation',
//                             'Shipment Preparation',
//                             $delta
//                         );
//                     }
//                 }

//                 // Total picked dihitung ulang di server, tidak percaya nilai dari client
//                 $shipmentScheduleDet->ssd_sod_qty_pick = $totalPicked;
//                 $shipmentScheduleDet->save();

//                 // History per lokasi (setelah qty_pick detail final)
//                 foreach ($shipmentScheduleDet->getShipmentScheduleLocation as $savedLocation) {
//                     $this->writeScheduleHistory(
//                         $shipmentScheduleMstr,
//                         $shipmentScheduleDet,
//                         $savedLocation,
//                         'Submit Packing'
//                     );
//                 }
//             }

//             $approval = ShipmentScheduleApproval::where('ssm_id', $shipmentScheduleMstr->id)
//                 ->where('ssa_sequence', 1)
//                 ->first();

//             if (! $approval) {
//                 $approval = new ShipmentScheduleApproval();
//                 $approval->ssm_id = $shipmentScheduleMstr->id;
//                 $approval->ssa_sequence = 1;
//                 $approval->created_by = Auth::user()->id;
//             }

//             $approval->ssa_user_approver = $approver;
//             $approval->ssa_status = 'Waiting for confirmation';
//             $approval->ssa_reason = null;
//             $approval->updated_by = Auth::user()->id;
//             $approval->save();

//             $shipmentScheduleMstr->ssm_status = 'Waiting for approval';
//             $shipmentScheduleMstr->updated_by = Auth::user()->id;
//             $shipmentScheduleMstr->save();

//             DB::commit();

//             return true;
//         } catch (\Exception $err) {
//             DB::rollBack();
//             Log::channel('shipmentSchedule')->error($err);

//             return false;
//         }
//     }

//     public function rejectPacking($idSsm, $reason)
//     {
//         DB::beginTransaction();

//         try {
//             $shipmentScheduleMstr = ShipmentScheduleMstr::with('getShipmentScheduleDetail.getShipmentScheduleLocation')
//                 ->findOrFail($idSsm);

//             $approval = $this->getApprovalForAction($shipmentScheduleMstr);

//             $approval->ssa_status = 'Pending';
//             $approval->ssa_reason = $reason;
//             $approval->updated_by = Auth::user()->id;
//             $approval->save();

//             foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentScheduleDet) {
//                 foreach ($shipmentScheduleDet->getShipmentScheduleLocation as $shipmentScheduleLocation) {
//                     $qtyPick = (float) ($shipmentScheduleLocation->ssl_qty_pick ?? 0);

//                     if ($qtyPick <= 0) {
//                         continue;
//                     }

//                     // Kembalikan stok: shp -> wrh
//                     $this->moveInventory($shipmentScheduleDet->ssd_sod_part, $shipmentScheduleLocation, -$qtyPick);

//                     $this->writeTransactionHistory(
//                         $shipmentScheduleDet,
//                         $shipmentScheduleLocation,
//                         'Shipment Reject',
//                         $reason,
//                         $qtyPick
//                     );

//                     $shipmentScheduleLocation->ssl_qty_pick = 0;
//                     $shipmentScheduleLocation->updated_by = Auth::user()->id;
//                     $shipmentScheduleLocation->save();

//                     $this->writeScheduleHistory(
//                         $shipmentScheduleMstr,
//                         $shipmentScheduleDet,
//                         $shipmentScheduleLocation,
//                         'Reject Packing',
//                         $reason
//                     );
//                 }

//                 $shipmentScheduleDet->ssd_sod_qty_pick = 0;
//                 $shipmentScheduleDet->ssd_status = 'Pending';
//                 $shipmentScheduleDet->updated_by = Auth::user()->id;
//                 $shipmentScheduleDet->save();
//             }

//             $shipmentScheduleMstr->ssm_status = 'Draft';
//             $shipmentScheduleMstr->updated_by = Auth::user()->id;
//             $shipmentScheduleMstr->save();

//             DB::commit();

//             return true;
//         } catch (\Exception $err) {
//             DB::rollBack();
//             Log::channel('shipmentSchedule')->error($err);

//             return false;
//         }
//     }



//     private function toNumber($value): float
//     {
//         if ($value === null || $value === '') {
//             return 0.0;
//         }

//         if (is_numeric($value)) {
//             return (float) $value;
//         }

//         $clean = str_replace(',', '', trim((string) $value));

//         return is_numeric($clean) ? (float) $clean : 0.0;
//     }

//     private function getApprovalForAction(ShipmentScheduleMstr $mstr): ShipmentScheduleApproval
//     {
//         if ($mstr->ssm_status !== 'Waiting for approval') {
//             throw new \Exception('Status shipment schedule bukan Waiting for approval.');
//         }

//         $approval = ShipmentScheduleApproval::where('ssm_id', $mstr->id)
//             ->where('ssa_sequence', 1)
//             ->firstOrFail();

//         $allowed = [(string) $approval->ssa_user_approver];
//         if ($approval->ssa_alt_user_approver) {
//             $allowed[] = (string) $approval->ssa_alt_user_approver;
//         }

//         if (! in_array((string) Auth::user()->id, $allowed, true)) {
//             throw new \Exception('User bukan approver.');
//         }

//         return $approval;
//     }

//     /**
//      * $qty > 0 : wrh -> shp (picking)
//      * $qty < 0 : shp -> wrh (reject / koreksi turun)
//      */


// }
