<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);

        $expenses = Expense::whereBetween('expense_date', [$range['from'], $range['to']])
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        return view('expenses.index', array_merge($range, compact('expenses')));
    }

    public function create()
    {
        return view('expenses.create', [
            'expenseTypes' => $this->expenseTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());

        $amount = $this->resolveAmount($request);

        Expense::create([
            'expense_type' => $request->expense_type,
            'amount' => $amount,
            'expense_date' => $request->expense_date,
            'notes' => $request->notes,
            'created_by' => Auth::id() ?? 1,
        ]);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', [
            'expense' => $expense,
            'expenseTypes' => $this->expenseTypes(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate($this->rules());

        $amount = $this->resolveAmount($request);

        $expense->update([
            'expense_type' => $request->expense_type,
            'amount' => $amount,
            'expense_date' => $request->expense_date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated (PKR ' . number_format($amount, 2) . ').');
    }

    /**
     * @return list<string>
     */
    private function expenseTypes(): array
    {
        return [
            'Salary',
            'Electricity Bill',
            'Maintenance',
            'Repair',
            'Miscellaneous',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'expense_type' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ];
    }

    private function salaryTotal(): float
    {
        return round((float) Employee::where('status', 1)->sum('salary'), 2);
    }

    private function resolveAmount(Request $request): float
    {
        if ($request->expense_type === 'Salary') {
            return $this->salaryTotal();
        }

        return round((float) $request->amount, 2);
    }
}
