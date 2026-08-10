<?php

namespace App\Services;

use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationHist;
use App\Models\API\OtherShipmentPreparation\OtherShipmentPreparationMstr;
use App\Models\API\OtherShipmentSchedule\OtherShipmentScheduleHist;
use App\Models\API\OtherTransactionConfirm\OtherTransactionConfirm;
use App\Models\API\xxinvDet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmOtherTransactionServices
{
    public function confirmOtherTransaction(Request $request, $otcApproval, $reason, $activeConnection)
    {
        DB::beginTransaction();

        try {
            $otherTransactionConfirm = OtherTransactionConfirm::where('id', $otcApproval['id'])->lockForUpdate()->first();

            if (! $otherTransactionConfirm) {
                DB::rollBack();
                Log::channel('confirmOtherTransaction')->info('OtherTransactionConfirm not found for id: '.$otcApproval['id']);

                return false;
            }

            if ($otherTransactionConfirm->otc_status !== 'Waiting for confirmation') {
                DB::rollBack();
                Log::channel('confirmOtherTransaction')->info('Duplicate confirmOtherTransaction request ignored. id: '.$otcApproval['id'].', current status: '.$otherTransactionConfirm->otc_status);

                return true;
            }

            $anotherConfirm = OtherTransactionConfirm::where('otpm_id', $otcApproval['otpm_id'])->where('id', '!=', $otcApproval['id'])->where('otc_status', '=', 'Waiting for confirmation')->lockForUpdate()->first();

            $otherTransactionConfirm->otc_status = 'Approved';
            $otherTransactionConfirm->updated_by = Auth::user()->id;
            $otherTransactionConfirm->otc_reason = $reason;
            $otherTransactionConfirm->save();

            Log::channel('confirmOtherTransaction')->info(json_encode($request->all()));

            if (! $anotherConfirm) {
                $dataOSPM = $otcApproval['get_other_shipment_preparation_mstr'];

                $otherShipmentPreparationMstr = OtherShipmentPreparationMstr::with(['getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster'])->find($dataOSPM['id']);

                $otherShipmentPreparationMstr->ospm_status = 'Shipped';
                $otherShipmentPreparationMstr->save();

                $fieldName = 'mji_pack_dock';

                // $wsaServices = new WSAServices();
                // $locationWSA = $wsaServices->wsaGenCode($fieldName);
                // if ($locationWSA[0] == 'false') {
                //     DB::rollBack();

                //     Log::channel('confirmOtherTransaction')->info('Gen code not found');

                //     return false;
                // }

                // $location = $locationWSA[1][0]['t_value'];

                $qxtendServices = new QxtendServices();

                foreach ($otherShipmentPreparationMstr->getOtherShipmentPreparationDet as $preparationDet) {
                    $locationDetail = $preparationDet->getOtherShipmentScheduleLocation;
                    if (! $locationDetail) {
                        continue;
                    }

                    $scheduleDet = $locationDetail->getOtherShipmentScheduleDet;
                    $scheduleMaster = $scheduleDet?->getOtherShipmentScheduleMaster;

                    if ($scheduleMaster) {
                        $scheduleMaster->ossm_status = 'Shipped';
                        $scheduleMaster->save();
                    }

                    if ($scheduleDet) {
                        if ($scheduleDet->ossd_qty_pick < $scheduleDet->ossd_qty_ord) {
                            $scheduleDet->ossd_status = 'Shipped (Partial)';
                        } else {
                            $scheduleDet->ossd_status = 'Shipped (Full)';
                        }
                        $scheduleDet->updated_by = Auth::user()->id;
                        $scheduleDet->save();
                    }

                    // Qxtend Inventory Issue Unplanned
                    $qxtend = $qxtendServices->qxIssueInventoryOtherTransaction($scheduleDet, $locationDetail, $activeConnection);
                    if ($qxtend[0] == false) {
                        DB::rollback();

                        Log::channel('confirmOtherTransaction')->info($qxtend[1]);

                        return [
                            'success' => false,
                            'message' => $qxtend[1],
                        ];
                    }

                    // History Other Shipment Preparation
                    $otherShipmentPreparationHist = new OtherShipmentPreparationHist();
                    $otherShipmentPreparationHist->osph_number = $dataOSPM['ospm_number'];
                    $otherShipmentPreparationHist->osph_item = $scheduleDet->ossd_part ?? null;
                    $otherShipmentPreparationHist->osph_site = $locationDetail->ossl_site;
                    $otherShipmentPreparationHist->osph_warehouse = $locationDetail->ossl_warehouse;
                    $otherShipmentPreparationHist->osph_location = $locationDetail->ossl_location;
                    $otherShipmentPreparationHist->osph_lotserial = $locationDetail->ossl_lotserial;
                    $otherShipmentPreparationHist->osph_level = $locationDetail->ossl_level;
                    $otherShipmentPreparationHist->osph_bin = $locationDetail->ossl_bin;
                    $otherShipmentPreparationHist->osph_qty_to_pick = $locationDetail->ossl_qty_to_pick;
                    $otherShipmentPreparationHist->osph_qty_pick = $locationDetail->ossl_qty_pick;
                    $otherShipmentPreparationHist->osph_status_qad = 'Yes';
                    $otherShipmentPreparationHist->osph_status = $otherShipmentPreparationMstr->ospm_status;
                    $otherShipmentPreparationHist->osph_action = 'Confirm Transaction';
                    $otherShipmentPreparationHist->created_by = Auth::user()->id;
                    $otherShipmentPreparationHist->save();

                    // History Other Shipment Schedule
                    if ($scheduleMaster) {
                        $otherShipmentScheduleHist = new OtherShipmentScheduleHist();
                        $otherShipmentScheduleHist->ossh_number = $scheduleMaster->ossm_number;
                        $otherShipmentScheduleHist->ossh_cust_code = $scheduleMaster->ossm_cust_code;
                        $otherShipmentScheduleHist->ossh_cust_desc = $scheduleMaster->ossm_cust_desc;
                        $otherShipmentScheduleHist->ossh_status_mstr = $scheduleMaster->ossm_status;
                        $otherShipmentScheduleHist->ossd_part = $scheduleDet->ossd_part;
                        $otherShipmentScheduleHist->ossd_desc = $scheduleDet->ossd_desc;
                        $otherShipmentScheduleHist->ossd_uom = $scheduleDet->ossd_uom;
                        $otherShipmentScheduleHist->ossd_qty_ord = $scheduleDet->ossd_qty_ord;
                        $otherShipmentScheduleHist->ossd_qty_pick = $scheduleDet->ossd_qty_pick;
                        $otherShipmentScheduleHist->ossd_status_det = $scheduleDet->ossd_status;
                        $otherShipmentScheduleHist->ossd_sent_to_qad = $scheduleDet->ossd_sent_to_qad;
                        $otherShipmentScheduleHist->ossl_site = $locationDetail->ossl_site;
                        $otherShipmentScheduleHist->ossl_warehouse = $locationDetail->ossl_warehouse;
                        $otherShipmentScheduleHist->ossl_location = $locationDetail->ossl_location;
                        $otherShipmentScheduleHist->ossl_lotserial = $locationDetail->ossl_lotserial;
                        $otherShipmentScheduleHist->ossl_level = $locationDetail->ossl_level;
                        $otherShipmentScheduleHist->ossl_bin = $locationDetail->ossl_bin;
                        $otherShipmentScheduleHist->ossl_qty_to_pick = $locationDetail->ossl_qty_to_pick;
                        $otherShipmentScheduleHist->ossl_qty_pick = $locationDetail->ossl_qty_pick;
                        $otherShipmentScheduleHist->ossl_action = 'Confirm Transaction';
                        $otherShipmentScheduleHist->created_by = Auth::user()->id;
                        $otherShipmentScheduleHist->save();
                    }
                }

                Log::info('CALL confirmOtherTransaction', [
                    'otpm_id' => $otcApproval['otpm_id'],
                    'time' => now(),
                ]);

                Log::channel('confirmOtherTransaction')->info(
                    json_encode([
                        'otcApproval' => $otcApproval,
                        'reason' => $reason,
                    ]),
                );
            }

            DB::commit();

            return true;
        } catch (\Throwable $err) {
            DB::rollBack();
            Log::channel('confirmOtherTransaction')->info($err);

            return false;
        }
    }

    public function rejectOtherTransaction(Request $request, $otcApproval, $reason, $activeConnection)
    {
        DB::beginTransaction();

        try {
            $otherTransactionConfirm = OtherTransactionConfirm::where('id', $otcApproval['id'])->lockForUpdate()->first();

            if (! $otherTransactionConfirm) {
                DB::rollBack();
                Log::channel('confirmOtherTransaction')->info('OtherTransactionConfirm not found for id: '.$otcApproval['id']);

                return false;
            }

            if ($otherTransactionConfirm->otc_status !== 'Waiting for confirmation') {
                DB::rollBack();
                Log::channel('confirmOtherTransaction')->info('Duplicate rejectOtherTransaction request ignored. id: '.$otcApproval['id'].', current status: '.$otherTransactionConfirm->otc_status);

                return true;
            }

            $anotherConfirm = OtherTransactionConfirm::where('otpm_id', $otcApproval['otpm_id'])->where('id', '!=', $otcApproval['id'])->where('otc_status', '=', 'Waiting for confirmation')->lockForUpdate()->first();

            $otherTransactionConfirm->otc_status = 'Draft';
            $otherTransactionConfirm->updated_by = Auth::user()->id;
            $otherTransactionConfirm->otc_reason = $reason;
            $otherTransactionConfirm->save();

            Log::channel('confirmOtherTransaction')->info(json_encode($request->all()));

            if (! $anotherConfirm) {
                $dataOSPM = $otcApproval['get_other_shipment_preparation_mstr'];

                $otherShipmentPreparationMstr = OtherShipmentPreparationMstr::with(['getOtherShipmentPreparationDet.getOtherShipmentScheduleLocation.getOtherShipmentScheduleDet.getOtherShipmentScheduleMaster'])->find($dataOSPM['id']);

                $otherShipmentPreparationMstr->ospm_status = 'Draft';
                $otherShipmentPreparationMstr->save();

                foreach ($otherShipmentPreparationMstr->getOtherShipmentPreparationDet as $preparationDet) {
                    $locationDetail = $preparationDet->getOtherShipmentScheduleLocation;
                    if (! $locationDetail) {
                        continue;
                    }

                    $scheduleDet = $locationDetail->getOtherShipmentScheduleDet;
                    $scheduleMaster = $scheduleDet?->getOtherShipmentScheduleMaster;

                    if ($scheduleMaster) {
                        $scheduleMaster->ossm_status = 'Draft';
                        $scheduleMaster->save();
                    }

                    // === Pengembalian stok (rollback qty pick ke xxinv_qtyoh) ===
                    if ($scheduleDet) {
                        xxinvDet::where('xxinv_part', $scheduleDet->ossd_part)
                            ->where('xxinv_lot', $locationDetail->ossl_lotserial)
                            ->where('xxinv_bin', $locationDetail->ossl_bin)
                            ->where('xxinv_level', $locationDetail->ossl_level)
                            ->increment(
                                'xxinv_qtyoh',
                                (float) $locationDetail->ossl_qty_pick
                            );
                    }

                    $locationDetail->ossl_qty_pick = 0;
                    $locationDetail->save();
                    // === akhir pengembalian stok ===

                    if ($scheduleDet) {
                        $scheduleDet->ossd_status = 'Draft';
                        $scheduleDet->updated_by = Auth::user()->id;
                        $scheduleDet->save();
                    }

                    // History Other Shipment Preparation
                    $otherShipmentPreparationHist = new OtherShipmentPreparationHist();
                    $otherShipmentPreparationHist->osph_number = $dataOSPM['ospm_number'];
                    $otherShipmentPreparationHist->osph_item = $scheduleDet->ossd_part ?? null;
                    $otherShipmentPreparationHist->osph_site = $locationDetail->ossl_site;
                    $otherShipmentPreparationHist->osph_warehouse = $locationDetail->ossl_warehouse;
                    $otherShipmentPreparationHist->osph_location = $locationDetail->ossl_location;
                    $otherShipmentPreparationHist->osph_lotserial = $locationDetail->ossl_lotserial;
                    $otherShipmentPreparationHist->osph_level = $locationDetail->ossl_level;
                    $otherShipmentPreparationHist->osph_bin = $locationDetail->ossl_bin;
                    $otherShipmentPreparationHist->osph_qty_to_pick = $locationDetail->ossl_qty_to_pick;
                    $otherShipmentPreparationHist->osph_qty_pick = $locationDetail->ossl_qty_pick;
                    $otherShipmentPreparationHist->osph_status_qad = 'Yes';
                    $otherShipmentPreparationHist->osph_status = $otherShipmentPreparationMstr->ospm_status;
                    $otherShipmentPreparationHist->osph_action = 'Draft';
                    $otherShipmentPreparationHist->created_by = Auth::user()->id;
                    $otherShipmentPreparationHist->save();

                    // History Other Shipment Schedule
                    if ($scheduleMaster) {
                        $otherShipmentScheduleHist = new OtherShipmentScheduleHist();
                        $otherShipmentScheduleHist->ossh_number = $scheduleMaster->ossm_number;
                        $otherShipmentScheduleHist->ossh_cust_code = $scheduleMaster->ossm_cust_code;
                        $otherShipmentScheduleHist->ossh_cust_desc = $scheduleMaster->ossm_cust_desc;
                        $otherShipmentScheduleHist->ossh_status_mstr = $scheduleMaster->ossm_status;
                        $otherShipmentScheduleHist->ossd_part = $scheduleDet->ossd_part;
                        $otherShipmentScheduleHist->ossd_desc = $scheduleDet->ossd_desc;
                        $otherShipmentScheduleHist->ossd_uom = $scheduleDet->ossd_uom;
                        $otherShipmentScheduleHist->ossd_qty_ord = $scheduleDet->ossd_qty_ord;
                        $otherShipmentScheduleHist->ossd_qty_pick = $scheduleDet->ossd_qty_pick;
                        $otherShipmentScheduleHist->ossd_status_det = $scheduleDet->ossd_status;
                        $otherShipmentScheduleHist->ossd_sent_to_qad = $scheduleDet->ossd_sent_to_qad;
                        $otherShipmentScheduleHist->ossl_site = $locationDetail->ossl_site;
                        $otherShipmentScheduleHist->ossl_warehouse = $locationDetail->ossl_warehouse;
                        $otherShipmentScheduleHist->ossl_location = $locationDetail->ossl_location;
                        $otherShipmentScheduleHist->ossl_lotserial = $locationDetail->ossl_lotserial;
                        $otherShipmentScheduleHist->ossl_level = $locationDetail->ossl_level;
                        $otherShipmentScheduleHist->ossl_bin = $locationDetail->ossl_bin;
                        $otherShipmentScheduleHist->ossl_qty_to_pick = $locationDetail->ossl_qty_to_pick;
                        $otherShipmentScheduleHist->ossl_qty_pick = $locationDetail->ossl_qty_pick;
                        $otherShipmentScheduleHist->ossl_action = 'Draft';
                        $otherShipmentScheduleHist->created_by = Auth::user()->id;
                        $otherShipmentScheduleHist->save();
                    }
                }

                Log::info('CALL rejectOtherTransaction', [
                    'otpm_id' => $otcApproval['otpm_id'],
                    'time' => now(),
                ]);

                Log::channel('confirmOtherTransaction')->info(
                    json_encode([
                        'otcApproval' => $otcApproval,
                        'reason' => $reason,
                    ]),
                );
            }

            DB::commit();

            return true;
        } catch (\Throwable $err) {
            DB::rollBack();
            Log::channel('confirmOtherTransaction')->info($err);

            return false;
        }
    }
}
