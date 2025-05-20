<?php

use App\Http\Controllers\API\APIController;
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
    
// Outbound QAD Item
Route::post('getitemupdate', [APIController::class, 'itemmaster']);

// Endpoint Shopify Sales to SO Solution
Route::post('getsummaryso', [APIController::class, 'getSummarySO']);



Route::middleware(['auth:api', 'token.api'])->group(function () {
    // WSA Stock 
    Route::post('getdataloc', [APIController::class, 'getDataLocationDetailQAD']);

    // Endpoint Get Item Update
    Route::post('getdataitem', [APIController::class, 'getQadData']);

    // Get SO Shopify
    Route::post('getsoshopify', [APIController::class, 'getSoShopify']);
        
    Route::post('testdata', [APIController::class, 'testdata']);
});

