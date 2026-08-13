<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\MobilOilProduct;
use App\Models\MobilOilPurchase;
use App\Models\MobilOilSale;
use App\Models\ProductPrice;
use App\Models\Tank;
use App\Models\OwnerFuelUsage;
use App\Models\EmployeeSalary;
use App\Models\TankDipReading;
use App\Models\TankRefill;
use App\Services\BusinessDayService;
use App\Support\DailyCashLedger;
use App\Support\DailyFuelMetrics;
use App\Support\FuelProducts;
use App\Support\MobilOilSalesMetrics;
use App\Support\ReportRange;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PDF;

class ReportController extends Controller
{
    public function dashboard()
    {
        return view('reports.dashboard');
    }

    private function getReportRange(Request $request): array
    {
        return ReportRange::fromRequest($request);
    }

    /*
    |--------------------------------------------------------------------------
    | DAILY SALES
    |--------------------------------------------------------------------------
    */
    private function getDailySalesRange(Request $request)
    {
        return $this->getReportRange($request);
    }

    private function getDailySalesData(Request $request)
    {
        $range = $this->getDailySalesRange($request);

        $query = EmployeeShift::with('employee', 'nozzle.product');

        if ($range['from'] && $range['to']) {
            $query->closedBetween($range['from'], $range['to']);
        }

        $shifts = $query->latest()->get();
        $totalAmount = $shifts->sum('total_amount');
        $totalLiters = $shifts->sum('total_liters');
        $totalCash = $shifts->sum('cash_received');
        $totalOnline = $shifts->sum('online_received');

        $productBreakdown = DailyFuelMetrics::byProduct($range['from'], $range['to']);
        $mobilOilBreakdown = MobilOilSalesMetrics::byProduct($range['fromAt'], $range['toAt']);

        $fuelByDay = DailyFuelMetrics::dailyByProduct($range['from'], $range['to']);
        $mobilOilByDay = MobilOilSalesMetrics::dailyTotals($range['fromAt'], $range['toAt']);
        $infoByDay = $this->dailySalesInfoByDay($range['from'], $range['to'], $range['fromAt'], $range['toAt']);

        $dates = $fuelByDay->keys()
            ->merge($mobilOilByDay->keys())
            ->merge($infoByDay->keys())
            ->unique()
            ->sort()
            ->values();

        $closingByDay = DailyFuelMetrics::dailyClosingStock($range['from'], $range['to'], $dates);

        $totalAmount += (float) $mobilOilBreakdown->sum('sales_amount');
        $totalCash += (float) $mobilOilBreakdown->sum('cash');
        $totalOnline += (float) $mobilOilBreakdown->sum('online');

        $empty = DailyFuelMetrics::emptyProductDay();

        $dailyTotals = $dates->map(function (string $date) use ($fuelByDay, $mobilOilByDay, $closingByDay, $infoByDay, $empty) {
            $fuel = $fuelByDay->get($date, ['petrol' => $empty, 'diesel' => $empty]);
            $petrol = $fuel['petrol'] ?? $empty;
            $diesel = $fuel['diesel'] ?? $empty;
            $mobilOil = $mobilOilByDay->get($date, $empty);
            $stockDay = $closingByDay->get($date, [
                'petrol' => ['stock_opening' => 0.0, 'stock_closing' => 0.0],
                'diesel' => ['stock_opening' => 0.0, 'stock_closing' => 0.0],
            ]);

            $dayAmount = (float) $petrol['sales_amount'] + (float) $diesel['sales_amount'] + (float) $mobilOil['sales_amount'];
            $dayCash = (float) $petrol['cash'] + (float) $diesel['cash'] + (float) $mobilOil['cash'];
            $dayOnline = (float) $petrol['online'] + (float) $diesel['online'] + (float) $mobilOil['online'];
            $dayProfit = (float) $petrol['total_profit'] + (float) $diesel['total_profit'] + (float) $mobilOil['total_profit'];

            $petrol = array_merge($petrol, [
                'stock_opening' => (float) ($stockDay['petrol']['stock_opening'] ?? 0),
                'stock_closing' => (float) ($stockDay['petrol']['stock_closing'] ?? 0),
                'show_stock' => true,
            ]);
            $diesel = array_merge($diesel, [
                'stock_opening' => (float) ($stockDay['diesel']['stock_opening'] ?? 0),
                'stock_closing' => (float) ($stockDay['diesel']['stock_closing'] ?? 0),
                'show_stock' => true,
            ]);

            return [
                'date' => $date,
                'label' => report_date($date),
                'petrol' => $petrol,
                'diesel' => $diesel,
                'mobil_oil' => $mobilOil,
                'total_amount' => round($dayAmount, 2),
                'total_cash' => round($dayCash, 2),
                'total_online' => round($dayOnline, 2),
                'total_profit' => round($dayProfit, 2),
                'total_liters' => round((float) $petrol['liters'] + (float) $diesel['liters'], 2),
                'infos' => $infoByDay->get($date, collect())->values()->all(),
            ];
        });

        return array_merge($range, compact(
            'shifts',
            'totalAmount',
            'totalLiters',
            'totalCash',
            'totalOnline',
            'dailyTotals',
            'productBreakdown',
            'mobilOilBreakdown'
        ));
    }

