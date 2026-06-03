<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);

        $attendances = EmployeeAttendance::with('employee')
            ->whereBetween('attendance_date', [$range['from'], $range['to']])
            ->latest('attendance_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('employee_attendances.index', array_merge($range, compact('attendances')));
    }

    public function create()
    {
        return view('employee_attendances.create', [
            'employees' => Employee::where('status', 1)->orderBy('name')->get(),
            'statuses' => EmployeeAttendance::STATUSES,
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($error = $this->validateTimes($data)) {
            return back()->withInput()->with('error', $error);
        }

        if (EmployeeAttendance::where('employee_id', $data['employee_id'])
            ->where('attendance_date', $data['attendance_date'])
            ->exists()) {
            return back()->withInput()->with('error', 'Attendance already recorded for this employee on the selected date.');
        }

        EmployeeAttendance::create($data + ['recorded_by' => Auth::id()]);

        return redirect()->route('employee-attendances.index')
            ->with('success', 'Attendance recorded successfully.');
    }

    public function edit(EmployeeAttendance $employeeAttendance)
    {
        return view('employee_attendances.edit', [
            'attendance' => $employeeAttendance,
            'employees' => Employee::where('status', 1)->orderBy('name')->get(),
            'statuses' => EmployeeAttendance::STATUSES,
        ]);
    }

    public function update(Request $request, EmployeeAttendance $employeeAttendance)
    {
        $data = $this->validated($request);

        if ($error = $this->validateTimes($data)) {
            return back()->withInput()->with('error', $error);
        }

        $duplicate = EmployeeAttendance::where('employee_id', $data['employee_id'])
            ->where('attendance_date', $data['attendance_date'])
            ->where('id', '!=', $employeeAttendance->id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Another attendance record exists for this employee on the selected date.');
        }

        $employeeAttendance->update($data);

        return redirect()->route('employee-attendances.index')
            ->with('success', 'Attendance updated successfully.');
    }

    public function destroy(EmployeeAttendance $employeeAttendance)
    {
        $employeeAttendance->delete();

        return redirect()->route('employee-attendances.index')
            ->with('success', 'Attendance record deleted.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'status' => ['required', Rule::in(EmployeeAttendance::STATUSES)],
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (in_array($validated['status'], ['absent', 'on_leave'], true)) {
            $validated['check_in'] = null;
            $validated['check_out'] = null;
        }

        return $validated;
    }

    private function validateTimes(array $data): ?string
    {
        if (empty($data['check_in']) || empty($data['check_out'])) {
            return null;
        }

        if ($data['check_out'] <= $data['check_in']) {
            return 'Check-out time must be after check-in time.';
        }

        return null;
    }
}
