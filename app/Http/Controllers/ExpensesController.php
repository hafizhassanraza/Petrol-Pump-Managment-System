<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    public function index()
    {
        $expenses = Expense::latest()->get();

        return view('expenses.index', compact('expenses'));
    }


    public function create()
    {
        return view('expenses.create');
    }


    public function store(Request $request)
    {
        $request->validate([

            'expense_type' => 'required',

            'amount' => 'required|numeric|min:1',

            'expense_date' => 'required|date',

        ]);


        Expense::create([

            'expense_type' => $request->expense_type,

            'amount' => $request->amount,

            'expense_date' => $request->expense_date,

            'notes' => $request->notes,

            'created_by' => 1,
        ]);


        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense added successfully.');
    }
}