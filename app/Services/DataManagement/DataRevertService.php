<?php

namespace App\Services\DataManagement;

use App\Models\AgencyFuelCredit;
use App\Models\AgencyFuelPayment;
use App\Models\AuditLog;
use App\Models\CashTransaction;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\MobilOilPrice;
use App\Models\MobilOilProduct;
use App\Models\MobilOilPurchase;
use App\Models\MobilOilSale;
use App\Models\OwnerFuelUsage;
use App\Models\ProductPrice;
use App\Models\Tank;
use App\Models\TankDipReading;
use App\Models\TankRefill;
use App\Services\MobilOilStockService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataRevertService
{
    /**
     * Delete and reverse all operational data from $fromDate through latest.
     *
     * @return array<string, int>
     */
    public function revertFromDate(string $fromDate): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $fromDateStr = $from->toDateString();
        $counts = [];

        DB::transaction(function () use ($from, $fromDateStr, &$counts) {
            $counts['agency_fuel_payments'] = $this->revertAgencyPayments($fromDateStr);
            $counts['employee_shifts'] = $this->revertEmployeeShifts($fromDateStr);
            $counts['tank_refills'] = $this->revertTankRefills($from);
            $counts['tank_dip_readings'] = $this->revertDipReadings($from);
            $counts['mobil_oil_sales'] = $this->revertMobilOilSales($from);
            $counts['mobil_oil_purchases'] = $this->revertMobilOilPurchases($from);
            $counts['expenses'] = Expense::whereDate('expense_date', '>=', $fromDateStr)->delete();
            $counts['cash_transactions'] = CashTransaction::whereDate('transaction_date', '>=', $fromDateStr)->delete();
            $counts['employee_salaries'] = EmployeeSalary::whereDate('payment_date', '>=', $fromDateStr)->delete();
            $counts['employee_attendances'] = EmployeeAttendance::whereDate('attendance_date', '>=', $fromDateStr)->delete();
            $counts['product_prices'] = ProductPrice::where('effective_from', '>=', $from)->delete();
            $counts['mobil_oil_prices'] = MobilOilPrice::where('effective_from', '>=', $from)->delete();
            $counts['owner_fuel_usages'] = OwnerFuelUsage::where('usage_datetime', '>=', $from)->delete();
            $counts['agency_fuel_credits'] = $this->deleteLeftoverAgencyCredits($from);
            $counts['audit_logs'] = AuditLog::where('created_at', '>=', $from)->delete();
        });

        return $counts;
    }

    private function revertAgencyPayments(string $fromDateStr): int
    {
        $payments = AgencyFuelPayment::query()
            ->with('credit')
            ->whereDate('payment_date', '>=', $fromDateStr)
            ->orderByDesc('id')
            ->get();

        $count = 0;

        foreach ($payments as $payment) {
            $credit = $payment->credit;
            $payment->delete();

            if ($credit) {
                $credit->refreshPaymentStatus();
            }

            $count++;
        }

        return $count;
    }

    private function revertEmployeeShifts(string $fromDateStr): int
    {
        $closed = EmployeeShift::query()
            ->with(['nozzle.tank', 'ownerFuelUsage', 'agencyFuelCredit.payments'])
            ->whereNotNull('closed_date')
            ->whereDate('closed_date', '>=', $fromDateStr)
            ->orderByDesc('closed_date')
            ->orderByDesc('id')
            ->get();

        $count = 0;

        foreach ($closed as $shift) {
            $ownerLiters = (float) ($shift->ownerFuelUsage?->liters ?? 0);
            $agencyLiters = (float) ($shift->agencyFuelCredit?->liters ?? 0);
            $stockOut = (float) $shift->total_liters + $ownerLiters + $agencyLiters;

            $tank = $shift->nozzle?->tank;
            if ($tank && $stockOut > 0) {
                StockService::increment($tank->fresh(), $stockOut);
            }

            $nozzle = $shift->nozzle;
            if ($nozzle && $shift->closing_reading !== null) {
                $current = (float) $nozzle->current_meter_reading;
                $closing = (float) $shift->closing_reading;
                if (abs($current - $closing) < 0.011) {
                    $nozzle->update(['current_meter_reading' => $shift->opening_reading]);
                }
            }

            if ($shift->agencyFuelCredit) {
                $shift->agencyFuelCredit->payments()->delete();
                $shift->agencyFuelCredit->delete();
            }

            $shift->ownerFuelUsage?->delete();
            $shift->delete();
            $count++;
        }

        $openShifts = EmployeeShift::query()
            ->whereNull('closed_date')
            ->whereDate('assigned_date', '>=', $fromDateStr)
            ->get();

        foreach ($openShifts as $shift) {
            $shift->delete();
            $count++;
        }

        return $count;
    }

    private function revertTankRefills(Carbon $from): int
    {
        $refills = TankRefill::query()
            ->where('received_datetime', '>=', $from)
            ->orderByDesc('received_datetime')
            ->orderByDesc('id')
            ->get();

        $count = 0;

        foreach ($refills as $refill) {
            $tank = Tank::find($refill->tank_id);
            $qty = (float) $refill->quantity_liters;

            if ($tank && $qty > 0) {
                if (! StockService::canDecrement($tank, $qty)) {
                    // Force stock down to zero floor if history is inconsistent.
                    $tank->update([
                        'current_stock_liters' => max(0, (float) $tank->current_stock_liters - $qty),
                    ]);
                } else {
                    StockService::decrement($tank, $qty);
                }
            }

            $refill->delete();
            $count++;
        }

        return $count;
    }

    private function revertDipReadings(Carbon $from): int
    {
        $readings = TankDipReading::query()
            ->where('reading_datetime', '>=', $from)
            ->orderByDesc('reading_datetime')
            ->orderByDesc('id')
            ->get();

        $count = 0;

        foreach ($readings as $reading) {
            if ($reading->stock_reconciled && $reading->system_stock_liters !== null) {
                $tank = Tank::find($reading->tank_id);
                if ($tank) {
                    StockService::reconcile($tank, (float) $reading->system_stock_liters);
                }
            }

            $reading->delete();
            $count++;
        }

        return $count;
    }

    private function revertMobilOilSales(Carbon $from): int
    {
        $sales = MobilOilSale::query()
            ->where('sold_datetime', '>=', $from)
            ->orderByDesc('sold_datetime')
            ->orderByDesc('id')
            ->get();

        $count = 0;

        foreach ($sales as $sale) {
            $product = MobilOilProduct::find($sale->mobil_oil_product_id);
            if ($product) {
                MobilOilStockService::increment($product, (float) $sale->quantity);
            }
            $sale->delete();
            $count++;
        }

        return $count;
    }

    private function revertMobilOilPurchases(Carbon $from): int
    {
        $purchases = MobilOilPurchase::query()
            ->where('received_datetime', '>=', $from)
            ->orderByDesc('received_datetime')
            ->orderByDesc('id')
            ->get();

        $count = 0;

        foreach ($purchases as $purchase) {
            $product = MobilOilProduct::find($purchase->mobil_oil_product_id);
            $qty = (float) $purchase->quantity;

            if ($product && $qty > 0) {
                if (MobilOilStockService::canDecrement($product, $qty)) {
                    MobilOilStockService::decrement($product, $qty);
                } else {
                    $product->update([
                        'current_stock_qty' => max(0, (float) $product->current_stock_qty - $qty),
                    ]);
                }
            }

            $purchase->delete();
            $count++;
        }

        return $count;
    }

    private function deleteLeftoverAgencyCredits(Carbon $from): int
    {
        $credits = AgencyFuelCredit::query()
            ->where('credit_datetime', '>=', $from)
            ->get();

        $count = 0;

        foreach ($credits as $credit) {
            $credit->payments()->delete();
            $credit->delete();
            $count++;
        }

        return $count;
    }
}
