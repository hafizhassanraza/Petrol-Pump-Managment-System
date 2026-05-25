<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

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
            'employee_code' => 'required|unique:employees,employee_code,' . $employee->id,
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
}
