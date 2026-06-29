<?php

use App\Http\Controllers\API\APIBarangJadi;
use App\Http\Controllers\API\APIController;
use App\Http\Controllers\API\APIPengembalian;
use App\Http\Controllers\API\APIPicklistShopping;
use App\Http\Controllers\API\APIPurchaseOrderApprovalController;
use App\Http\Controllers\API\APIPurchaseOrderController;
use App\Http\Controllers\API\APIPurchaseOrderRecheckController;
use App\Http\Controllers\API\APIQualityInfoController;
use App\Http\Controllers\API\APISampling;
use App\Http\Controllers\API\APISingleTransfer;
use App\Http\Controllers\API\APITransIssUnpController;
use App\Http\Controllers\API\APITransRctUnpController;
use App\Http\Controllers\API\APITrasnferStockController;
use App\Http\Controllers\API\APIWorkOrderController;
use App\Http\Controllers\API\APIZebraPrinterController;
use App\Http\Controllers\API\OtherShipmentPreparation\APIOtherShipmentPreparationController;
use App\Http\Controllers\API\OtherShipmentSchedule\APIOtherShipmentScheduleController;
use App\Http\Controllers\API\PackingReplenishment\APIPackingReplenishmentController;
use App\Http\Controllers\API\ShipmentSchedule\APIShipmentScheduleController;
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

// Outbound WO
Route::post('getWorkOrderQad', [APIController::class, 'getWorkOrderQad']);

// API DKP
Route::post('sendQxCompIssue', [APIController::class, 'sendQxCompIssue']);

