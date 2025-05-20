<?php

use App\Http\Controllers\ComplainReturn\ComplainReturnConfirmController;
use App\Http\Controllers\ComplainReturn\ComplainReturnController;
use App\Http\Controllers\ComplainReturnShortage\ComplainReturnShortageController;
use App\Http\Controllers\CustomerShipTo\CustomerShipToController;
use App\Http\Controllers\EPoint\SummaryEpointController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\ItemTransfer\ItemTFConfirmController;
use App\Http\Controllers\ItemTransfer\ItemTFSSBConfirmController;
use App\Http\Controllers\ItemTransfer\ItemTransferController;
use App\Http\Controllers\ItemTransfer\ItemTransferSSBController;
use App\Http\Controllers\ItemTransferShortage\ItemTransferShortageController;
use App\Http\Controllers\ItemTransferShortage\ItemTransferShortageSSBController;
use App\Http\Controllers\LaborFeedback\LaborFeedbackController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackagingRequest\PackagingRequestApprovalController;
use App\Http\Controllers\PackagingRequest\PackagingRequestController;
use App\Http\Controllers\Picklist\PicklistController;
use App\Http\Controllers\PurchaseOrder\BuildPOFromPRController;
use App\Http\Controllers\PurchaseOrder\BuildPOFromPRSSBController;
use App\Http\Controllers\PurchaseOrder\ConvertPOMonthlyController;
use App\Http\Controllers\PurchaseOrder\POPrintController;
use App\Http\Controllers\PurchaseOrder\POPrintSSBController;
use App\Http\Controllers\PurchaseOrder\POReprintApprovalController;
use App\Http\Controllers\PurchaseOrder\POReprintApprovalSSBController;
use App\Http\Controllers\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrder\PurchaseOrderMaintController;
use App\Http\Controllers\PurchaseRequisition\PlannedOrderController;
use App\Http\Controllers\PurchaseRequisition\PlannedOrderSSBController;
use App\Http\Controllers\PurchaseRequisition\PRApprovalController;
use App\Http\Controllers\PurchaseRequisition\PRApprovalSSBController;
use App\Http\Controllers\PurchaseRequisition\PurchaseRequisitionController;
use App\Http\Controllers\PurchaseRequisition\PurchaseRequisitionSSBController;
use App\Http\Controllers\QxtendLog\QxtendLogController;
use App\Http\Controllers\SalesOrder\SalesOrderController;
use App\Http\Controllers\SalesOrder\SalesOrderControllerSSB;
use App\Http\Controllers\Settings\AccountManagementController;
use App\Http\Controllers\Settings\ApprovalCodesController;
use App\Http\Controllers\Settings\BuyerManagementController;
use App\Http\Controllers\Settings\ConnectionController;
use App\Http\Controllers\Settings\CostController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\DomainController;
use App\Http\Controllers\Settings\ExternalLinkController;
use App\Http\Controllers\Settings\FinanceEmailController;
use App\Http\Controllers\Settings\IconController;
use App\Http\Controllers\Settings\ItemController;
use App\Http\Controllers\Settings\MenuAccessController;
use App\Http\Controllers\Settings\MenuController;
use App\Http\Controllers\Settings\MenuStructureController;
use App\Http\Controllers\Settings\MonthlyPOSOEmailController;
use App\Http\Controllers\Settings\PrefixController;
use App\Http\Controllers\Settings\RetailPriceController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SuppliersController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\StockRequest\StockRequestConfirmController;
use App\Http\Controllers\StockRequest\StockRequestConfirmSSBController;
use App\Http\Controllers\StockRequest\StockRequestController;
use App\Http\Controllers\StockRequest\StockRequestSSBController;
use App\Http\Controllers\StockRequestShortage\StockRequestShortageController;
use App\Http\Controllers\StockRequestShortage\StockRequestShortageSSBController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\WAController;
use App\Models\API\SummaryEpoint;
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
Route::get('/', function () {
	if (Auth::check()) {
		return Redirect::to('home');
	}
	return view('auth.login');
})->name('defaultLogin');

Route::group(['middleware' => ['auth']], function () {
	Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

	// User
	Route::group(['middleware' => function ($request, $next) {
		$menuLink = 'users';

		if (Gate::allows('access_menu', $menuLink)) {
			return $next($request);
		}

		abort(
			403,
			'Sorry you do not have access to this menu'
		);
	}], function () {
		Route::resource('users', UserController::class);
		Route::post('/deleteUser', [UserController::class, 'delete'])->name('deleteUser');
		Route::put('/resetPassword', [UserController::class, 'resetPassword'])->name('resetPassword');
	});
});

Auth::routes();
Route::get('/home', 'HomeController@index')->name('home');
Route::post('changePassword', [UserController::class, 'changePassword'])->name('changePassword');
