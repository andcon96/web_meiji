<?php

use App\Http\Controllers\API\APIController;
use App\Http\Controllers\API\APIPurchaseOrderApprovalController;
use App\Http\Controllers\API\APIPurchaseOrderController;
use App\Http\Controllers\API\APIPurchaseOrderRecheckController;
use App\Http\Controllers\API\APIQualityInfoController;
use App\Http\Controllers\API\OtherShipmentSchedule\APIOtherShipmentScheduleController;
use App\Http\Controllers\API\OtherShipmentPreparation\APIOtherShipmentPreparationController;
use App\Http\Controllers\API\ShipmentSchedule\APIShipmentScheduleController;
use App\Http\Controllers\API\APITrasnferStockController;
use App\Http\Controllers\API\APIWorkOrderController;
use App\Http\Controllers\API\APIPicklistShopping;
use App\Http\Controllers\API\APISingleTransfer;
use App\Http\Controllers\API\APIZebraPrinterController;
use App\Http\Controllers\API\PackingReplenishment\APIPackingReplenishmentController;
use App\Http\Controllers\API\ShipperConfirm\APIShipperConfirmController;
use App\Http\Controllers\API\APITransIssUnpController;
use App\Http\Controllers\API\APITransRctUnpController;
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

Route::post("login", [APIController::class, "login"]);
Route::post("changepass", [APIController::class, "resetPass"]);

// Outbound WO
Route::post("getWorkOrderQad", [APIController::class, "getWorkOrderQad"]);