// Get APK Latest Version
Route::get('getAPKLatestVersion', [APIController::class, 'getAPKLatestVersion']);
Route::middleware(['auth:api', 'token.api'])->group(function () {

    // PO
    Route::get('getDataPO', [APIPurchaseOrderController::class, 'index']);
    Route::post('saveReceipt', [APIPurchaseOrderController::class, 'saveReceipt']);
    Route::post('saveEditReceipt', [APIPurchaseOrderController::class, 'saveEditReceipt']);
    Route::get('getWarehouseReceipt', [APIPurchaseOrderController::class, 'wsaWarehouse']);
    Route::get('wsaPenyimpananPalet', [APIPurchaseOrderController::class, 'wsaPenyimpananPaletSearch']);
    //delete po
    Route::post('deleteDraftPo', [APIPurchaseOrderController::class, 'deleteDraft']);

    Route::get("getDataPORecheck", [APIPurchaseOrderRecheckController::class, "index"]);
    Route::post("checkWarehouse", [APIPurchaseOrderRecheckController::class, "checkWarehouse"]);
    Route::post("submitRecheckReceipt", [APIPurchaseOrderRecheckController::class, "saveReceiptRecheck"]);
    Route::post("validateRecheck", [APIPurchaseOrderRecheckController::class, "validateRecheck"]);
    Route::post("getDuplicateKeys", [APIPurchaseOrderRecheckController::class, "getDuplicateKeys"]);

    // PO Approval
    Route::get('getPoApproval', [APIPurchaseOrderApprovalController::class, 'getPoApproval']);
    Route::get('getDataApprovalPO', [APIPurchaseOrderApprovalController::class, 'index']);
    Route::post('approveRejectReceipt', [APIPurchaseOrderApprovalController::class, 'approveRejectReceipt']);

    // PO Quality Info
    Route::get('getReceiptSeenBy', [APIQualityInfoController::class, 'index']);
    Route::post('updateReceiptSeenBy', [APIQualityInfoController::class, 'store']);

    // PO Transfer WMS
    Route::get('getTransferList', [APITrasnferStockController::class, 'index']);
    Route::get('getStockItemBin', [APITrasnferStockController::class, 'getStockItemBin']);
    Route::post('saveTransfer', [APITrasnferStockController::class, 'saveTransfer']);

    // Print QR
    Route::get('getDataPrintQR', [APIZebraPrinterController::class, 'getDataPrintQR']);
    Route::post('getPoPrint', [APIZebraPrinterController::class, 'getPoPrint']);
    Route::post('getBookPrint', [APIZebraPrinterController::class, 'getBookPrint']);
    Route::post('getLotPrint', [APIZebraPrinterController::class, 'getLotPrint']);
    Route::post('getItemPrint', [APIZebraPrinterController::class, 'getItemPrint']);
    Route::post('getPrinterPrint', [APIZebraPrinterController::class, 'getPrinterPrint']);
    Route::post('printQRItem', [APIZebraPrinterController::class, 'printQRItem']);

    // Print WO QR
    Route::post('printQRItemWO', [APIZebraPrinterController::class, 'printQRItemWO']);

    // WSA PO
    Route::get('wsaWOPrint', [APIPurchaseOrderController::class, 'wsaWOPrint']); //mira
    Route::get('wsaWOMaster', [APIPurchaseOrderController::class, 'wsaWOMaster']); //mira
    Route::post('wsaDataPO', [APIPurchaseOrderController::class, 'wsaDataPO']);
    Route::post('wsaLotBatch', [APIPurchaseOrderController::class, 'wsaLotBatch']);
    Route::post('wsaPenyimpanan', [APIPurchaseOrderController::class, 'wsaPenyimpanan']);
    Route::post('wsaPenyimpananWarehouse', [APIPurchaseOrderController::class, 'wsaPenyimpananWarehouse']);
    Route::post('wsaWarehouse', [APIPurchaseOrderController::class, 'wsaWarehouse']);
    Route::post('wsaLevel', [APIPurchaseOrderController::class, 'wsaPenyimpananPalet']);
    Route::post('wsaBin', [APIPurchaseOrderController::class, 'wsaPenyimpananPalet']);
    Route::post('wsaLoc', [APIPurchaseOrderController::class, 'wsaLoc']);
    Route::post('wsaLastBatch', [APIPurchaseOrderController::class, 'wsaLastBatch']);
    Route::post('getListUser', [APIPurchaseOrderController::class, 'getListUser']);
    Route::post('wsaCheckBatch', [APIPurchaseOrderController::class, 'wsaCheckBatch']);
    Route::post('getWebLocationData', [APIPurchaseOrderController::class, 'getWebLocationData']);
    Route::post('wsaNewLevel', [APIPurchaseOrderController::class, 'wsaNewLevel']);
    Route::post('wsaNewBin', [APIPurchaseOrderController::class, 'wsaNewBin']);
    Route::post('wsaGetPotensi', [APIPurchaseOrderController::class, 'wsaGetPotensi']);
    Route::post('getWebLocationDataReceipt', [APIPurchaseOrderController::class, 'getWebLocationDataReceipt']);
    Route::post('getAllWarehouse', [APIPurchaseOrderController::class, 'getAllWarehouse']);
    Route::post('getAllLevel', [APIPurchaseOrderController::class, 'getAllLevel']);
    Route::post('getAllBin', [APIPurchaseOrderController::class, 'getAllBin']);

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
    Route::get('approverList', [APIPackingReplenishmentController::class, 'approverList']);
    Route::post('rejectPackingReplenishment', [APIPackingReplenishmentController::class, 'rejectPackingReplenishment']);
    Route::post('approvePackingReplenishment', [APIPackingReplenishmentController::class, 'approvePackingReplenishment']);
    Route::get('editPackingReplenishment/{id}', [APIPackingReplenishmentController::class, 'editPackingReplenishment']);
    Route::get('getPackingReplenishmentApprovalList', [APIPackingReplenishmentController::class, 'getPackingReplenishmentApprovalList']);

    Route::get('listShipmentScheduleWSA', [APIPackingReplenishmentController::class, 'listShipmentScheduleWSA']);

    Route::get('getStockWarehouse', [APIPackingReplenishmentController::class, 'getStockWarehouse']);

    // Shipper Confirm
    Route::get('getShipperConfirmation', [APIShipperConfirmController::class, 'index']);
    Route::post('confirmShipment', [APIShipperConfirmController::class, 'store']);

    // Other Shipment Schedule
    Route::get('getOtherShipmentSchedule', [APIOtherShipmentScheduleController::class, 'index']);
    Route::get('/getItemOSS', [APIOtherShipmentScheduleController::class, 'getItemOSS']);
    Route::post('/getLocationByPart', [APIOtherShipmentScheduleController::class, 'getLocationByPart']);
    Route::post('/saveOtherShipmentSchedule', [APIOtherShipmentScheduleController::class, 'store']);
    Route::post('deleteOtherShipmentSchedule', [APIOtherShipmentScheduleController::class, 'delete']);
    Route::get('editOtherShipmentSchedule/{id}', [APIOtherShipmentScheduleController::class, 'edit']);
    Route::put('updateOtherShipmentSchedule/{id}', [APIOtherShipmentScheduleController::class, 'update']);

    // Other Shipment Preparation
    Route::get('getOtherShipmentPreparation', [APIOtherShipmentPreparationController::class, 'index']);
    Route::get('listOtherShipmentSchedule', [APIOtherShipmentPreparationController::class, 'listOtherShipmentSchedule']);
    Route::post('saveOtherShipmentPreparation', [APIOtherShipmentPreparationController::class, 'store']);
    Route::get('approverListShipmentPreparation', [APIOtherShipmentPreparationController::class, 'approverListShipmentPreparation']);
    Route::post('rejectOtherShipmentPreparation', [APIOtherShipmentPreparationController::class, 'rejectShipmentPreparation']);
    Route::post('approveOtherShipmentPreparation', [APIOtherShipmentPreparationController::class, 'approveShipmentPreparation']);
    Route::get('editOtherShipmentPreparation/{id}', [APIOtherShipmentPreparationController::class, 'editShipmentPreparation']);
    Route::get('getOtherShipmentPreparationApprovalList', [
        APIOtherShipmentPreparationController::class,
        'getOtherShipmentPreparationApprovalList',
    ]);

    // Picklist
    Route::get('getDataWo', [APIWorkOrderController::class, 'getDataWo']);
    Route::post('searchDataWo', [APIWorkOrderController::class, 'wsaDataWo']);
    Route::post('insertDataWoMstr', [APIWorkOrderController::class, 'insertDataWoMstr']);
    Route::post('insertDataWoDetail', [APIWorkOrderController::class, 'insertDataWoDetail']);
    Route::post('deleteDataWoDetail', [APIWorkOrderController::class, 'deleteDataWoDetail']);
    Route::post('wsaDataInvWo', [APIWorkOrderController::class, 'wsaDataInvWo']);
    Route::post('sendDataInvWo', [APIWorkOrderController::class, 'sendDataInvWo']);
    Route::post('deleteDataWo', [APIWorkOrderController::class, 'deleteDataWo']);
    Route::post('saveQtyWo', [APIWorkOrderController::class, 'saveQtyWo']);

    //Route::get('getDataPicklist', [APIWorkOrderController::class, 'getDataPicklist']);
    Route::get('getDataPicklistDetail', [APIWorkOrderController::class, 'getDataPicklistDetail']);
    Route::get('getDataItemWo', [APIWorkOrderController::class, 'getDataItemWo']);
    Route::post('wsaDataItem', [APIWorkOrderController::class, 'wsaDataItem']);

    //picklist browse
    Route::get('getDataPicklist', [APIPicklistShopping::class, 'getPicklistMstr']);

    // Picklist Shopping
    Route::get('getPicklistDet', [APIPicklistShopping::class, 'getPicklistDet']);
    Route::post('wsaSendQtyPick', [APIPicklistShopping::class, 'wsaSendQtyPick']);
    Route::post('getApproverList', [APIPicklistShopping::class, 'getApproverList']);

    // Picklist Approval
    Route::get('getPicklistDetAppr', [APIPicklistShopping::class, 'getPicklistDetAppr']);
    Route::post('wsaUpdateStatusPick', [APIPicklistShopping::class, 'wsaUpdateStatusPick']);

    // Picklist Transfer
    Route::get('getPicklistDetTrans', [APIPicklistShopping::class, 'getPicklistDetAppr']);
    Route::get('getLocationToPick', [APIPicklistShopping::class, 'getLocationTo']);
    Route::post('submitPicklistTransfer', [APIPicklistShopping::class, 'submitPicklistTransfer']);

    // Picklist Receipt
    Route::get('getPicklistDetRcpt', [APIPicklistShopping::class, 'getPicklistDetAppr']);
    Route::post('wsaReceiptPick', [APIPicklistShopping::class, 'submitPicklistReceipt']);

    // Single Transfer
    // Route::get("getLocationData", [APIPicklistShopping::class, "getLocationData"]);
    // Route::get("wsaWarehousePick", [APIPicklistShopping::class, "wsaWarehouse"]);
    // Route::get("getSearchLocation", [APIPicklistShopping::class, "wsainvdet"]);
    // Route::post("sendTransferItem", [APIPicklistShopping::class, "sendTransferItem"]);
    Route::get('getLocationData', [APISingleTransfer::class, 'getLocationData']);
    Route::post('getWebLocationDataTransfer', [APISingleTransfer::class, 'getWebLocationDataTransfer']);
    Route::get('getSiteData', [APISingleTransfer::class, 'getSiteData']);
    Route::get('wsaWarehousePick', [APISingleTransfer::class, 'wsaWarehouse']);
    Route::get('getSearchLocation', [APISingleTransfer::class, 'wsainvdet']);
    Route::post('sendTransferItem', [APISingleTransfer::class, 'sendTransferItem']);
    Route::get('getTransferData', [APISingleTransfer::class, 'getTransferData']);
    Route::post('receiptItem', [APISingleTransfer::class, 'receiptItem']);
    Route::get('getSingleTransferData', [APISingleTransfer::class, 'getSingleTransferData']);

    Route::post('getWlbData', [APISingleTransfer::class, 'getWlbData']);

    //Work Order Issue
    //Route::get("getIssueData", [APIWorkOrderController::class, "getIssueData"]);
    Route::post('issueWorkOrder', [APIPicklistShopping::class, 'issueWorkOrder']);

    // Inventory WMS
    Route::get('/getInvWms', [APIController::class, 'getInvWms']);

    //Transaksi Out
    Route::post('/submitout', [APITransIssUnpController::class, 'submitIssOut']);

    //Transaksi In
    Route::post('/submitRctUnp', [APITransRctUnpController::class, 'submitRctUnp']);

    //Data Inquiry

    Route::post('/checkpallet', [APIController::class, 'checkPallet']);
    Route::post('/checkpalletloc', [APIController::class, 'checkPalletLoc']);
    Route::post('/checkloc', [APIController::class, 'checkLoc']);
    Route::post('/checkItem', [APIController::class, 'checkItem']);
    Route::post('/checkSupplier', [APIController::class, 'checkSupplier']);
    Route::post('/checkdatainquiry', [APIController::class, 'getDataInquiry']);

    //Sampling & Pengembalian QO
    Route::get("/getSamplingData", [APISampling::class, 'getSamplingData']);
    Route::get("/getLotSampling", [APISampling::class, 'getLotSampling']);
    Route::post("/transferSampling", [APISampling::class, 'transferSampling']);
    
    
    Route::get("/getPengembalianQo", [APIPengembalian::class, 'getPengembalianQo']);
    Route::post("/checkWarehouseReturn", [APIPengembalian::class, 'checkWarehouseReturn']);
    Route::get("/getLotPengembalian", [APIPengembalian::class, 'getLotPengembalian']);
    Route::post("/transferPengembalianQo", [APIPengembalian::class, 'transferPengembalianQo']);
    Route::get("getApproverSampling", [APIPengembalian::class, "getApproverSampling"]);
    
    Route::get("getTransactionHistory", [APISingleTransfer::class, "getTransactionHistory"]);
    //lookup browse android
    Route::get('getLocData', [APIController::class, 'getLocData']);
    Route::get('getSites', [APIController::class, 'getSites']);
    Route::get('getWrhData', [APIController::class, 'getWrhData']);
    Route::get('getLevelData', [APIController::class, 'getLevelData']);
    Route::get('getBinData', [APIController::class, 'getBinData']);
    Route::get('getHistoryData', [APIController::class, 'getHistoryData']);

    Route::get('cekItemLot', [APIController::class, 'cekItemLot']);

    // Penyerahan Barang
    // Route::get("getLocationData", [APIPicklistShopping::class, "getLocationData"]);
    // Route::get("wsaWarehousePick", [APIPicklistShopping::class, "wsaWarehouse"]);
    // Route::get("getSearchLocation", [APIPicklistShopping::class, "wsainvdet"]);
    // Route::post("sendTransferItem", [APIPicklistShopping::class, "sendTransferItem"]);
    Route::get("getLocationBarangJadi", [APIBarangJadi::class, "getLocationBarangJadi"]);
    Route::post("getWebLocationDataTransfer", [APIBarangJadi::class, "getWebLocationBarangJadi"]);
    Route::get("getSiteBarangJadi", [APIBarangJadi::class, "getSiteBarangJadi"]);
    Route::get("wsaWarehousePick", [APIBarangJadi::class, "wsaWarehouseBarangJadi"]);
    Route::get("getSearchLocation", [APIBarangJadi::class, "wsainvdetBarangJadi"]);
    Route::post("sendBarangJadi", [APIBarangJadi::class, "sendBarangJadi"]);
    Route::get("getTransferData", [APIBarangJadi::class, "getTransferBarangJadi"]);
    Route::post("receiptItempb", [APIBarangJadi::class, "receiptItempb"]);
    Route::get("getPenerimaanBarangData", [APIBarangJadi::class, "getPenerimaanBarangData"]);
    Route::post("getWlbBarangJadi", [APIBarangJadi::class, "getWlbBarangJadi"]);
    
    
});
// WSA Picklist
