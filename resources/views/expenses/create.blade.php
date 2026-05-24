@extends('layouts.app')

@section('content')

<h2>Add Expense</h2>

<form method="POST"
      action="{{ route('expenses.store') }}">

    @csrf


    <div class="mb-3">

        <label>Expense Type</label>

        <select name="expense_type"
                class="form-control">

            <option>Salary</option>

            <option>Electricity Bill</option>

            <option>Maintenance</option>

            <option>Repair</option>

            <option>Miscellaneous</option>

        </select>

    </div>



    <div class="mb-3">

        <label>Amount</label>

        <input type="number"
               step="0.01"
               name="amount"
               class="form-control">

    </div>



    <div class="mb-3">

        <label>Date</label>

        <input type="date"
               name="expense_date"
               class="form-control">

    </div>



    <div class="mb-3">

        <label>Notes</label>

        <textarea name="notes"
                  class="form-control"></textarea>

    </div>



    <button class="btn btn-success">

        Save Expense

    </button>

</form>

@endsection