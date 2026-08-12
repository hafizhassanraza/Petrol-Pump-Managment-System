<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PDF;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->buildIndexData($request);

        return view('audit_logs.index', $data);
    }

    public function pdf(Request $request): Response
    {
        $data = $this->buildIndexData($request, paginate: false);

        $pdf = PDF::loadView('reports.pdf.audit_logs', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('activity-logs-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIndexData(Request $request, bool $paginate = true): array
    {
        if (! $request->filled('filter') && ! $request->filled('from')) {
            $request->merge(['filter' => 'last-week']);
        }

        $range = ReportRange::fromRequest($request);
        $actionFilter = $request->string('action')->toString();
        $moduleFilter = $request->string('module')->toString();
        $userFilter = $request->integer('user_id') ?: null;
        $search = trim($request->string('q')->toString());

        $base = AuditLog::query()
            ->whereBetween('created_at', [$range['fromAt'], $range['toAt']]);

        $query = (clone $base)
            ->with('user')
            ->latest('created_at')
            ->latest('id');

        if ($actionFilter !== '' && array_key_exists($actionFilter, AuditLog::actionLabels())) {
            $query->where('action', $actionFilter);
        }

        if ($moduleFilter !== '') {
            $query->where('module', $moduleFilter);
        }

        if ($userFilter) {
            $query->where('user_id', $userFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $summary = [
            'total' => (clone $base)->count(),
            'created' => (clone $base)->where('action', 'created')->count(),
            'updated' => (clone $base)->where('action', 'updated')->count(),
            'deleted' => (clone $base)->where('action', 'deleted')->count(),
            'viewed' => (clone $base)->where('action', 'viewed')->count(),
            'exported' => (clone $base)->where('action', 'exported')->count(),
            'auth' => (clone $base)->whereIn('action', ['logged_in', 'logged_out'])->count(),
        ];

        $modules = AuditLog::query()
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        if ($paginate) {
            $logs = $query->paginate(30)->withQueryString();
        } else {
            $logs = $query->limit(2000)->get();
        }

        return array_merge($range, [
            'logs' => $logs,
            'summary' => $summary,
            'actionFilter' => $actionFilter,
            'moduleFilter' => $moduleFilter,
            'userFilter' => $userFilter,
            'search' => $search,
            'modules' => $modules,
            'users' => $users,
            'actionLabels' => AuditLog::actionLabels(),
        ]);
    }
}
