<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::latest()->get();
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
            'name' => 'required',
        ]);

        Employee::create([
            'employee_code' => $request->employee_code,
            'name' => $request->name,
            'cnic' => $request->cnic,
            'phone' => $request->phone,
            'salary' => $request->salary ?? 0,
            'joining_date' => $request->joining_date,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Employee created');
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($request->all());

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted');
    }
}