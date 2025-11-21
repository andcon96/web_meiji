<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReceiptBookController;
use App\Http\Controllers\Settings\ApprovalReceiptMasterController;
use App\Http\Controllers\Settings\ApprovalSetupController;
use App\Http\Controllers\Settings\ConnectionController;
use App\Http\Controllers\Settings\IconController;
use App\Http\Controllers\Settings\ItemController;
use App\Http\Controllers\Settings\ItemLocationController;
use App\Http\Controllers\Settings\LocationController;
use App\Http\Controllers\Settings\MenuController;
use App\Http\Controllers\Settings\MenuStructureController;
use App\Http\Controllers\Settings\PrefixController;
use App\Http\Controllers\Settings\RoleAndroidMenuController;
use App\Http\Controllers\Settings\RoleAccessWebController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\ShipmentSchedulePrefixController;
use App\Http\Controllers\Settings\OtherShipmentSchedulePrefixController;
use App\Http\Controllers\Settings\OtherShipmentPreparationPrefixController;
use App\Http\Controllers\Settings\ShipperPrefixController;
use App\Http\Controllers\Settings\PicklistPrefixController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\ShipmentReportController;
use App\Http\Controllers\OtherShipmentReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Default Auth Laravel
Route::get("/", function () {
    if (Auth::check()) {
        return Redirect::to("home");
    }
    return view("auth.login");
})->name("defaultLogin");