Route::middleware(["auth:api", "token.api"])->group(function () {
    // PO
    Route::get("getDataPO", [APIPurchaseOrderController::class, "index"]);
    Route::post("saveReceipt", [APIPurchaseOrderController::class, "saveReceipt"]);
    Route::post("saveEditReceipt", [APIPurchaseOrderController::class, "saveEditReceipt"]);
    Route::get("getPoApproval", [APIPurchaseOrderApprovalController::class, "getPoApproval"]);

    // PO Recheck
    Route::get("getDataPORecheck", [APIPurchaseOrderRecheckController::class, "index"]);
    Route::post("submitRecheckReceipt", [APIPurchaseOrderRecheckController::class, "saveReceiptRecheck"]);

    // PO Approval
    Route::get("getDataApprovalPO", [APIPurchaseOrderApprovalController::class, "index"]);
    Route::post("approveRejectReceipt", [APIPurchaseOrderApprovalController::class, "approveRejectReceipt"]);

    // PO Quality Info
    Route::get("getReceiptSeenBy", [APIQualityInfoController::class, "index"]);
    Route::post("updateReceiptSeenBy", [APIQualityInfoController::class, "store"]);

    // PO Transfer WMS
    Route::get("getTransferList", [APITrasnferStockController::class, "index"]);
    Route::get("getStockItemBin", [APITrasnferStockController::class, "getStockItemBin"]);
    Route::post("saveTransfer", [APITrasnferStockController::class, "saveTransfer"]);

    // Print QR
    Route::get("getDataPrintQR", [APIZebraPrinterController::class, "getDataPrintQR"]);
    Route::post("printQRItem", [APIZebraPrinterController::class, "printQRItem"]);

    // WSA PO
    Route::post("wsaDataPO", [APIPurchaseOrderController::class, "wsaDataPO"]);
    Route::post("wsaLotBatch", [APIPurchaseOrderController::class, "wsaLotBatch"]);
    Route::post("wsaPenyimpanan", [APIPurchaseOrderController::class, "wsaPenyimpanan"]);

    Route::post("wsaWarehouse", [APIPurchaseOrderController::class, "wsaWarehouse"]);
    Route::post("wsaLevel", [APIPurchaseOrderController::class, "wsaLevel"]);
    Route::post("wsaBin", [APIPurchaseOrderController::class, "wsaBin"]);
    Route::post("wsaLoc", [APIPurchaseOrderController::class, "wsaLoc"]);
    Route::post("wsaLastBatch", [APIPurchaseOrderController::class, "wsaLastBatch"]);
    Route::post("getListUser", [APIPurchaseOrderController::class, "getListUser"]);
    Route::post("wsaCheckBatch", [APIPurchaseOrderController::class, "wsaCheckBatch"]);

    // Shipment Schedule
    Route::get("getShipmentSchedule", [APIShipmentScheduleController::class, "index"]);
    Route::post("wsaCustomer", [APIShipmentScheduleController::class, "wsaCustomer"]);
    Route::post("wsaSalesOrder", [APIShipmentScheduleController::class, "wsaSalesOrder"]);
    Route::post("wsaInventoryDetail", [APIShipmentScheduleController::class, "wsaInventoryDetail"]);
    Route::post("saveShipmentSchedule", [APIShipmentScheduleController::class, "store"]);
    Route::post("deleteShipmentSchedule", [APIShipmentScheduleController::class, "delete"]);
    Route::get("editShipmentSchedule/{id}", [APIShipmentScheduleController::class, "edit"]);
    Route::put("updateShipmentSchedule/{id}", [APIShipmentScheduleController::class, "update"]);
    Route::get("getDefaultSampleLoc", [APITrasnferStockController::class, "getDefaultSampleLoc"]);

    // Packing Replenishment
    Route::get("getPackingReplenishment", [APIPackingReplenishmentController::class, "index"]);
    Route::get("listShipmentSchedule", [APIPackingReplenishmentController::class, "listShipmentSchedule"]);
    Route::post("savePackingReplenishment", [APIPackingReplenishmentController::class, "store"]);
    Route::get("approverList", [APIPackingReplenishmentController::class, "approverList"]);
    Route::post("rejectPackingReplenishment", [APIPackingReplenishmentController::class, "rejectPackingReplenishment"]);
    Route::post("approvePackingReplenishment", [APIPackingReplenishmentController::class, "approvePackingReplenishment"]);
    Route::get("editPackingReplenishment/{id}", [APIPackingReplenishmentController::class, "editPackingReplenishment"]);
    Route::get("getPackingReplenishmentApprovalList", [APIPackingReplenishmentController::class, "getPackingReplenishmentApprovalList"]);

    // Shipper Confirm
    Route::get("getShipperConfirmation", [APIShipperConfirmController::class, "index"]);
    Route::post("confirmShipment", [APIShipperConfirmController::class, "store"]);

    // Other Shipment Schedule
    Route::get("getOtherShipmentSchedule", [APIOtherShipmentScheduleController::class, "index"]);
    Route::get("/getItemOSS", [APIOtherShipmentScheduleController::class, "getItemOSS"]);
    Route::post("/getLocationByPart", [APIOtherShipmentScheduleController::class, "getLocationByPart"]);
    Route::post("/saveOtherShipmentSchedule", [APIOtherShipmentScheduleController::class, "store"]);
    Route::post("deleteOtherShipmentSchedule", [APIOtherShipmentScheduleController::class, "delete"]);
    Route::get("editOtherShipmentSchedule/{id}", [APIOtherShipmentScheduleController::class, "edit"]);
    Route::put("updateOtherShipmentSchedule/{id}", [APIOtherShipmentScheduleController::class, "update"]);

    // Other Shipment Preparation
    Route::get("getOtherShipmentPreparation", [APIOtherShipmentPreparationController::class, "index"]);
    Route::get("listOtherShipmentSchedule", [APIOtherShipmentPreparationController::class, "listOtherShipmentSchedule"]);
    Route::post("saveOtherShipmentPreparation", [APIOtherShipmentPreparationController::class, "store"]);
    Route::get("approverListShipmentPreparation", [APIOtherShipmentPreparationController::class, "approverListShipmentPreparation"]);
    Route::post("rejectOtherShipmentPreparation", [APIOtherShipmentPreparationController::class, "rejectShipmentPreparation"]);
    Route::post("approveOtherShipmentPreparation", [APIOtherShipmentPreparationController::class, "approveShipmentPreparation"]);
    Route::get("editOtherShipmentPreparation/{id}", [APIOtherShipmentPreparationController::class, "editShipmentPreparation"]);
    Route::get("getOtherShipmentPreparationApprovalList", [
        APIOtherShipmentPreparationController::class,
        "getOtherShipmentPreparationApprovalList",
    ]);

    // Picklist
    Route::get("getDataWo", [APIWorkOrderController::class, "getDataWo"]);
    Route::post("searchDataWo", [APIWorkOrderController::class, "wsaDataWo"]);
    Route::post("insertDataWoMstr", [APIWorkOrderController::class, "insertDataWoMstr"]);
    Route::post("insertDataWoDetail", [APIWorkOrderController::class, "insertDataWoDetail"]);
    Route::post("deleteDataWoDetail", [APIWorkOrderController::class, "deleteDataWoDetail"]);
    Route::post("wsaDataInvWo", [APIWorkOrderController::class, "wsaDataInvWo"]);
    Route::post("sendDataInvWo", [APIWorkOrderController::class, "sendDataInvWo"]);
    Route::post("deleteDataWo", [APIWorkOrderController::class, "deleteDataWo"]);
    Route::post("saveQtyWo", [APIWorkOrderController::class, "saveQtyWo"]);

    //Route::get('getDataPicklist', [APIWorkOrderController::class, 'getDataPicklist']);
    Route::get("getDataPicklistDetail", [APIWorkOrderController::class, "getDataPicklistDetail"]);
    Route::get("getDataItemWo", [APIWorkOrderController::class, "getDataItemWo"]);
    Route::post("wsaDataItem", [APIWorkOrderController::class, "wsaDataItem"]);

    //picklist browse
    Route::get("getDataPicklist", [APIPicklistShopping::class, "getPicklistMstr"]);

    // Picklist Shopping
    Route::get("getPicklistDet", [APIPicklistShopping::class, "getPicklistDet"]);
    Route::post("wsaSendQtyPick", [APIPicklistShopping::class, "wsaSendQtyPick"]);

    // Picklist Approval
    Route::get("getPicklistDetAppr", [APIPicklistShopping::class, "getPicklistDetAppr"]);
    Route::post("wsaUpdateStatusPick", [APIPicklistShopping::class, "wsaUpdateStatusPick"]);

    // Picklist Transfer
    Route::get("getPicklistDetTrans", [APIPicklistShopping::class, "getPicklistDetAppr"]);
    Route::get("getLocationToPick", [APIPicklistShopping::class, "getLocationTo"]);
    Route::post("submitPicklistTransfer", [APIPicklistShopping::class, "submitPicklistTransfer"]);

    // Picklist Receipt
    Route::get("getPicklistDetRcpt", [APIPicklistShopping::class, "getPicklistDetAppr"]);
    Route::post("wsaReceiptPick", [APIPicklistShopping::class, "submitPicklistReceipt"]);

    // Single Transfer
    Route::get("getLocationData", [APIPicklistShopping::class, "getLocationData"]);
    Route::get("wsaWarehousePick", [APIPicklistShopping::class, "wsaWarehouse"]);
    Route::get("getSearchLocation", [APIPicklistShopping::class, "wsainvdet"]);
    Route::post("sendTransferItem", [APIPicklistShopping::class, "sendTransferItem"]);
    Route::get("getLocationData", [APISingleTransfer::class, "getLocationData"]);
    Route::get("wsaWarehousePick", [APISingleTransfer::class, "wsaWarehouse"]);
    Route::get("getSearchLocation", [APISingleTransfer::class, "wsainvdet"]);
    Route::post("sendTransferItem", [APISingleTransfer::class, "sendTransferItem"]);
    
    Route::get("getTransferData", [APISingleTransfer::class, "getTransferData"]);
    Route::post("receiptItem", [APISingleTransfer::class, "receiptItem"]);
    Route::get("getSingleTransferData", [APISingleTransfer::class, "getSingleTransferData"]);

    //Work Order Issue
    //Route::get("getIssueData", [APIWorkOrderController::class, "getIssueData"]);
    Route::post("issueWorkOrder", [APIPicklistShopping::class, "issueWorkOrder"]);

    // Inventory WMS
    Route::get("/getInvWms", [APIController::class, 'getInvWms']);

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

});
// WSA Picklist
