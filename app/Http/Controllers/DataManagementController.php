<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\DataManagement\DataBackupService;
use App\Services\DataManagement\DataRevertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataManagementController extends Controller
{
    public function index(): View
    {
        $tables = collect(DataBackupService::tables())->mapWithKeys(function (string $table) {
            try {
                return [$table => \Illuminate\Support\Facades\DB::table($table)->count()];
            } catch (\Throwable) {
                return [$table => 0];
            }
        });

        return view('data_management.index', [
            'tables' => $tables,
            'totalRows' => $tables->sum(),
        ]);
    }

    public function export(DataBackupService $backup): StreamedResponse
    {
        $payload = $backup->export();
        $filename = 'fuel-station-backup-'.now()->format('Y-m-d-His').'.json';

        $rowCount = array_sum($payload['meta']['row_counts'] ?? []);

        AuditLogger::log(
            action: 'exported',
            description: 'Exported full data backup ('.$rowCount.' rows)',
            module: 'data-management',
            properties: ['row_counts' => $payload['meta']['row_counts'] ?? []],
        );

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $request, DataBackupService $backup)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json,txt', 'max:51200'],
            'confirm_import' => ['accepted'],
        ]);

        $contents = file_get_contents($request->file('backup_file')->getRealPath());
        $payload = json_decode($contents ?: '', true);

        if (! is_array($payload)) {
            return back()->with('error', 'Invalid JSON backup file.');
        }

        try {
            $result = $backup->import($payload);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (Auth::id() && ! \App\Models\User::whereKey(Auth::id())->exists()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Data imported. Please sign in again with a restored user account.');
        }

        AuditLogger::log(
            action: 'updated',
            description: 'Imported full data backup ('.$result['tables'].' tables, '.$result['rows'].' rows)',
            module: 'data-management',
            properties: $result,
            userId: Auth::id(),
        );

        return redirect()->route('data-management.index')
            ->with('success', 'Data imported successfully: '.$result['tables'].' tables, '.$result['rows'].' rows.');
    }

    public function revert(Request $request, DataRevertService $reverter)
    {
        $data = $request->validate([
            'from_date' => ['required', 'date', 'before_or_equal:today'],
            'confirm_revert' => ['accepted'],
            'confirm_text' => ['required', 'in:REVERT'],
        ]);

        try {
            $counts = $reverter->revertFromDate($data['from_date']);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $deleted = array_sum($counts);

        AuditLogger::log(
            action: 'deleted',
            description: 'Reverted data from '.$data['from_date'].' to latest ('.$deleted.' records affected)',
            module: 'data-management',
            properties: ['from_date' => $data['from_date'], 'counts' => $counts],
        );

        return redirect()->route('data-management.index')
            ->with('success', 'Data reverted from '.$data['from_date'].' to latest. Records affected: '.$deleted.'.')
            ->with('revert_counts', $counts);
    }
}
