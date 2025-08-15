<?php

use App\Http\Controllers\API\APIController;
use App\Http\Controllers\API\APIPurchaseOrderApprovalController;
use App\Http\Controllers\API\APIPurchaseOrderController;
use App\Http\Controllers\API\APIQualityInfoController;
use App\Http\Controllers\API\ShipmentSchedule\APIShipmentScheduleController;
use App\Http\Controllers\API\APITrasnferStockController;
use App\Http\Controllers\API\APIWorkOrderController;
use App\Http\Controllers\API\PackingReplenishment\APIPackingReplenishmentController;
use App\Http\Controllers\API\ShipperConfirm\APIShipperConfirmController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', [APIController::class, 'login']);
Route::post('changepass', [APIController::class, 'resetPass']);

Route::middleware(['auth:api', 'token.api'])->group(function () {
    // PO
    Route::get('getDataPO', [APIPurchaseOrderController::class, 'index']);
    Route::post('saveReceipt', [APIPurchaseOrderController::class, 'saveReceipt']);
    Route::post('saveEditReceipt', [APIPurchaseOrderController::class, 'saveEditReceipt']);
    Route::get('getPoApproval', [APIPurchaseOrderApprovalController::class, 'getPoApproval']);

    // PO Approval
    Route::get('getDataApprovalPO', [APIPurchaseOrderApprovalController::class, 'index']);
    Route::post('approveRejectReceipt', [APIPurchaseOrderApprovalController::class, 'approveRejectReceipt']);

    // PO Quality Info
    Route::get('getReceiptSeenBy', [APIQualityInfoController::class, 'index']);
    Route::post('updateReceiptSeenBy', [APIQualityInfoController::class, 'store']);

    // PO Transfer WMS
    Route::get('getTransferList', [APITrasnferStockController::class, 'index']);
    Route::get('getStockItemBin', [APITrasnferStockController::class, 'getStockItemBin']);
    Route::post('saveTransfer', [APITrasnferStockController::class, 'saveTransfer']);

    // WSA PO
    Route::post('wsaDataPO', [APIPurchaseOrderController::class, 'wsaDataPO']);
    Route::post('wsaLotBatch', [APIPurchaseOrderController::class, 'wsaLotBatch']);
    Route::post('wsaPenyimpanan', [APIPurchaseOrderController::class, 'wsaPenyimpanan']);
    Route::post('wsaWarehouse', [APIPurchaseOrderController::class, 'wsaWarehouse']);
    Route::post('wsaLevel', [APIPurchaseOrderController::class, 'wsaLevel']);
    Route::post('wsaBin', [APIPurchaseOrderController::class, 'wsaBin']);


    // Shipment Schedule
    Route::get('getShipmentSchedule', [APIShipmentScheduleController::class, 'index']);
    Route::post('wsaCustomer', [APIShipmentScheduleController::class, 'wsaCustomer']);
    Route::post('wsaSalesOrder', [APIShipmentScheduleController::class, 'wsaSalesOrder']);
    Route::post('wsaInventoryDetail', [APIShipmentScheduleController::class, 'wsaInventoryDetail']);
    Route::post('saveShipmentSchedule', [APIShipmentScheduleController::class, 'store']);
    Route::post('deleteShipmentSchedule', [APIShipmentScheduleController::class, 'delete']);
    Route::get('editShipmentSchedule/{id}', [APIShipmentScheduleController::class, 'edit']);
    Route::put('updateShipmentSchedule/{id}', [APIShipmentScheduleController::class, 'update']);
    Route::get('getDefaultSampleLoc', [APITrasnferStockController::class, 'getDefaultSampleLoc']);

    // Packing Replenishment
    Route::get('getPackingReplenishment', [APIPackingReplenishmentController::class, 'index']);
    Route::get('listShipmentSchedule', [APIPackingReplenishmentController::class, 'listShipmentSchedule']);
    Route::post('savePackingReplenishment', [APIPackingReplenishmentController::class, 'store']);

    // Shipper Confirm
    Route::get('getShipperConfirmation', [APIShipperConfirmController::class, 'index']);
    Route::post('wsaDataWo', [APIWorkOrderController::class, 'wsaDataWo']);
});
    // WSA Picklist
