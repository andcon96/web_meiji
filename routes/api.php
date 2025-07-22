<?php

use App\Http\Controllers\API\APIController;
use App\Http\Controllers\API\APIPurchaseOrderController;
use App\Http\Controllers\API\APIServiceRequestController;
use Illuminate\Http\Request;
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
    Route::get('getDataPO', [APIPurchaseOrderController::class, 'index']);
    Route::post('saveReceipt', [APIPurchaseOrderController::class, 'saveReceipt']);

    // WSA PO 
    Route::post('wsaDataPO', [APIPurchaseOrderController::class, 'wsaDataPO']);
    Route::post('wsaLotBatch', [APIPurchaseOrderController::class, 'wsaLotBatch']);
    Route::post('wsaPenyimpanan', [APIPurchaseOrderController::class, 'wsaPenyimpanan']);
});