Route::group(["middleware" => ["auth"]], function () {
    Route::get("logout", "\App\Http\Controllers\Auth\LoginController@logout");
    Route::post("markAsRead", [NotificationController::class, "markAsRead"])->name("markAsRead");
    Route::post("markAllAsRead", [NotificationController::class, "markAllAsRead"])->name("markAllAsRead");

    Route::resource("users", UserController::class);
    Route::post("/deleteUser", [UserController::class, "delete"])->name("deleteUser");
    Route::put("/resetPassword", [UserController::class, "resetPassword"])->name("resetPassword");

    Route::resource("menus", MenuController::class);
    Route::post("/deleteMenu", [MenuController::class, "delete"])->name("deleteMenu");

    Route::resource("menuStructure", MenuStructureController::class);
    Route::post("/deleteMenuStructure", [MenuStructureController::class, "delete"])->name("deleteMenuStructure");
    Route::post("/moveUpMenuStructure", [MenuStructureController::class, "moveUp"])->name("moveUpMenuStructure");
    Route::post("/moveDownMenuStructure", [MenuStructureController::class, "moveDown"])->name("moveDownMenuStructure");

    Route::resource("roles", RoleController::class);
    Route::post("/deleteRole", [RoleController::class, "delete"])->name("deleteRole");

    Route::resource("rolesAndroid", RoleAndroidMenuController::class);
    Route::post("/updateRoleAccess", [RoleAndroidMenuController::class, "updateRoleAccess"])->name("updateRoleAccess");

    Route::resource("icons", IconController::class);
    Route::post("/deleteIcon", [IconController::class, "delete"])->name("deleteIcon");

    Route::resource("connections", ConnectionController::class);
    Route::post("/deleteConnection", [ConnectionController::class, "delete"])->name("deleteConnection");

    Route::resource("prefix", PrefixController::class);
    Route::post("/deletePrefix", [PrefixController::class, "delete"])->name("deletePrefix");

    Route::resource("items", ItemController::class);
    Route::post("/loadItem", [ItemController::class, "loadItem"])->name("loadItem");

    Route::resource("appReceipts", ApprovalReceiptMasterController::class);

    Route::resource("approvalSetup", ApprovalSetupController::class);

    Route::resource("locations", LocationController::class);
    Route::get("/uploadLocationDetail", [LocationController::class, "uploadLocationDetail"])->name("uploadLocationDetail");
    Route::post("/checkFileUploadLocation", [LocationController::class, "checkFileUploadLocation"])->name("checkFileUploadLocation");
    Route::post("/confirmFileUploadLocation", [LocationController::class, "confirmFileUploadLocation"])->name("confirmFileUploadLocation");
    Route::get("/downloadTemplateLoadLocation", [LocationController::class, "downloadTemplateLoadLocation"])->name(
        "downloadTemplateLoadLocation",
    );
    Route::post("/loadLocation", [LocationController::class, "loadLocation"])->name("loadLocation");

    Route::resource("itemlocation", ItemLocationController::class);
    Route::get("/uploadItemLocationDetail", [ItemLocationController::class, "uploadItemLocationDetail"])->name("uploadItemLocationDetail");
    Route::post("/checkFileUploadItemLocation", [ItemLocationController::class, "checkFileUploadItemLocation"])->name(
        "checkFileUploadItemLocation",
    );
    Route::post("/confirmFileUploadItemLocation", [ItemLocationController::class, "confirmFileUploadItemLocation"])->name(
        "confirmFileUploadItemLocation",
    );
    Route::get("itemlocationdetail/{id}", [ItemLocationController::class, "itemLocationDetail"])->name("itemLocationDetail");
    Route::get("createitemlocationdetail/{id}", [ItemLocationController::class, "createItemLocationDetail"])->name(
        "createItemLocationDetail",
    );
    Route::get("/downloadTemplateLoadItemLocation", [ItemLocationController::class, "downloadTemplateLoadItemLocation"])->name(
        "downloadTemplateLoadItemLocation",
    );

    // Shipment Schedule Prefix
    Route::resource("shipmentSchedulePrefix", ShipmentSchedulePrefixController::class);
    Route::post("deleteShipmentScedulePrefix", [ShipmentSchedulePrefixController::class, "delete"])->name("deleteShipmentScedulePrefix");

    Route::resource("shipperPrefix", ShipperPrefixController::class);
    Route::post("deleteShipperPrefix", [ShipperPrefixController::class, "delete"])->name("deleteShipperPrefix");

    // Other Shipment Schedule Prefix
    Route::resource("otherShipmentSchedulePrefix", OtherShipmentSchedulePrefixController::class);
    Route::post("deleteOtherShipmentSchedulePrefix", [OtherShipmentSchedulePrefixController::class, "delete"])->name(
        "deleteOtherShipmentSchedulePrefix",
    );
    // Picklist Prefix

    Route::resource("shipperPrefix", ShipperPrefixController::class);
    Route::post("deleteShipperPrefix", [ShipperPrefixController::class, "delete"])->name("deleteShipperPrefix");

    // Other Shipment Preparation Prefix
    Route::resource("otherShipmentPreparationPrefix", OtherShipmentPreparationPrefixController::class);
    Route::post("deleteOtherShipmentPreparationPrefix", [OtherShipmentPreparationPrefixController::class, "delete"])->name(
        "deleteOtherShipmentPreparationPrefix",
    );

    // Picklist Prefix
    Route::resource("shipperPrefix", ShipperPrefixController::class);
    Route::post("deleteShipperPrefix", [ShipperPrefixController::class, "delete"])->name("deleteShipperPrefix");

    Route::resource("PicklistPrefix", PicklistPrefixController::class);
    Route::post("deletePicklistPrefix", [PicklistPrefixController::class, "delete"])->name("deletePicklistPrefix");


    // Shipment Schedule Report
    Route::get("/ShipmentScheduleReport", [ShipmentReportController::class, "index"]);
    /* Route::get('/getAllSSD', [ShipmentReportController::class, 'getAllSSD']); */
    Route::get("/getAllSSM", [ShipmentReportController::class, "getAllSSM"]);
    Route::post("/SSDExport", [ShipmentReportController::class, "SSDExport"])->name("SSDExport");

    // Other Shipment Schedule Report
    Route::get("/OtherShipmentScheduleReport", [OtherShipmentReportController::class, "index"]);
    Route::get("/getAllOSSM", [OtherShipmentReportController::class, "getAllOSSM"]);
    Route::post("/OSSDExport", [OtherShipmentReportController::class, "OSSDExport"])->name("OSSDExport");

    // Printout Menu
    Route::get("getReceiptBook", [ReceiptBookController::class, "getReceiptBook"]);
    Route::get("printBook/{id}", [ReceiptBookController::class, "printBook"])->name("printBook");

    //access role menu web
    Route::resource("rolesWeb", RoleAccessWebController::class);
    Route::post("/updateRoleAccessWeb", [RoleAccessWebController::class, "updateRoleAccess"])->name("updateRoleAccessWeb");
});

Auth::routes();
Route::get("/home", "HomeController@index")->name("home");
Route::post("changePassword", [UserController::class, "changePassword"])->name("changePassword");
