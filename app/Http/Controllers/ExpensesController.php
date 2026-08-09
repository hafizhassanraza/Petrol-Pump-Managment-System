<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Services\BusinessDayService;
use App\Support\ReportRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);

        $expenses = Expense::whereDate('expense_date', '>=', $range['from'])
            ->whereDate('expense_date', '<=', $range['to'])
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        return view('expenses.index', array_merge($range, compact('expenses')));
    }

    public function create()
    {
        return view('expenses.create', [
            'expenseTypes' => $this->expenseTypes(),
            'defaultDate' => BusinessDayService::currentBusinessDate()->toDateString(),
            'salaryTotal' => $this->salaryTotal(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Expense::create($data + ['created_by' => Auth::id() ?? 1]);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully (PKR '.money($data['amount']).').');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', [
            'expense' => $expense,
            'expenseTypes' => $this->expenseTypes(),
            'salaryTotal' => $this->salaryTotal(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $this->validated($request);

        $expense->update($data);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated (PKR '.money($data['amount']).').');
    }

    /**
     * @return list<string>
     */
    private function expenseTypes(): array
    {
        return [
            'Electricity Bill',
            'Maintenance',
            'Repair',
            'Salary',
            'Miscellaneous',
        ];
    }

    /**
     * @return array{expense_type: string, amount: float, expense_date: string, notes: ?string}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'expense_type' => ['required', 'string', 'max:100', Rule::in($this->expenseTypes())],
            'amount' => [
                Rule::requiredIf(fn () => $request->input('expense_type') !== 'Salary'),
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $data['amount'] = $data['expense_type'] === 'Salary'
            ? $this->salaryTotal()
            : round((float) $data['amount'], 2);

        $data['expense_date'] = Carbon::parse($data['expense_date'])->toDateString();

        return $data;
    }

    private function salaryTotal(): float
    {
        return round((float) Employee::where('status', 1)->sum('salary'), 2);
    }
}