    /**
     * Info notices keyed by business date (Petrol/Diesel stock refill / sale price change).
     *
     * @return Collection<string, Collection<int, array{type: string, message: string}>>
     */
    private function dailySalesInfoByDay(string $from, string $to, ?Carbon $fromAt, ?Carbon $toAt): Collection
    {
        if (! $fromAt || ! $toAt) {
            return collect();
        }

        $fuelIds = FuelProducts::ids();
        $productIds = array_values($fuelIds);

        $byDay = collect();

        $push = function (string $date, string $type, string $message) use (&$byDay, $from, $to): void {
            if ($date < $from || $date > $to) {
                return;
            }
            if (! $byDay->has($date)) {
                $byDay[$date] = collect();
            }
            $byDay[$date]->push([
                'type' => $type,
                'message' => $message,
            ]);
        };

        $refills = TankRefill::query()
            ->with('product:id,name')
            ->whereIn('product_id', $productIds)
            ->whereBetween('received_datetime', [$fromAt, $toAt])
            ->orderBy('received_datetime')
            ->get();

        foreach ($refills as $refill) {
            $date = BusinessDayService::toBusinessDate($refill->received_datetime)->toDateString();
            $product = $refill->product?->name ?? 'Fuel';
            $qty = number_format((float) $refill->quantity_liters, 2);
            $rate = rate($refill->purchase_rate);
            $push($date, 'refill', "Stock refill — {$product}: +{$qty} L @ purchase {$rate}");
        }

        $fuelPrices = ProductPrice::query()
            ->with('product:id,name')
            ->whereIn('product_id', $productIds)
            ->whereBetween('effective_from', [$fromAt, $toAt])
            ->orderBy('effective_from')
            ->get();

        foreach ($fuelPrices as $price) {
            $date = BusinessDayService::toBusinessDate($price->effective_from)->toDateString();
            $product = $price->product?->name ?? 'Fuel';
            $previous = ProductPrice::query()
                ->where('product_id', $price->product_id)
                ->where(function ($q) use ($price) {
                    $q->where('effective_from', '<', $price->effective_from)
                        ->orWhere(function ($q2) use ($price) {
                            $q2->where('effective_from', $price->effective_from)
                                ->where('id', '<', $price->id);
                        });
                })
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->value('price');

            if ($previous !== null) {
                $push($date, 'price_change', "Price change — {$product}: ".rate($previous).' → '.rate($price->price));
            } else {
                $push($date, 'price_change', "Sale price set — {$product}: ".rate($price->price));
            }
        }

        return $byDay;
    }

    public function dailySales(Request $request)
    {
        return view('reports.daily_sales', $this->getDailySalesData($request));
    }

    public function dailySalesPdf(Request $request)
    {
        $data = $this->getDailySalesData($request);
        $pdf = PDF::loadView('reports.pdf.daily_sales', $data);

        return $pdf->download('daily-sales-report.pdf');
    }

