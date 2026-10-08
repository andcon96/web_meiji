<?php

namespace App\Services;

use App\Models\API\ShipmentSchedule\ShipmentScheduleDet;
use App\Models\API\ShipmentSchedule\ShipmentScheduleHist;
use App\Models\API\ShipmentSchedule\ShipmentScheduleLoc;
use App\Models\API\ShipmentSchedule\ShipmentScheduleMstr;
use App\Models\API\ShipperConfirm\ShipperConfirm;
use App\Models\API\TransactionHistory;
use App\Models\API\xxinvDet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmShipmentServices
{
    public function confirmShipment(Request $request, $confirmApproval, $reason, $activeConnection)
    {
        DB::beginTransaction();

        try {
            $shipperConfirm = ShipperConfirm::where('id', $confirmApproval['id'])->lockForUpdate()->first();

            if (! $shipperConfirm) {
                DB::rollBack();
                Log::channel('confirmShipment')->info('ShipperConfirm not found for id: ' . $confirmApproval['id']);

                return false;
            }

            if ($shipperConfirm->sc_status !== 'Waiting for confirmation') {
                DB::rollBack();
                Log::channel('confirmShipment')->info('Duplicate confirmShipment request ignored. ShipperConfirm id: ' . $confirmApproval['id'] . ', current status: ' . $shipperConfirm->sc_status);

                return true;
            }

            // ssm_id diambil dari database, bukan dari payload client
            $ssmId = $shipperConfirm->ssm_id;

            $anotherApproval = ShipperConfirm::where('ssm_id', $ssmId)
                ->where('id', '!=', $shipperConfirm->id)
                ->where('sc_status', 'Waiting for confirmation')
                ->lockForUpdate()
                ->first();

            $shipperConfirm->sc_status = 'Approved';
            $shipperConfirm->updated_by = Auth::user()->id;
            $shipperConfirm->sc_reason = $reason;
            $shipperConfirm->save();

            Log::channel('confirmShipment')->info(json_encode($request->all()));

            // Kalau masih ada approver lain yang belum konfirmasi, tunggu dulu
            if (! $anotherApproval) {
                $shipmentScheduleMstr = ShipmentScheduleMstr::with('getShipmentScheduleDetail.shipmentScheduleLoc')
                    ->findOrFail($ssmId);

                $shipmentScheduleMstr->ssm_status = 'Shipped';
                $shipmentScheduleMstr->updated_by = Auth::user()->id;
                $shipmentScheduleMstr->save();

                foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentScheduleDet) {
                    foreach ($shipmentScheduleDet->shipmentScheduleLoc as $shipmentScheduleLocation) {
                        $qtyPick = (float) ($shipmentScheduleLocation->ssl_qty_pick ?? 0);

                        if ($qtyPick <= 0) {
                            continue;
                        }

                        // History dicatat sebelum qty pick di-nol-kan
                        // $this->writeScheduleHistory(
                        //     $shipmentScheduleMstr,
                        //     $shipmentScheduleDet,
                        //     $shipmentScheduleLocation,
                        //     'Confirm Shipment',
                        //     $reason
                        // );

                        // Stok keluar: shp berkurang, qty on hand berkurang
                        $this->updateInventory($shipmentScheduleDet, $shipmentScheduleLocation, [
                            'xxinv_qty_shp' => DB::raw('xxinv_qty_shp - ' . $qtyPick),
                            'xxinv_qtyoh' => DB::raw('xxinv_qtyoh - ' . $qtyPick),
                        ]);

                        $this->writeTransactionHistory(
                            $shipmentScheduleDet,
                            $shipmentScheduleLocation,
                            'Shipment Confirm',
                            $reason ?: 'Shipment Confirm',
                            $qtyPick
                        );

                        $shipmentScheduleLocation->ssl_qty_pick = 0;
                        $shipmentScheduleLocation->updated_by = Auth::user()->id;
                        $shipmentScheduleLocation->save();
                    }

                    if ((float) $shipmentScheduleDet->ssd_sod_qty_pick < (float) $shipmentScheduleDet->ssd_sod_qty_ord) {
                        $shipmentScheduleDet->ssd_status = 'Shipped (Partial)';
                    } else {
                        $shipmentScheduleDet->ssd_status = 'Shipped (Full)';
                    }

                    $shipmentScheduleDet->updated_by = Auth::user()->id;
                    $shipmentScheduleDet->save();
                }

                Log::info('CALL confirmShipment', [
                    'ssm_id' => $ssmId,
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

    public function rejectShipment(Request $request, $confirmApproval, $reason, $activeConnection)
    {
        DB::beginTransaction();

        try {
            $shipperConfirm = ShipperConfirm::where('id', $confirmApproval['id'])->lockForUpdate()->first();

            if (! $shipperConfirm) {
                DB::rollBack();
                Log::channel('confirmShipment')->info('ShipperConfirm not found for id: ' . $confirmApproval['id']);

                return false;
            }

            if ($shipperConfirm->sc_status !== 'Waiting for confirmation') {
                DB::rollBack();
                Log::channel('confirmShipment')->info('Duplicate rejectShipment request ignored. ShipperConfirm id: ' . $confirmApproval['id'] . ', current status: ' . $shipperConfirm->sc_status);

                return true;
            }

            $ssmId = $shipperConfirm->ssm_id;

            $anotherApproval = ShipperConfirm::where('ssm_id', $ssmId)
                ->where('id', '!=', $shipperConfirm->id)
                ->where('sc_status', 'Waiting for confirmation')
                ->lockForUpdate()
                ->first();

            $shipperConfirm->sc_status = 'Draft';
            $shipperConfirm->updated_by = Auth::user()->id;
            $shipperConfirm->sc_reason = $reason;
            $shipperConfirm->save();

            Log::channel('confirmShipment')->info(json_encode($request->all()));

            if (! $anotherApproval) {
                $shipmentScheduleMstr = ShipmentScheduleMstr::with('getShipmentScheduleDetail.shipmentScheduleLoc')
                    ->findOrFail($ssmId);

                $shipmentScheduleMstr->ssm_status = 'Draft';
                $shipmentScheduleMstr->updated_by = Auth::user()->id;
                $shipmentScheduleMstr->save();

                foreach ($shipmentScheduleMstr->getShipmentScheduleDetail as $shipmentScheduleDet) {
                    foreach ($shipmentScheduleDet->shipmentScheduleLoc as $shipmentScheduleLocation) {
                        $qtyPick = (float) ($shipmentScheduleLocation->ssl_qty_pick ?? 0);

                        if ($qtyPick <= 0) {
                            continue;
                        }

                        // $this->writeScheduleHistory(
                        //     $shipmentScheduleMstr,
                        //     $shipmentScheduleDet,
                        //     $shipmentScheduleLocation,
                        //     'Reject Shipment',
                        //     $reason
                        // );

                        // Kembalikan stok: shp -> wrh
                        $this->updateInventory($shipmentScheduleDet, $shipmentScheduleLocation, [
                            'xxinv_qty_shp' => DB::raw('xxinv_qty_shp - ' . $qtyPick),
                            'xxinv_qty_wrh' => DB::raw('xxinv_qty_wrh + ' . $qtyPick),
                        ]);

                        $this->writeTransactionHistory(
                            $shipmentScheduleDet,
                            $shipmentScheduleLocation,
                            'Shipment Confirm Reject',
                            $reason ?: 'Shipment Confirm Reject',
                            $qtyPick
                        );

                        $shipmentScheduleLocation->ssl_qty_pick = 0;
                        $shipmentScheduleLocation->updated_by = Auth::user()->id;
                        $shipmentScheduleLocation->save();
                    }

                    $shipmentScheduleDet->ssd_sod_qty_pick = 0;
                    $shipmentScheduleDet->ssd_status = 'Pending';
                    $shipmentScheduleDet->updated_by = Auth::user()->id;
                    $shipmentScheduleDet->save();
                }

                Log::info('CALL rejectShipment', [
                    'ssm_id' => $ssmId,
                    'time' => now(),
                ]);

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

    private function updateInventory(ShipmentScheduleDet $det, ShipmentScheduleLoc $loc, array $values): void
    {
        $affected = xxinvDet::where('xxinv_part', $det->ssd_sod_part)
            ->where('xxinv_lot', $loc->ssl_lotserial)
            ->where('xxinv_bin', $loc->ssl_bin ?? '0')
            ->where('xxinv_level', $loc->ssl_level ?? '0')
            ->update($values);

        if ($affected === 0) {
            throw new \Exception(
                'Inventory tidak ditemukan untuk item ' . $det->ssd_sod_part . ' lot ' . $loc->ssl_lotserial . '.'
            );
        }
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
}
