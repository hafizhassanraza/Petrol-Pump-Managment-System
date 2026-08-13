@extends('layouts.app')

@section('content')
@include('reports.partials.report-styles')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('revert_counts'))
    <div class="alert alert-info">
        <strong>Revert details:</strong>
        <ul class="mb-0 mt-2">
            @foreach(session('revert_counts') as $table => $count)
                @if($count > 0)
                    <li>{{ str_replace('_', ' ', $table) }}: {{ $count }}</li>
                @endif
            @endforeach
        </ul>
    </div>
@endif

<div class="page-card mb-3">
    <h3 class="page-title mb-1">Data Management</h3>
    <p class="page-subtitle mb-0">
        Export or import a full station backup, or revert operational data from a selected date through today.
        Master setup (tanks, employees, products) is kept on revert; stocks and meters are rolled back.
    </p>
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-2">
        <div class="info-card amount">
            <div class="info-card-label">Tracked Tables</div>
            <div class="info-card-value">{{ $tables->count() }}</div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="info-card stock">
            <div class="info-card-label">Total Rows</div>
            <div class="info-card-value">{{ number_format($totalRows) }}</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="page-card h-100">
            <h5 class="mb-2"><i class="bi bi-download"></i> Export Data</h5>
            <p class="text-muted small">Download a JSON backup of the whole database (masters + transactions + current stock/meters).</p>
            <a href="{{ route('data-management.export') }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-arrow-down"></i> Export Full Backup
            </a>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="page-card h-100">
            <h5 class="mb-2"><i class="bi bi-upload"></i> Import Data</h5>
            <p class="text-muted small text-danger">
                Replaces all existing station data with the backup file. This cannot be undone unless you keep another export.
            </p>
            <form method="POST" action="{{ route('data-management.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Backup JSON file</label>
                    <input type="file" name="backup_file" class="form-control" accept=".json,application/json" required>
                    @error('backup_file')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="confirm_import" value="1" class="form-check-input" id="confirmImport" required>
                    <label class="form-check-label" for="confirmImport">I understand this will replace current data</label>
                </div>
                <button class="btn btn-warning" onclick="return confirm('Import will REPLACE all current data. Continue?')">
                    <i class="bi bi-file-earmark-arrow-up"></i> Import Backup
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="page-card h-100 border border-danger">
            <h5 class="mb-2 text-danger"><i class="bi bi-arrow-counterclockwise"></i> Data Revert</h5>
            <p class="text-muted small">
                Select a date. Everything from that date <strong>to the latest</strong> is deleted and reversed
                (shifts, sales, refills, expenses, salaries, cash, Mobil Oil, agency payments, activity logs).
                Tank/Mobil stock and nozzle meters are adjusted.
            </p>
            <form method="POST" action="{{ route('data-management.revert') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">From date (inclusive)</label>
                    <input type="date" name="from_date" class="form-control"
                           value="{{ old('from_date') }}" max="{{ now()->toDateString() }}" required>
                    @error('from_date')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Type <code>REVERT</code> to confirm</label>
                    <input type="text" name="confirm_text" class="form-control" placeholder="REVERT" required autocomplete="off">
                    @error('confirm_text')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="confirm_revert" value="1" class="form-check-input" id="confirmRevert" required>
                    <label class="form-check-label" for="confirmRevert">Delete &amp; reverse selected period</label>
                </div>
                <button class="btn btn-danger" onclick="return confirm('This will permanently delete and reverse data from the selected date to today. Continue?')">
                    <i class="bi bi-trash"></i> Revert Period
                </button>
            </form>
        </div>
    </div>
</div>

<div class="page-card">
    <h5 class="mb-3">Included Tables</h5>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Table</th>
                    <th style="text-align:right;">Rows</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tables as $table => $count)
                    <tr>
                        <td>{{ $table }}</td>
                        <td style="text-align:right;">{{ number_format($count) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