    public function dailySalesCsv(Request $request)
    {
        $data = $this->getDailySalesData($request);
        $dailyTotals = $data['dailyTotals'];
        $productBreakdown = $data['productBreakdown'];
        $mobilOilBreakdown = $data['mobilOilBreakdown'];
        $filename = 'daily-sales-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($dailyTotals, $productBreakdown, $mobilOilBreakdown, $data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Daily Sales Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);

            $this->writeFuelProductColumnsCsv($f, $productBreakdown, true);
            $this->writeMobilOilBreakdownCsv($f, $mobilOilBreakdown);

            fputcsv($f, []);
            fputcsv($f, ['Daily Breakdown']);
            fputcsv($f, [
                'Date',
                'Petrol Close (L)',
                'Petrol Rate × Qty',
                'Petrol Amount',
                'Petrol Profit',
                'Diesel Close (L)',
                'Diesel Rate × Qty',
                'Diesel Amount',
                'Diesel Profit',
                'Mobil Oil Rate × Qty',
                'Mobil Oil Amount',
                'Mobil Oil Profit',
                'Total Amount',
                'Cash',
                'Bank',
                'Profit',
            ]);

            $stackExpr = function (array $row): string {
                $segments = collect($row['segments'] ?? [])
                    ->filter(fn ($s) => ((float) ($s['liters'] ?? 0)) > 0 || ((float) ($s['sales_amount'] ?? 0)) > 0)
                    ->values();

                if ($segments->isEmpty()) {
                    $qty = (float) ($row['liters'] ?? $row['quantity'] ?? 0);
                    $amount = (float) ($row['sales_amount'] ?? 0);
                    if ($qty <= 0 && $amount <= 0) {
                        return '';
                    }

                    $rate = $row['sale_rate'] !== null ? rate($row['sale_rate']) : '—';

                    return $rate.' × '.number_format($qty, 2);
                }

                return $segments->map(function (array $segment) {
                    $qty = (float) ($segment['liters'] ?? $segment['quantity'] ?? 0);
                    $rate = ($segment['sale_rate'] ?? null) !== null ? rate($segment['sale_rate']) : '—';

                    return $rate.' × '.number_format($qty, 2);
                })->implode(' | ');
            };

            foreach ($dailyTotals as $day) {
                fputcsv($f, [
                    $day['label'],
                    number_format($day['petrol']['stock_closing'] ?? 0, 2),
                    $stackExpr($day['petrol']),
                    money($day['petrol']['sales_amount'] ?? 0),
                    money($day['petrol']['total_profit'] ?? 0),
                    number_format($day['diesel']['stock_closing'] ?? 0, 2),
                    $stackExpr($day['diesel']),
                    money($day['diesel']['sales_amount'] ?? 0),
                    money($day['diesel']['total_profit'] ?? 0),
                    $stackExpr($day['mobil_oil']),
                    money($day['mobil_oil']['sales_amount'] ?? 0),
                    money($day['mobil_oil']['total_profit'] ?? 0),
                    money($day['total_amount']),
                    money($day['total_cash']),
                    money($day['total_online']),
                    money($day['total_profit']),
                ]);

                foreach (($day['infos'] ?? []) as $info) {
                    fputcsv($f, [
                        $day['label'],
                        $info['message'] ?? '',
                    ]);
                }
            }

            $lastDay = $dailyTotals->last();

            fputcsv($f, []);
            fputcsv($f, [
                'Grand Total',
                number_format($lastDay['petrol']['stock_closing'] ?? 0, 2),
                '',
                money($dailyTotals->sum(fn ($d) => $d['petrol']['sales_amount'] ?? 0)),
                money($dailyTotals->sum(fn ($d) => $d['petrol']['total_profit'] ?? 0)),
                number_format($lastDay['diesel']['stock_closing'] ?? 0, 2),
                '',
                money($dailyTotals->sum(fn ($d) => $d['diesel']['sales_amount'] ?? 0)),
                money($dailyTotals->sum(fn ($d) => $d['diesel']['total_profit'] ?? 0)),
                '',
                money($dailyTotals->sum(fn ($d) => $d['mobil_oil']['sales_amount'] ?? 0)),
                money($dailyTotals->sum(fn ($d) => $d['mobil_oil']['total_profit'] ?? 0)),
                money($dailyTotals->sum('total_amount')),
                money($dailyTotals->sum('total_cash')),
                money($dailyTotals->sum('total_online')),
                money($dailyTotals->sum('total_profit')),
            ]);

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | PROFIT & LOSS
    |--------------------------------------------------------------------------
    */
    private function getProfitLossData(Request $request): array
    {
        $range = $this->getReportRange($request);
        $from = $range['from'];
        $to = $range['to'];
        $fromAt = $range['fromAt'];
        $toAt = $range['toAt'];

        $fuelSales = (float) EmployeeShift::closedBetween($from, $to)->sum('total_amount');
        $salesLiters = (float) EmployeeShift::closedBetween($from, $to)->sum('total_liters');
        $salesCount = EmployeeShift::closedBetween($from, $to)->count();

        $mobilOilSales = (float) MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->sum('total_amount');
        $mobilOilSalesQty = (float) MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->sum('quantity');
        $mobilOilSalesCount = MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->count();

        $sales = $fuelSales + $mobilOilSales;

        $expenses = (float) Expense::operating()->whereBetween('expense_date', [$from, $to])->sum('amount');
        $expenseCount = Expense::operating()->whereBetween('expense_date', [$from, $to])->count();
        $salaries = (float) EmployeeSalary::whereBetween('payment_date', [$from, $to])->sum('amount');
        $salaryCount = EmployeeSalary::whereBetween('payment_date', [$from, $to])->count();
        $ownerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])->sum('total_amount');
        $ownerFuelLiters = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])->sum('liters');
        $ownerFuelCount = OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])->count();

        $refillCogs = (float) TankRefill::whereBetween('received_datetime', [$fromAt, $toAt])->sum('total_amount');
        $refillLiters = (float) TankRefill::whereBetween('received_datetime', [$fromAt, $toAt])->sum('quantity_liters');

        $mobilOilCogs = (float) MobilOilPurchase::whereBetween('received_datetime', [$fromAt, $toAt])->sum('total_amount');
        $mobilOilPurchaseQty = (float) MobilOilPurchase::whereBetween('received_datetime', [$fromAt, $toAt])->sum('quantity');

        $totalCosts = $expenses + $salaries + $refillCogs + $mobilOilCogs;
        $grossProfit = $sales - $expenses - $salaries;
        $netProfit = $sales - $totalCosts;
        $profitMargin = $sales > 0 ? round(($grossProfit / $sales) * 100, 2) : 0;
        $expenseRatio = $sales > 0 ? round(($expenses / $sales) * 100, 2) : 0;
        $salaryRatio = $sales > 0 ? round(($salaries / $sales) * 100, 2) : 0;
        $ownerFuelRatio = $sales > 0 ? round(($ownerFuel / $sales) * 100, 2) : 0;

        $productBreakdown = DailyFuelMetrics::byProduct($from, $to);
        $mobilOilBreakdown = MobilOilSalesMetrics::byProduct($fromAt, $toAt);

        $fuelSalesProfit = (float) $productBreakdown->sum('total_profit');
        $mobilOilSalesProfit = (float) $mobilOilBreakdown->sum('total_profit');
        $totalSalesProfit = $fuelSalesProfit + $mobilOilSalesProfit;
        // Owner fuel liters are already removed from shift sales on close — do not deduct again.
        $operatingAndOwnerTotal = $expenses + $salaries;
        $netSalesProfit = $totalSalesProfit - $expenses - $salaries;

        return array_merge($range, compact(
            'sales',
            'fuelSales',
            'mobilOilSales',
            'mobilOilSalesQty',
            'mobilOilSalesCount',
            'salesLiters',
            'salesCount',
            'expenses',
            'expenseCount',
            'salaries',
            'salaryCount',
            'ownerFuel',
            'ownerFuelLiters',
            'ownerFuelCount',
            'refillCogs',
            'refillLiters',
            'mobilOilCogs',
            'mobilOilPurchaseQty',
            'totalCosts',
            'grossProfit',
            'netProfit',
            'profitMargin',
            'expenseRatio',
            'salaryRatio',
            'ownerFuelRatio',
            'productBreakdown',
            'mobilOilBreakdown',
            'fuelSalesProfit',
            'mobilOilSalesProfit',
            'totalSalesProfit',
            'operatingAndOwnerTotal',
            'netSalesProfit'
        ));
    }

    public function profitLoss(Request $request)
    {
        return view('reports.profit_loss', $this->getProfitLossData($request));
    }

    public function profitLossPdf(Request $request)
    {
        $data = $this->getProfitLossData($request);
        $pdf = PDF::loadView('reports.pdf.profit_loss', $data);

        return $pdf->download('profit-loss-report.pdf');
    }

    public function profitLossCsv(Request $request)
    {
        $data = $this->getProfitLossData($request);
        $filename = 'profit-loss-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Profit & Loss Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Filter', ucfirst(str_replace('-', ' ', $data['filter']))]);
            fputcsv($f, ['Profit Margin %', $data['profitMargin']]);

            $this->writeFuelProductColumnsCsv($f, $data['productBreakdown'], simple: true);
            $this->writeMobilOilBreakdownCsv($f, $data['mobilOilBreakdown'] ?? collect());

            fputcsv($f, []);
            fputcsv($f, ['Total Sales & Profit']);
            fputcsv($f, ['Category', 'Sales (PKR)', 'Profit/Loss (PKR)']);
            fputcsv($f, ['Petroleum', money($data['fuelSales']), money($data['fuelSalesProfit'])]);
            fputcsv($f, ['Mobil Oil', money($data['mobilOilSales']), money($data['mobilOilSalesProfit'])]);
            fputcsv($f, ['Total', money($data['sales']), money($data['totalSalesProfit'])]);
            fputcsv($f, ['Operating Expenses', '', '- '.money($data['expenses'])]);
            fputcsv($f, ['Employee Salaries', '', '- '.money($data['salaries'])]);
            fputcsv($f, ['Owner Fuel Usage (excluded from sales)', '', money($data['ownerFuel'])]);
            fputcsv($f, ['Total Operating Expense', '', '- '.money($data['operatingAndOwnerTotal'])]);
            fputcsv($f, ['Net Profit (Inc. Total Expense)', '', money($data['netSalesProfit'])]);

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK REPORT
    |--------------------------------------------------------------------------
    */
    private function getStockData(): array
    {
        $tanks = Tank::with('product')->orderBy('tank_number')->get();

        $rows = $tanks->map(function ($tank) {
            $capacity = (float) $tank->capacity_liters;
            $stock = (float) $tank->current_stock_liters;
            $minimum = (float) $tank->minimum_level;
            $fillPercent = $capacity > 0 ? round(($stock / $capacity) * 100, 1) : 0;

            return [
                'tank_number' => $tank->tank_number,
                'product' => $tank->product->name ?? 'N/A',
                'capacity' => $capacity,
                'current_stock' => $stock,
                'minimum_level' => $minimum,
                'available' => max(0, $capacity - $stock),
                'fill_percent' => $fillPercent,
                'is_low' => $stock <= $minimum,
                'status' => $tank->status ?? 'active',
            ];
        });

        return [
            'tanks' => $rows,
            'totalCapacity' => $rows->sum('capacity'),
            'totalStock' => $rows->sum('current_stock'),
            'avgFillPercent' => $rows->avg('fill_percent') ?? 0,
            'tankCount' => $rows->count(),
            'lowStockCount' => $rows->where('is_low', true)->count(),
            'mobilOilProducts' => $this->getMobilOilStockRows(),
            'generatedAt' => now()->format('d M Y, h:i A'),
        ];
    }

    private function getMobilOilStockRows()
    {
        return MobilOilProduct::orderBy('name')->get()->map(function ($product) {
            $stock = (float) $product->current_stock_qty;
            $minimum = (float) $product->minimum_level;

            return [
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'current_stock' => $stock,
                'minimum_level' => $minimum,
                'is_low' => $stock <= $minimum,
                'status' => $product->status ? 'Active' : 'Inactive',
            ];
        });
    }

    public function stock()
    {
        $data = $this->getStockData();
        $data['mobilOilLowCount'] = collect($data['mobilOilProducts'])->where('is_low', true)->count();
        $data['mobilOilProductCount'] = collect($data['mobilOilProducts'])->count();

        return view('reports.stock', $data);
    }

    public function stockPdf()
    {
        $data = $this->getStockData();
        $pdf = PDF::loadView('reports.pdf.stock', $data);

        return $pdf->download('stock-report.pdf');
    }

    public function stockCsv()
    {
        $data = $this->getStockData();
        $filename = 'stock-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Tank', 'Product', 'Capacity (L)', 'Current Stock (L)', 'Available (L)', 'Min Level (L)', 'Fill %', 'Status', 'Alert'];

        $callback = function () use ($data, $columns) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Tank Stock Report']);
            fputcsv($f, ['Generated', $data['generatedAt']]);
            fputcsv($f, ['Tanks', $data['tankCount']]);
            fputcsv($f, ['Total Capacity (L)', number_format($data['totalCapacity'], 2)]);
            fputcsv($f, ['Total Stock (L)', number_format($data['totalStock'], 2)]);
            fputcsv($f, ['Low Stock Alerts', $data['lowStockCount']]);
            fputcsv($f, []);
            fputcsv($f, $columns);

            foreach ($data['tanks'] as $t) {
                fputcsv($f, [
                    $t['tank_number'],
                    $t['product'],
                    number_format($t['capacity'], 2),
                    number_format($t['current_stock'], 2),
                    number_format($t['available'], 2),
                    number_format($t['minimum_level'], 2),
                    $t['fill_percent'] . '%',
                    $t['status'],
                    $t['is_low'] ? 'Low Stock' : 'OK',
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | EXPENSE REPORT
    |--------------------------------------------------------------------------
    */
    private function getExpensesRange(Request $request)
    {
        return $this->getReportRange($request);
    }

    private function getExpensesData(Request $request)
    {
        $range = $this->getExpensesRange($request);

        $query = Expense::operating();

        if ($range['from'] && $range['to']) {
            $query->whereBetween('expense_date', [$range['from'], $range['to']]);
        }

        $expenses = $query->latest('expense_date')->get();
        $totalAmount = $expenses->sum('amount');
        $totalRecords = $expenses->count();

        $dailyTotals = $expenses
            ->groupBy(fn ($expense) => \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'label' => \Carbon\Carbon::parse($date)->format('d M Y'),
                    'total_amount' => $group->sum('amount'),
                    'record_count' => $group->count(),
                ];
            })
            ->values();

        return array_merge($range, compact('expenses', 'totalAmount', 'totalRecords', 'dailyTotals'));
    }

    public function expenses(Request $request)
    {
        return view('reports.expenses', $this->getExpensesData($request));
    }

    public function expensesPdf(Request $request)
    {
        $data = $this->getExpensesData($request);
        $pdf = PDF::loadView('reports.pdf.expense', $data);

        return $pdf->download('expense-report.pdf');
    }

    public function expensesCsv(Request $request)
    {
        $data = $this->getExpensesData($request);
        $expenses = $data['expenses'];
        $dailyTotals = $data['dailyTotals'];
        $filename = 'expense-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Type', 'Amount (PKR)', 'Date', 'Notes'];

        $callback = function () use ($expenses, $dailyTotals, $data, $columns) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Expense Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Filter', ucfirst(str_replace('-', ' ', $data['filter']))]);
            fputcsv($f, ['Records', $expenses->count()]);
            fputcsv($f, ['Total Amount', money($data['totalAmount'])]);
            fputcsv($f, []);
            fputcsv($f, $columns);

            foreach ($expenses as $e) {
                fputcsv($f, [
                    $e->expense_type,
                    money($e->amount),
                    \Carbon\Carbon::parse($e->expense_date)->format('d-m-Y'),
                    $e->notes ?? '',
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Daily Totals']);
            fputcsv($f, ['Date', 'Amount (PKR)', 'Records']);

            foreach ($dailyTotals as $day) {
                fputcsv($f, [
                    $day['label'],
                    money($day['total_amount']),
                    $day['record_count'],
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Grand Total', money($data['totalAmount']), $expenses->count()]);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANCE REPORT
    |--------------------------------------------------------------------------
    */
    private function getVarianceData(): array
    {
        $tanks = Tank::with('product')->orderBy('tank_number')->get();
        $variances = collect();

        foreach ($tanks as $tank) {
            $system = (float) $tank->current_stock_liters;
            $dip = TankDipReading::where('tank_id', $tank->id)->latest('reading_datetime')->first();
            $physical = $dip ? (float) $dip->measured_liters : $system;
            $difference = $physical - $system;

            if (abs($difference) < 0.01) {
                $status = 'match';
                $statusLabel = 'Matched';
            } elseif ($difference > 0) {
                $status = 'over';
                $statusLabel = 'Overage';
            } else {
                $status = 'under';
                $statusLabel = 'Shortage';
            }

            $variances->push([
                'tank_number' => $tank->tank_number,
                'product' => $tank->product->name ?? 'N/A',
                'system' => $system,
                'physical' => $physical,
                'difference' => $difference,
                'status' => $status,
                'status_label' => $statusLabel,
                'dip_date' => $dip ? Carbon::parse($dip->reading_datetime)->format('d M Y, h:i A') : null,
                'has_dip' => (bool) $dip,
            ]);
        }

        return [
            'variances' => $variances,
            'totalVariance' => $variances->sum('difference'),
            'tanksWithVariance' => $variances->where('status', '!=', 'match')->count(),
            'matchedCount' => $variances->where('status', 'match')->count(),
            'tankCount' => $variances->count(),
            'generatedAt' => now()->format('d M Y, h:i A'),
        ];
    }

    public function variance()
    {
        return view('reports.variance', $this->getVarianceData());
    }

    public function variancePdf()
    {
        $data = $this->getVarianceData();
        $pdf = PDF::loadView('reports.pdf.variance', $data);

        return $pdf->download('variance-report.pdf');
    }

    public function varianceCsv()
    {
        $data = $this->getVarianceData();
        $filename = 'variance-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Tank', 'Product', 'System (L)', 'Physical (L)', 'Difference (L)', 'Status', 'Last Dip Reading'];

        $callback = function () use ($data, $columns) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Tank Variance Report']);
            fputcsv($f, ['Generated', $data['generatedAt']]);
            fputcsv($f, ['Tanks', $data['tankCount']]);
            fputcsv($f, ['Matched', $data['matchedCount']]);
            fputcsv($f, ['With Variance', $data['tanksWithVariance']]);
            fputcsv($f, ['Total Variance (L)', number_format($data['totalVariance'], 2)]);
            fputcsv($f, []);
            fputcsv($f, $columns);

            foreach ($data['variances'] as $v) {
                fputcsv($f, [
                    $v['tank_number'],
                    $v['product'],
                    number_format($v['system'], 2),
                    number_format($v['physical'], 2),
                    number_format($v['difference'], 2),
                    $v['status_label'],
                    $v['dip_date'] ?? 'No dip reading',
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | MOBIL OIL SALES REPORT
    |--------------------------------------------------------------------------
    */
    private function getMobilOilSalesData(Request $request): array
    {
        $range = $this->getReportRange($request);
        $fromAt = $range['fromAt'];
        $toAt = $range['toAt'];

        $sales = MobilOilSale::with(['product', 'employee'])
            ->whereBetween('sold_datetime', [$fromAt, $toAt])
            ->latest('sold_datetime')
            ->get();

        $totalAmount = (float) $sales->sum('total_amount');
        $totalQty = (float) $sales->sum('quantity');

        $dailyTotals = $sales
            ->groupBy(fn ($sale) => Carbon::parse($sale->sold_datetime)->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d M Y'),
                    'total_amount' => $group->sum('total_amount'),
                    'total_qty' => $group->sum('quantity'),
                    'record_count' => $group->count(),
                ];
            })
            ->values();

        $byProduct = $sales
            ->groupBy('mobil_oil_product_id')
            ->map(function ($group) {
                $product = $group->first()->product;

                return [
                    'name' => $product->name ?? 'Unknown',
                    'unit' => $product->unit ?? '',
                    'total_amount' => $group->sum('total_amount'),
                    'total_qty' => $group->sum('quantity'),
                    'record_count' => $group->count(),
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        return array_merge($range, compact('sales', 'totalAmount', 'totalQty', 'dailyTotals', 'byProduct'));
    }

    public function mobilOilSales(Request $request)
    {
        return view('reports.mobil_oil_sales', $this->getMobilOilSalesData($request));
    }

    public function mobilOilSalesPdf(Request $request)
    {
        $data = $this->getMobilOilSalesData($request);
        $pdf = PDF::loadView('reports.pdf.mobil_oil_sales', $data);

        return $pdf->download('mobil-oil-sales-report.pdf');
    }

    public function mobilOilSalesCsv(Request $request)
    {
        $data = $this->getMobilOilSalesData($request);
        $filename = 'mobil-oil-sales-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Mobil Oil Sales Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Total Sales (PKR)', money($data['totalAmount'])]);
            fputcsv($f, ['Total Quantity', number_format($data['totalQty'], 2)]);
            fputcsv($f, []);
            fputcsv($f, ['Product', 'Quantity', 'Amount (PKR)', 'Payment', 'Employee', 'Sold At']);

            foreach ($data['sales'] as $s) {
                fputcsv($f, [
                    $s->product->name ?? '',
                    number_format($s->quantity, 2) . ' ' . ($s->product->unit ?? ''),
                    money($s->total_amount),
                    ucfirst($s->payment_method),
                    $s->employee->name ?? '',
                    $s->sold_datetime?->format('d-m-Y H:i') ?? '',
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE REPORT
    |--------------------------------------------------------------------------
    */
    private function getAttendanceData(Request $request): array
    {
        $range = $this->getReportRange($request);

        $attendances = EmployeeAttendance::with('employee')
            ->whereBetween('attendance_date', [$range['from'], $range['to']])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        $statusCounts = collect(EmployeeAttendance::STATUSES)
            ->mapWithKeys(fn ($status) => [
                $status => $attendances->where('status', $status)->count(),
            ]);

        $employeeSummaries = $attendances
            ->groupBy('employee_id')
            ->map(function ($group) {
                $employee = $group->first()->employee;

                return [
                    'employee_code' => $employee->employee_code ?? '—',
                    'name' => $employee->name ?? 'Unknown',
                    'present' => $group->where('status', 'present')->count(),
                    'absent' => $group->where('status', 'absent')->count(),
                    'late' => $group->where('status', 'late')->count(),
                    'half_day' => $group->where('status', 'half_day')->count(),
                    'on_leave' => $group->where('status', 'on_leave')->count(),
                    'total' => $group->count(),
                ];
            })
            ->sortBy('name')
            ->values();

        $dailyTotals = $attendances
            ->groupBy(fn ($row) => Carbon::parse($row->attendance_date)->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d M Y'),
                    'present' => $group->where('status', 'present')->count(),
                    'absent' => $group->where('status', 'absent')->count(),
                    'late' => $group->where('status', 'late')->count(),
                    'half_day' => $group->where('status', 'half_day')->count(),
                    'on_leave' => $group->where('status', 'on_leave')->count(),
                    'record_count' => $group->count(),
                ];
            })
            ->values();

        return array_merge($range, [
            'attendances' => $attendances,
            'totalRecords' => $attendances->count(),
            'statusCounts' => $statusCounts,
            'employeeSummaries' => $employeeSummaries,
            'dailyTotals' => $dailyTotals,
        ]);
    }

    public function attendance(Request $request)
    {
        return view('reports.attendance', $this->getAttendanceData($request));
    }

    public function attendancePdf(Request $request)
    {
        $data = $this->getAttendanceData($request);
        $pdf = PDF::loadView('reports.pdf.attendance', $data);

        return $pdf->download('attendance-report.pdf');
    }

    public function attendanceCsv(Request $request)
    {
        $data = $this->getAttendanceData($request);
        $attendances = $data['attendances'];
        $filename = 'attendance-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($attendances, $data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Employee Attendance Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Filter', ucfirst(str_replace('-', ' ', $data['filter']))]);
            fputcsv($f, ['Records', $data['totalRecords']]);
            fputcsv($f, []);
            fputcsv($f, ['Date', 'Employee', 'Code', 'Status', 'Check In', 'Check Out', 'Hours', 'Notes']);

            foreach ($attendances as $a) {
                fputcsv($f, [
                    $a->attendance_date->format('d-m-Y'),
                    $a->employee->name ?? '',
                    $a->employee->employee_code ?? '',
                    $a->status_label,
                    $a->check_in ? Carbon::parse($a->check_in)->format('H:i') : '',
                    $a->check_out ? Carbon::parse($a->check_out)->format('H:i') : '',
                    $a->worked_hours !== null ? number_format($a->worked_hours, 2) : '',
                    $a->notes ?? '',
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Employee Summary']);
            fputcsv($f, ['Name', 'Code', 'Present', 'Absent', 'Late', 'Half Day', 'On Leave', 'Total']);

            foreach ($data['employeeSummaries'] as $row) {
                fputcsv($f, [
                    $row['name'],
                    $row['employee_code'],
                    $row['present'],
                    $row['absent'],
                    $row['late'],
                    $row['half_day'],
                    $row['on_leave'],
                    $row['total'],
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | CASH REPORT
    |--------------------------------------------------------------------------
    */
    private function getCashReportData(Request $request): array
    {
        $range = $this->getReportRange($request);
        $ledger = DailyCashLedger::forRange($range['from'], $range['to']);

        return array_merge($range, $ledger);
    }

    public function cash(Request $request)
    {
        return view('reports.cash', $this->getCashReportData($request));
    }

    public function cashPdf(Request $request)
    {
        $data = $this->getCashReportData($request);
        $pdf = PDF::loadView('reports.pdf.cash', $data);

        return $pdf->download('cash-report.pdf');
    }

    public function cashCsv(Request $request)
    {
        $data = $this->getCashReportData($request);
        $filename = 'cash-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Cash Report']);
            fputcsv($f, ['Range', $data['from'].' to '.$data['to']]);
            fputcsv($f, []);
            fputcsv($f, [
                'Date',
                'Sales Cash',
                'Sales Bank',
                'Cash In',
                'Cash Out',
                'Expenses',
                'Closing',
            ]);

            foreach ($data['days'] as $day) {
                fputcsv($f, [
                    $day['label'],
                    money($day['sales_cash']),
                    money($day['sales_bank']),
                    money($day['cash_in']),
                    money($day['cash_out']),
                    money($day['expenses']),
                    money($day['closing']),
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, [
                'Period Total',
                money($data['total_sales_cash']),
                money($data['total_sales_bank']),
                money($data['total_cash_in']),
                money($data['total_cash_out']),
                money($data['total_expenses']),
                money($data['closing_balance']),
            ]);

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | PURCHASE REPORT
    |--------------------------------------------------------------------------
    */
    private function getPurchaseReportData(Request $request): array
    {
        $range = $this->getReportRange($request);
        $fromAt = $range['fromAt'];
        $toAt = $range['toAt'];

        $fuelPurchases = TankRefill::query()
            ->with(['product', 'tank'])
            ->whereBetween('received_datetime', [$fromAt, $toAt])
            ->latest('received_datetime')
            ->get();

        $mobilOilPurchases = MobilOilPurchase::query()
            ->with('product')
            ->whereBetween('received_datetime', [$fromAt, $toAt])
            ->latest('received_datetime')
            ->get();

        $fuelPurchaseAmount = (float) $fuelPurchases->sum('total_amount');
        $fuelPurchaseLiters = (float) $fuelPurchases->sum('quantity_liters');
        $mobilOilPurchaseAmount = (float) $mobilOilPurchases->sum('total_amount');
        $mobilOilPurchaseQty = (float) $mobilOilPurchases->sum('quantity');
        $totalPurchaseAmount = $fuelPurchaseAmount + $mobilOilPurchaseAmount;

        $fuelByProduct = FuelProducts::all()->map(function ($product) use ($fuelPurchases) {
            $rows = $fuelPurchases->where('product_id', $product->id);
            $qty = (float) $rows->sum('quantity_liters');
            $amount = (float) $rows->sum('total_amount');

            return [
                'product' => $product->name,
                'quantity' => round($qty, 2),
                'amount' => round($amount, 2),
                'avg_rate' => $qty > 0 ? round($amount / $qty, 2) : null,
                'count' => $rows->count(),
            ];
        })->values();

        $mobilOilByProduct = $mobilOilPurchases
            ->groupBy('mobil_oil_product_id')
            ->map(function ($group) {
                $product = $group->first()->product;
                $qty = (float) $group->sum('quantity');
                $amount = (float) $group->sum('total_amount');

                return [
                    'product' => $product->name ?? 'Unknown',
                    'unit' => $product->unit ?? '',
                    'quantity' => round($qty, 2),
                    'amount' => round($amount, 2),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return array_merge($range, compact(
            'fuelPurchases',
            'mobilOilPurchases',
            'fuelPurchaseAmount',
            'fuelPurchaseLiters',
            'mobilOilPurchaseAmount',
            'mobilOilPurchaseQty',
            'totalPurchaseAmount',
            'fuelByProduct',
            'mobilOilByProduct'
        ));
    }

    public function purchases(Request $request)
    {
        return view('reports.purchases', $this->getPurchaseReportData($request));
    }

    public function purchasesPdf(Request $request)
    {
        $data = $this->getPurchaseReportData($request);
        $pdf = PDF::loadView('reports.pdf.purchases', $data);

        return $pdf->download('purchase-report.pdf');
    }

    public function purchasesCsv(Request $request)
    {
        $data = $this->getPurchaseReportData($request);
        $filename = 'purchase-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Purchase Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Petroleum Purchases', money($data['fuelPurchaseAmount'])]);
            fputcsv($f, ['Mobil Oil Purchases', money($data['mobilOilPurchaseAmount'])]);
            fputcsv($f, ['Total Purchases', money($data['totalPurchaseAmount'])]);
            fputcsv($f, []);

            fputcsv($f, ['Petroleum Purchases']);
            fputcsv($f, ['Date', 'Product', 'Tank', 'Invoice', 'Qty (L)', 'Rate', 'Amount (PKR)', 'Notes']);
            foreach ($data['fuelPurchases'] as $p) {
                fputcsv($f, [
                    optional($p->received_datetime)->format('d-m-Y H:i'),
                    $p->product->name ?? '',
                    $p->tank->tank_number ?? '',
                    $p->invoice_no ?? '',
                    number_format((float) $p->quantity_liters, 2),
                    rate($p->purchase_rate),
                    money($p->total_amount),
                    $p->notes ?? '',
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Petroleum by Product']);
            fputcsv($f, ['Product', 'Qty (L)', 'Avg Rate', 'Amount (PKR)', 'Records']);
            foreach ($data['fuelByProduct'] as $row) {
                fputcsv($f, [
                    $row['product'],
                    number_format($row['quantity'], 2),
                    $row['avg_rate'] !== null ? rate($row['avg_rate']) : '',
                    money($row['amount']),
                    $row['count'],
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Mobil Oil Purchases']);
            fputcsv($f, ['Date', 'Product', 'Invoice', 'Qty', 'Rate', 'Amount (PKR)', 'Notes']);
            foreach ($data['mobilOilPurchases'] as $p) {
                fputcsv($f, [
                    optional($p->received_datetime)->format('d-m-Y H:i'),
                    $p->product->name ?? '',
                    $p->invoice_no ?? '',
                    number_format((float) $p->quantity, 2),
                    rate($p->purchase_rate),
                    money($p->total_amount),
                    $p->notes ?? '',
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Mobil Oil by Product']);
            fputcsv($f, ['Product', 'Qty', 'Amount (PKR)', 'Records']);
            foreach ($data['mobilOilByProduct'] as $row) {
                fputcsv($f, [
                    $row['product'].(! empty($row['unit']) ? ' ('.$row['unit'].')' : ''),
                    number_format($row['quantity'], 2),
                    money($row['amount']),
                    $row['count'],
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | SHIFT REPORT
    |--------------------------------------------------------------------------
    */
    private function getShiftReportData(Request $request): array
    {
        $range = $this->getReportRange($request);

        $shifts = EmployeeShift::query()
            ->with(['employee', 'nozzle.product'])
            ->when($range['from'] && $range['to'], fn ($q) => $q->closedBetween($range['from'], $range['to']))
            ->orderByDesc('closed_date')
            ->orderByDesc('id')
            ->get();

        $closed = $shifts->whereIn('status', ['submitted', 'verified']);

        $totalShifts = $shifts->count();
        $totalLiters = (float) $closed->sum('total_liters');
        $totalAmount = (float) $closed->sum('total_amount');
        $totalCash = (float) $closed->sum('cash_received');
        $totalOnline = (float) $closed->sum('online_received');
        $totalShortage = (float) $closed->sum('shortage_amount');
        $totalExtra = (float) $closed->sum('extra_amount');

        $closingByDay = DailyFuelMetrics::dailyClosingStock($range['from'], $range['to']);

        return array_merge($range, compact(
            'shifts',
            'closingByDay',
            'totalShifts',
            'totalLiters',
            'totalAmount',
            'totalCash',
            'totalOnline',
            'totalShortage',
            'totalExtra'
        ));
    }

    public function shifts(Request $request)
    {
        return view('reports.shifts', $this->getShiftReportData($request));
    }

    public function shiftsPdf(Request $request)
    {
        $data = $this->getShiftReportData($request);
        $pdf = PDF::loadView('reports.pdf.shifts', $data)->setPaper('a4', 'landscape');

        return $pdf->download('shift-report.pdf');
    }

    public function shiftsCsv(Request $request)
    {
        $data = $this->getShiftReportData($request);
        $filename = 'shift-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Shift Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Shifts', $data['totalShifts']]);
            fputcsv($f, ['Total Liters', number_format($data['totalLiters'], 2)]);
            fputcsv($f, ['Total Amount', money($data['totalAmount'])]);
            fputcsv($f, ['Cash', money($data['totalCash'])]);
            fputcsv($f, ['Bank', money($data['totalOnline'])]);
            fputcsv($f, []);
            fputcsv($f, [
                'Date',
                'Employee',
                'Nozzle',
                'Fuel',
                'Opening Meter',
                'Closing Meter',
                'Closing Petrol Stock (L)',
                'Closing Diesel Stock (L)',
                'Testing (L)',
                'Liters',
                'Rate',
                'Amount (PKR)',
                'Cash',
                'Bank',
                'Shortage',
                'Extra',
                'Status',
            ]);

            foreach ($data['shifts'] as $s) {
                $isOpen = $s->status === 'active';
                $dateKey = Carbon::parse($s->closed_date ?? $s->assigned_date)->format('Y-m-d');
                $closing = $data['closingByDay']->get($dateKey, [
                    'petrol' => ['stock_closing' => 0.0],
                    'diesel' => ['stock_closing' => 0.0],
                ]);
                fputcsv($f, [
                    report_date($s->closed_date ?? $s->assigned_date),
                    $s->employee->name ?? '',
                    $s->nozzle->nozzle_number ?? '',
                    $s->nozzle->product->name ?? '',
                    $s->opening_reading !== null ? number_format((float) $s->opening_reading, 2) : '',
                    $s->closing_reading !== null ? number_format((float) $s->closing_reading, 2) : '',
                    number_format($closing['petrol']['stock_closing'] ?? 0, 2),
                    number_format($closing['diesel']['stock_closing'] ?? 0, 2),
                    $isOpen ? '' : number_format((float) ($s->testing_liters ?? 0), 2),
                    $isOpen ? '' : number_format((float) ($s->total_liters ?? 0), 2),
                    $isOpen || $s->price_per_liter === null ? '' : rate($s->price_per_liter),
                    $isOpen ? '' : money($s->total_amount),
                    $isOpen ? '' : money($s->cash_received),
                    $isOpen ? '' : money($s->online_received),
                    $isOpen ? '' : money($s->shortage_amount),
                    $isOpen ? '' : money($s->extra_amount),
                    ucfirst((string) $s->status),
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, [
                'Grand Total',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($data['totalLiters'], 2),
                '',
                money($data['totalAmount']),
                money($data['totalCash']),
                money($data['totalOnline']),
                money($data['totalShortage']),
                money($data['totalExtra']),
                '',
            ]);

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * @param  resource  $f
     * @param  \Illuminate\Support\Collection|array  $productBreakdown
     */
    private function writeFuelProductColumnsCsv($f, $productBreakdown, bool $simple = false): void
    {
        $rows = collect([
            $productBreakdown['petrol'] ?? null,
            $productBreakdown['diesel'] ?? null,
        ])->filter();

        if ($rows->isEmpty()) {
            return;
        }

        fputcsv($f, []);
        fputcsv($f, ['Petroleum Sales & Profit']);

        if ($simple) {
            fputcsv($f, ['Fuel', 'Liters', 'Sales Amount', 'Cash', 'Online', 'Total Profit']);

            foreach ($rows as $row) {
                fputcsv($f, [
                    $row['product'],
                    number_format($row['liters'], 2),
                    money($row['sales_amount']),
                    money($row['cash']),
                    money($row['online']),
                    money($row['total_profit']),
                ]);
            }

            fputcsv($f, [
                'Total',
                number_format($rows->sum('liters'), 2),
                money($rows->sum('sales_amount')),
                money($rows->sum('cash')),
                money($rows->sum('online')),
                money($rows->sum('total_profit')),
            ]);

            return;
        }

        fputcsv($f, [
            'Fuel',
            'Liters',
            'Sales Amount',
            'Cash',
            'Online',
            'Purchase Rate',
            'Sale Rate',
            'Profit / L',
            'Total Profit',
            'Closing Stock (L)',
            'Closing Balance (PKR)',
        ]);

        foreach ($rows as $row) {
            fputcsv($f, [
                $row['product'],
                number_format($row['liters'], 2),
                money($row['sales_amount']),
                money($row['cash']),
                money($row['online']),
                $row['purchase_rate'] !== null ? rate($row['purchase_rate']) : '',
                $row['sale_rate'] !== null ? rate($row['sale_rate']) : '',
                $row['profit_per_liter'] !== null ? number_format($row['profit_per_liter'], 2) : '',
                money($row['total_profit']),
                number_format($row['closing_stock_liters'], 2),
                $row['closing_balance'] !== null ? money($row['closing_balance']) : '',
            ]);
        }

        fputcsv($f, [
            'Total',
            number_format($rows->sum('liters'), 2),
            money($rows->sum('sales_amount')),
            money($rows->sum('cash')),
            money($rows->sum('online')),
            '',
            '',
            '',
            money($rows->sum('total_profit')),
            number_format($rows->sum('closing_stock_liters'), 2),
            money($rows->sum(fn ($r) => $r['closing_balance'] ?? 0)),
        ]);
    }

    /**
     * @param  resource  $f
     * @param  \Illuminate\Support\Collection|array  $mobilOilBreakdown
     */
    private function writeMobilOilBreakdownCsv($f, $mobilOilBreakdown): void
    {
        $rows = collect($mobilOilBreakdown);

        if ($rows->isEmpty()) {
            return;
        }

        fputcsv($f, []);
        fputcsv($f, ['Mobil Oil Sales & Profit']);
        fputcsv($f, ['Product', 'Qty', 'Sales Amount', 'Cash', 'Online', 'Total Profit']);

        foreach ($rows as $row) {
            fputcsv($f, [
                $row['product'].(! empty($row['unit']) ? ' ('.$row['unit'].')' : ''),
                number_format($row['quantity'], 2),
                money($row['sales_amount']),
                money($row['cash']),
                money($row['online']),
                money($row['total_profit']),
            ]);
        }

        fputcsv($f, [
            'Total',
            number_format($rows->sum('quantity'), 2),
            money($rows->sum('sales_amount')),
            money($rows->sum('cash')),
            money($rows->sum('online')),
            money($rows->sum('total_profit')),
        ]);
    }
}