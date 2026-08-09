<?php

namespace App\Http\Controllers;

use App\Models\AgencyCustomer;
use App\Models\AgencyFuelCredit;
use App\Models\AgencyFuelPayment;
use App\Models\CashTransaction;
use App\Services\BusinessDayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgencyCustomerController extends Controller
{
    public function index()
    {
        $customers = AgencyCustomer::query()
            ->withSum('credits as credit_total', 'total_amount')
            ->withSum('credits as paid_total', 'paid_amount')
            ->orderBy('name')
            ->paginate(20);

        return view('agency_customers.index', compact('customers'));
    }

    public function create()
    {
        return view('agency_customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'cnic' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        AgencyCustomer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'cnic' => $request->cnic,
            'address' => $request->address,
            'notes' => $request->notes,
            'status' => $request->boolean('status', true),
        ]);

        return redirect()->route('agency-customers.index')
            ->with('success', 'Agency customer added.');
    }

    public function show(AgencyCustomer $agency_customer)
    {
        $agency_customer->load([
            'credits' => fn ($q) => $q->with(['product', 'nozzle', 'payments'])
                ->latest('credit_datetime'),
        ]);

        return view('agency_customers.show', [
            'customer' => $agency_customer,
            'defaultPaymentDate' => BusinessDayService::currentBusinessDate()->toDateString(),
        ]);
    }

    public function edit(AgencyCustomer $agency_customer)
    {
        return view('agency_customers.edit', ['customer' => $agency_customer]);
    }

    public function update(Request $request, AgencyCustomer $agency_customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'cnic' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $agency_customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'cnic' => $request->cnic,
            'address' => $request->address,
            'notes' => $request->notes,
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('agency-customers.index')
            ->with('success', 'Agency customer updated.');
    }

    public function storePayment(Request $request, AgencyFuelCredit $credit)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,online',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $balance = $credit->balance();
        $amount = round((float) $request->amount, 2);

        if ($amount > $balance + 0.009) {
            return back()->withInput()->with(
                'error',
                'Payment cannot exceed remaining balance (PKR '.money($balance).').'
            );
        }

        try {
            DB::transaction(function () use ($request, $credit, $amount) {
                AgencyFuelPayment::create([
                    'agency_fuel_credit_id' => $credit->id,
                    'amount' => $amount,
                    'payment_method' => $request->payment_method,
                    'payment_date' => $request->payment_date,
                    'notes' => $request->notes,
                    'created_by' => Auth::id() ?? 1,
                ]);

                $credit->refreshPaymentStatus();

                if ($request->payment_method === 'cash') {
                    CashTransaction::create([
                        'type' => CashTransaction::TYPE_IN,
                        'amount' => $amount,
                        'payment_method' => 'cash',
                        'transaction_date' => $request->payment_date,
                        'category' => 'Agency Payment',
                        'notes' => 'Agency payment: '.($credit->customer->name ?? 'Customer').
                            ' (credit #'.$credit->id.')',
                        'created_by' => Auth::id() ?? 1,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment of PKR '.money($amount).' recorded.');
    }
}
