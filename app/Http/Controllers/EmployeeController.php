<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PDF;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::latest()->paginate(15);

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|unique:employees,employee_code',
            'name' => 'required|string|max:255',
            'cnic' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'salary' => 'nullable|numeric|min:0',
            'joining_date' => 'nullable|date',
        ]);

        Employee::create([
            'employee_code' => $request->employee_code,
            'name' => $request->name,
            'cnic' => $request->cnic,
            'phone' => $request->phone,
            'salary' => $request->salary ?? 0,
            'joining_date' => $request->joining_date ?? now()->toDateString(),
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'employee_code' => 'required|unique:employees,employee_code,'.$employee->id,
            'name' => 'required|string|max:255',
            'cnic' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'salary' => 'nullable|numeric|min:0',
            'joining_date' => 'nullable|date',
        ]);

        $employee->update([
            'employee_code' => $request->employee_code,
            'name' => $request->name,
            'cnic' => $request->cnic,
            'phone' => $request->phone,
            'salary' => $request->salary ?? 0,
            'joining_date' => $request->joining_date,
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    public function ledger(Request $request, Employee $employee): View
    {
        return view('employees.ledger', $this->getLedgerData($request, $employee));
    }

    public function ledgerPdf(Request $request, Employee $employee): Response
    {
        $data = $this->getLedgerData($request, $employee);
        $pdf = PDF::loadView('reports.pdf.employee_ledger', $data);
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', $employee->employee_code) ?: 'employee';

        return $pdf->download('employee-ledger-'.$slug.'-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function getLedgerData(Request $request, Employee $employee): array
    {
        if (! $request->filled('filter') && ! $request->filled('from')) {
            $request->merge(['filter' => 'last-month']);
        }

        $range = ReportRange::fromRequest($request);

        $payments = EmployeeSalary::query()
            ->where('employee_id', $employee->id)
            ->whereDate('payment_date', '>=', $range['from'])
            ->whereDate('payment_date', '<=', $range['to'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $running = 0.0;
        $ledgerRows = $payments->map(function (EmployeeSalary $payment) use (&$running) {
            $running += (float) $payment->amount;

            return [
                'payment' => $payment,
                'balance' => $running,
            ];
        });

        $byType = collect(EmployeeSalary::TYPES)->mapWithKeys(function (string $type) use ($payments) {
            return [$type => (float) $payments->where('type', $type)->sum('amount')];
        });

        return array_merge($range, [
            'employee' => $employee,
            'payments' => $payments,
            'ledgerRows' => $ledgerRows,
            'byType' => $byType,
            'totalPaid' => (float) $payments->sum('amount'),
            'typeLabels' => EmployeeSalary::typeLabels(),
        ]);
    }
}
