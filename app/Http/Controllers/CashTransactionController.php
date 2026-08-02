<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $typeFilter = $request->get('type');

        $query = CashTransaction::query()
            ->whereDate('transaction_date', '>=', $range['from'])
            ->whereDate('transaction_date', '<=', $range['to'])
            ->latest('transaction_date')
            ->latest('id');

        if (in_array($typeFilter, CashTransaction::TYPES, true)) {
            $query->where('type', $typeFilter);
        }

        $transactions = $query->paginate(15)->withQueryString();

        $totalIn = (float) CashTransaction::query()
            ->whereDate('transaction_date', '>=', $range['from'])
            ->whereDate('transaction_date', '<=', $range['to'])
            ->where('type', CashTransaction::TYPE_IN)
            ->sum('amount');
        $totalOut = (float) CashTransaction::query()
            ->whereDate('transaction_date', '>=', $range['from'])
            ->whereDate('transaction_date', '<=', $range['to'])
            ->where('type', CashTransaction::TYPE_OUT)
            ->sum('amount');
        $netCash = $totalIn - $totalOut;

        return view('cash_transactions.index', array_merge($range, compact(
            'transactions',
            'typeFilter',
            'totalIn',
            'totalOut',
            'netCash'
        )));
    }

    public function create(Request $request)
    {
        return view('cash_transactions.create', [
            'types' => $this->typeOptions(),
            'categoriesIn' => $this->cashInCategories(),
            'categoriesOut' => $this->cashOutCategories(),
            'paymentMethods' => CashTransaction::PAYMENT_METHODS,
            'defaultType' => in_array($request->get('type'), CashTransaction::TYPES, true)
                ? $request->get('type')
                : CashTransaction::TYPE_IN,
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        CashTransaction::create($data + ['created_by' => Auth::id() ?? 1]);

        return redirect()->route('cash-transactions.index')
            ->with('success', ($data['type'] === CashTransaction::TYPE_IN ? 'Cash In' : 'Cash Out')
                . ' recorded (PKR ' . money($data['amount']) . ').');
    }

    public function edit(CashTransaction $cash_transaction)
    {
        return view('cash_transactions.edit', [
            'transaction' => $cash_transaction,
            'types' => $this->typeOptions(),
            'categoriesIn' => $this->cashInCategories(),
            'categoriesOut' => $this->cashOutCategories(),
            'paymentMethods' => CashTransaction::PAYMENT_METHODS,
        ]);
    }

    public function update(Request $request, CashTransaction $cash_transaction)
    {
        $data = $this->validated($request);

        $cash_transaction->update($data);

        return redirect()->route('cash-transactions.index')
            ->with('success', 'Cash transaction updated (PKR ' . money($data['amount']) . ').');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(CashTransaction::TYPES)],
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'payment_method' => ['required', Rule::in(CashTransaction::PAYMENT_METHODS)],
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $data['amount'] = round((float) $data['amount'], 2);

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        return [
            CashTransaction::TYPE_IN => 'Cash In',
            CashTransaction::TYPE_OUT => 'Cash Out',
        ];
    }

    /**
     * @return list<string>
     */
    private function cashInCategories(): array
    {
        return [
            'Owner Investment',
            'Bank Withdrawal to Till',
            'Customer Advance',
            'Loan Received',
            'Other Income',
            'Miscellaneous',
        ];
    }

    /**
     * @return list<string>
     */
    private function cashOutCategories(): array
    {
        return [
            'Owner Draw',
            'Bank Deposit',
            'Supplier Payment',
            'Staff Advance',
            'Utility Payment',
            'Miscellaneous',
        ];
    }
}
