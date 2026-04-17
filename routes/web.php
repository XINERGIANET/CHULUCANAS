<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\WebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\QuotaController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DistrictController;


Route::get('optimize', function () {
	Artisan::call('optimize:clear');
});


Route::get('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('login', [AuthController::class, 'check'])->name('auth.check');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');


Route::middleware('auth')->group(function () {

	Route::get('/', function () {
		return redirect('/dashboard/indicadores');
	});

	Route::get('/dashboard/productividad', [WebController::class, 'productividad'])->name('dashboard.productividad');
	Route::get('/dashboard/rentabilidad', [WebController::class, 'rentabilidad'])->name('dashboard.rentabilidad');
	Route::get('/dashboard/rentabilidad/card-details', [WebController::class, 'rentabilidadCardDetails'])->name('dashboard.rentabilidad.card-details');
	Route::get('/dashboard/indicadores', [WebController::class, 'indicadores'])->name('dashboard.indicadores');


	Route::get('api/reniec', [WebController::class, 'apiReniec'])->name('api.reniec');

	Route::get('clients/quotas', [ClientController::class, 'quotas'])->name('clients.quotas');
	Route::get('clients/contracts', [ClientController::class, 'contracts'])->name('clients.contracts');
	Route::get('clients/details', [ClientController::class, 'details'])->name('clients.details');
	Route::get('clients/edit', [ClientController::class, 'edit'])->name('clients.edit');
	Route::get('clients/check', [ClientController::class, 'check'])->name('clients.check');
	Route::get('clients/api', [ClientController::class, 'api'])->name('clients.api');
	Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
	Route::put('clients/update-person', [ClientController::class, 'updatePerson'])->name('clients.update-person');
	Route::get('contracts/api', [ContractController::class, 'api'])->name('contracts.api');
	Route::get('contracts/ending', [ContractController::class, 'ending'])->name('contracts.ending');
	Route::get('contracts/ending/excel', [ContractController::class, 'endingExcel'])->name('contracts.ending.excel');
	Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
	Route::get('contracts/{contract}/pdf1', [ContractController::class, 'pdf1Blade'])->name('contracts.pdf1');
	Route::get('contracts/{contract}/pdf2', [ContractController::class, 'pdf2Blade'])->name('contracts.pdf2');
	Route::get('contracts/{contract}/pdf3', [ContractController::class, 'pdf3Blade'])->name('contracts.pdf3');
	Route::resource('contracts', ContractController::class);

	Route::get('quotas/api', [QuotaController::class, 'api'])->name('quotas.api');
	Route::get('quotas', [QuotaController::class, 'index'])->name('quotas.index');
	Route::get('quotas/excel', [QuotaController::class, 'excel'])->name('quotas.excel');
	Route::get('quotas/clients', [QuotaController::class, 'clients'])->name('quotas.clients');
	Route::get('districts/api', [DistrictController::class, 'api'])->name('districts.api');

	Route::get('payments/charges', [PaymentController::class, 'charges'])->name('payments.charges');
	Route::get('contracts/charges/excel', [PaymentController::class, 'chargesExcel'])->name('payments.charges.excel');

	Route::get('payments/dues/excel', [PaymentController::class, 'duesExcel'])->name('payments.dues.excel');
	Route::get('payments/dues', [PaymentController::class, 'dues'])->name('payments.dues');
	Route::get('payments/{payment}/group-payments', [PaymentController::class, 'getGroupPayments'])->name('payments.group-payments');
	Route::resource('payments', PaymentController::class);

	Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
	Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

	Route::get('expenses/excel', [ExpenseController::class, 'excel'])->name('expenses.excel');
	Route::get('expenses/index_cash', [ExpenseController::class, 'index_cash'])->name('expenses.index_cash');
	Route::get('expenses/excel_cash', [ExpenseController::class, 'excel_cash'])->name('expenses.excel_cash');
	Route::resource('expenses', ExpenseController::class);

	Route::middleware('role:admin,credit_manager')->group(function () {
		Route::put('sellers/drop/{id}', [SellerController::class, 'drop'])->name('sellers.drop');
		Route::put('sellers/up/{id}', [SellerController::class, 'up'])->name('sellers.up');

		Route::resource('sellers', SellerController::class);

		Route::resource('transfers', TransferController::class);

		Route::get('interests/monthly', [InterestController::class, 'index'])->name('interests.monthly');
		Route::get('interests/sbs', [InterestController::class, 'report_sbs'])->name('interests.sbs');
		Route::get('interests/sbs-download', [InterestController::class, 'download_sbs'])->name('interests.excel_sbs');
	});
});
