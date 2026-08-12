<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TankController;
use App\Http\Controllers\DispenserController;
use App\Http\Controllers\NozzleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\EmployeeShiftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TankRefillsController;
use App\Http\Controllers\TankDipReadingController;
use App\Http\Controllers\AgencyCustomerController;
use App\Http\Controllers\OwnerFuelUsageController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProductPriceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MobilOilProductController;
use App\Http\Controllers\MobilOilPurchaseController;
use App\Http\Controllers\MobilOilSaleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Middleware\LogAuditTrail;

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::middleware(['auth', LogAuditTrail::class])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/daily-sales', [ReportController::class, 'dailySales'])->name('daily-sales');
        Route::get('/daily-sales/pdf', [ReportController::class, 'dailySalesPdf'])->name('daily-sales.pdf');
        Route::get('/daily-sales/csv', [ReportController::class, 'dailySalesCsv'])->name('daily-sales.csv');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/profit-loss/pdf', [ReportController::class, 'profitLossPdf'])->name('profit-loss.pdf');
        Route::get('/profit-loss/csv', [ReportController::class, 'profitLossCsv'])->name('profit-loss.csv');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/stock/pdf', [ReportController::class, 'stockPdf'])->name('stock.pdf');
        Route::get('/stock/csv', [ReportController::class, 'stockCsv'])->name('stock.csv');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('/expenses/pdf', [ReportController::class, 'expensesPdf'])->name('expenses.pdf');
        Route::get('/expenses/csv', [ReportController::class, 'expensesCsv'])->name('expenses.csv');
        Route::get('/variance', [ReportController::class, 'variance'])->name('variance');
        Route::get('/variance/pdf', [ReportController::class, 'variancePdf'])->name('variance.pdf');
        Route::get('/variance/csv', [ReportController::class, 'varianceCsv'])->name('variance.csv');
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('/attendance/pdf', [ReportController::class, 'attendancePdf'])->name('attendance.pdf');
        Route::get('/attendance/csv', [ReportController::class, 'attendanceCsv'])->name('attendance.csv');
        Route::get('/mobil-oil-sales', [ReportController::class, 'mobilOilSales'])->name('mobil-oil-sales');
        Route::get('/mobil-oil-sales/pdf', [ReportController::class, 'mobilOilSalesPdf'])->name('mobil-oil-sales.pdf');
        Route::get('/mobil-oil-sales/csv', [ReportController::class, 'mobilOilSalesCsv'])->name('mobil-oil-sales.csv');
        Route::get('/cash', [ReportController::class, 'cash'])->name('cash');
        Route::get('/cash/pdf', [ReportController::class, 'cashPdf'])->name('cash.pdf');
        Route::get('/cash/csv', [ReportController::class, 'cashCsv'])->name('cash.csv');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/purchases/pdf', [ReportController::class, 'purchasesPdf'])->name('purchases.pdf');
        Route::get('/purchases/csv', [ReportController::class, 'purchasesCsv'])->name('purchases.csv');
        Route::get('/shifts', [ReportController::class, 'shifts'])->name('shifts');
        Route::get('/shifts/pdf', [ReportController::class, 'shiftsPdf'])->name('shifts.pdf');
        Route::get('/shifts/csv', [ReportController::class, 'shiftsCsv'])->name('shifts.csv');
    });

    Route::prefix('mobil-oil')->name('mobil-oil.')->group(function () {
        Route::resource('products', MobilOilProductController::class)->except(['show', 'destroy']);
        Route::resource('purchases', MobilOilPurchaseController::class)->only(['index', 'create', 'store']);
        Route::resource('sales', MobilOilSaleController::class)->only(['index', 'create', 'store']);
    });

    Route::resource('expenses', ExpensesController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('employee-salaries', EmployeeSalaryController::class)->except(['show']);
    Route::get('reports/employee-salaries', [EmployeeSalaryController::class, 'report'])->name('reports.employee-salaries');
    Route::get('reports/employee-salaries/pdf', [EmployeeSalaryController::class, 'reportPdf'])->name('reports.employee-salaries.pdf');
    Route::resource('cash-transactions', CashTransactionController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('owner-fuel-usages', OwnerFuelUsageController::class)->only(['index']);
    Route::resource('agency-customers', AgencyCustomerController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('agency-credits/{credit}/payments', [AgencyCustomerController::class, 'storePayment'])
        ->name('agency-customers.credits.pay');
    Route::resource('tank-dip-readings', TankDipReadingController::class)->only(['index', 'create', 'store']);
    Route::resource('tank-refills', TankRefillsController::class)->only(['index', 'create', 'store']);

    Route::resource('employee-shifts', EmployeeShiftController::class)->only(['index', 'create', 'store']);
    Route::get('employee-shifts/{id}/edit', [EmployeeShiftController::class, 'edit'])->name('employee-shifts.edit');
    Route::put('employee-shifts/{id}', [EmployeeShiftController::class, 'update'])->name('employee-shifts.update');
    Route::get('employee-shifts/{id}/close', [EmployeeShiftController::class, 'closeForm'])->name('employee-shifts.close-form');
    Route::post('employee-shifts/{id}/close', [EmployeeShiftController::class, 'close'])->name('employee-shifts.close');
    Route::post('employee-shifts/{id}/verify', [EmployeeShiftController::class, 'verify'])->name('employee-shifts.verify');

    Route::get('employees/{employee}/ledger', [EmployeeController::class, 'ledger'])->name('employees.ledger');
    Route::get('employees/{employee}/ledger/pdf', [EmployeeController::class, 'ledgerPdf'])->name('employees.ledger.pdf');
    Route::resource('employees', EmployeeController::class);
    Route::resource('employee-attendances', EmployeeAttendanceController::class);
    Route::resource('nozzles', NozzleController::class);
    Route::resource('dispensers', DispenserController::class);
    Route::resource('tanks', TankController::class);

    Route::resource('product-prices', ProductPriceController::class)->only(['index', 'create', 'store']);

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/pdf', [AuditLogController::class, 'pdf'])->name('audit-logs.pdf');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
