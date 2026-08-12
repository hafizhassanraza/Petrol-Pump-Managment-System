<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Services\BusinessDayService;
use App\Support\ReportRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PDF;
use Symfony\Component\HttpFoundation\Response;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request): View
    {
        $range = ReportRange::fromRequest($request);
        $typeFilter = $request->string('type')->toString();
        $employeeFilter = $request->integer('employee_id') ?: null;

        $query = EmployeeSalary::query()
            ->with('employee')
            ->whereDate('payment_date', '>=', $range['from'])
            ->whereDate('payment_date', '<=', $range['to'])
            ->latest('payment_date')
            ->latest('id');

        if (in_array($typeFilter, EmployeeSalary::TYPES, true)) {
            $query->where('type', $typeFilter);
        }

        if ($employeeFilter) {
            $query->where('employee_id', $employeeFilter);
        }

        $salaries = $query->paginate(20)->withQueryString();

        $base = EmployeeSalary::query()
            ->whereDate('payment_date', '>=', $range['from'])
            ->whereDate('payment_date', '<=', $range['to']);

        if ($employeeFilter) {
            $base->where('employee_id', $employeeFilter);
        }

        $totals = [
            'all' => (float) (clone $base)->sum('amount'),
            'full' => (float) (clone $base)->where('type', EmployeeSalary::TYPE_FULL)->sum('amount'),
            'advance' => (float) (clone $base)->where('type', EmployeeSalary::TYPE_ADVANCE)->sum('amount'),
            'partial' => (float) (clone $base)->where('type', EmployeeSalary::TYPE_PARTIAL)->sum('amount'),
            'bonus' => (float) (clone $base)->where('type', EmployeeSalary::TYPE_BONUS)->sum('amount'),
        ];

        return view('employee_salaries.index', array_merge($range, [
            'salaries' => $salaries,
            'totals' => $totals,
            'typeFilter' => $typeFilter,
            'employeeFilter' => $employeeFilter,
            'employees' => Employee::orderBy('name')->get(['id', 'name', 'employee_code']),
            'typeLabels' => EmployeeSalary::typeLabels(),
        ]));
    }

    public function create(Request $request): View
    {
        $employees = Employee::where('status', 1)->orderBy('name')->get();

        return view('employee_salaries.create', [
            'employees' => $employees,
            'typeLabels' => EmployeeSalary::typeLabels(),
            'paymentMethods' => EmployeeSalary::PAYMENT_METHODS,
            'defaultDate' => BusinessDayService::currentBusinessDate()->toDateString(),
            'defaultMonth' => BusinessDayService::currentBusinessDate()->startOfMonth()->format('Y-m'),
            'defaultType' => in_array($request->get('type'), EmployeeSalary::TYPES, true)
                ? $request->get('type')
                : EmployeeSalary::TYPE_FULL,
            'selectedEmployeeId' => $request->integer('employee_id') ?: null,
            'employeeRates' => $employees->mapWithKeys(fn ($e) => [$e->id => (float) $e->salary]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        EmployeeSalary::create($data + ['created_by' => Auth::id() ?? 1]);

        return redirect()->route('employee-salaries.index')
            ->with('success', EmployeeSalary::typeLabels()[$data['type']].' recorded (PKR '.money($data['amount']).').');
    }

    public function edit(EmployeeSalary $employee_salary): View
    {
        $employees = Employee::orderBy('name')->get();

        return view('employee_salaries.edit', [
            'salary' => $employee_salary,
            'employees' => $employees,
            'typeLabels' => EmployeeSalary::typeLabels(),
            'paymentMethods' => EmployeeSalary::PAYMENT_METHODS,
            'employeeRates' => $employees->mapWithKeys(fn ($e) => [$e->id => (float) $e->salary]),
        ]);
    }

    public function update(Request $request, EmployeeSalary $employee_salary)
    {
        $data = $this->validated($request);
        $employee_salary->update($data);

        return redirect()->route('employee-salaries.index')
            ->with('success', 'Salary record updated (PKR '.money($data['amount']).').');
    }

    public function destroy(EmployeeSalary $employee_salary)
    {
        $employee_salary->delete();

        return redirect()->route('employee-salaries.index')
            ->with('success', 'Salary record deleted.');
    }

    public function report(Request $request): View
    {
        return view('reports.employee_salaries', $this->getReportData($request));
    }

    public function reportPdf(Request $request): Response
    {
        $data = $this->getReportData($request);
        $pdf = PDF::loadView('reports.pdf.employee_salaries', $data);

        return $pdf->download('employee-salaries-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function getReportData(Request $request): array
    {
        $range = ReportRange::fromRequest($request);

        $salaries = EmployeeSalary::query()
            ->with('employee')
            ->whereDate('payment_date', '>=', $range['from'])
            ->whereDate('payment_date', '<=', $range['to'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $byType = collect(EmployeeSalary::TYPES)->mapWithKeys(function (string $type) use ($salaries) {
            return [$type => (float) $salaries->where('type', $type)->sum('amount')];
        });

        $byEmployee = $salaries
            ->groupBy('employee_id')
            ->map(function ($rows) {
                $employee = $rows->first()->employee;

                return [
                    'name' => $employee->name ?? 'Unknown',
                    'code' => $employee->employee_code ?? '—',
                    'base_salary' => (float) ($employee->salary ?? 0),
                    'total' => (float) $rows->sum('amount'),
                    'full' => (float) $rows->where('type', EmployeeSalary::TYPE_FULL)->sum('amount'),
                    'advance' => (float) $rows->where('type', EmployeeSalary::TYPE_ADVANCE)->sum('amount'),
                    'partial' => (float) $rows->where('type', EmployeeSalary::TYPE_PARTIAL)->sum('amount'),
                    'bonus' => (float) $rows->where('type', EmployeeSalary::TYPE_BONUS)->sum('amount'),
                    'count' => $rows->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return array_merge($range, [
            'salaries' => $salaries,
            'byType' => $byType,
            'byEmployee' => $byEmployee,
            'totalAmount' => (float) $salaries->sum('amount'),
            'typeLabels' => EmployeeSalary::typeLabels(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', Rule::in(EmployeeSalary::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'salary_month' => ['required', 'date_format:Y-m'],
            'payment_method' => ['required', Rule::in(EmployeeSalary::PAYMENT_METHODS)],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['amount'] = round((float) $data['amount'], 2);
        $data['payment_date'] = Carbon::parse($data['payment_date'])->toDateString();
        $data['salary_month'] = Carbon::createFromFormat('Y-m', $data['salary_month'])->startOfMonth()->toDateString();

        return $data;
    }
}
