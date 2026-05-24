<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TankController;
use App\Http\Controllers\DispenserController;
use App\Http\Controllers\NozzleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeShiftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TankRefillsController;
use App\Http\Controllers\TankDipReadingController;
use App\Http\Controllers\OwnerFuelUsageController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ReportController;




Route::prefix('reports')->name('reports.')->group(function () {

    Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');

    Route::get('/daily-sales', [ReportController::class, 'dailySales'])->name('daily-sales');
    Route::get('/daily-sales/pdf', [ReportController::class, 'dailySalesPdf'])->name('daily-sales.pdf');

    Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('/profit-loss/pdf', [ReportController::class, 'profitLossPdf'])->name('profit-loss.pdf');

    Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
    Route::get('/stock/pdf', [ReportController::class, 'stockPdf'])->name('stock.pdf');

    Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
    Route::get('/expenses/pdf', [ReportController::class, 'expensesPdf'])->name('expenses.pdf');

    Route::get('/variance', [ReportController::class, 'variance'])->name('variance');
    Route::get('/variance/pdf', [ReportController::class, 'variancePdf'])->name('variance.pdf');

});




Route::resource('expenses', ExpensesController::class);

Route::resource('owner-fuel-usages', OwnerFuelUsageController::class);

Route::resource(
    'tank-dip-readings',
    TankDipReadingController::class
);


Route::resource('tank-refills', TankRefillsController::class);

Route::get('/', [DashboardController::class, 'index']);





Route::resource('employee-shifts', EmployeeShiftController::class);
Route::get(
    'employee-shifts/{id}/close',
    [EmployeeShiftController::class, 'closeForm']
)->name('employee-shifts.close-form');

Route::post(
    'employee-shifts/{id}/close',
    [EmployeeShiftController::class, 'close']
)->name('employee-shifts.close');


Route::resource('employees', EmployeeController::class);

Route::resource('nozzles', NozzleController::class);

Route::resource('dispensers', DispenserController::class);


Route::resource('tanks', TankController::class);

Route::resource('products', ProductController::class);



/* Route::get('/', function () {
    return view('welcome');
}); */

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
