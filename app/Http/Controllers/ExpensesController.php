<?php

namespace App\Http\Controllers;

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
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_type' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Expense::create([
            'expense_type' => $request->expense_type,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'notes' => $request->notes,
            'created_by' => Auth::id() ?? 1,
        ]);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }
}
