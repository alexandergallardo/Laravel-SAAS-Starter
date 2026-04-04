<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImpersonationLogController extends Controller
{
    /**
     * Display a paginated impersonation audit log.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $logs = ImpersonationLog::query()
            ->with(['impersonator', 'impersonated'])
            ->when($search, function ($query, $search) {
                $query->whereHas('impersonator', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('impersonated', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('started_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/impersonation-logs', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Export impersonation logs as a CSV download.
     */
    public function export(): StreamedResponse
    {
        $logs = ImpersonationLog::query()
            ->with(['impersonator:id,name,email', 'impersonated:id,name,email'])
            ->latest('started_at')
            ->get();

        $filename = 'impersonation-logs-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Impersonator', 'Impersonator Email', 'Target User', 'Target Email', 'IP Address', 'Duration', 'Started At', 'Ended At']);

            foreach ($logs as $log) {
                $duration = $log->ended_at
                    ? ceil($log->started_at->diffInSeconds($log->ended_at) / 60).' min'
                    : 'Active';

                fputcsv($handle, [
                    $log->started_at->toDateString(),
                    $log->impersonator?->name ?? 'Unknown',
                    $log->impersonator?->email ?? '',
                    $log->impersonated?->name ?? 'Unknown',
                    $log->impersonated?->email ?? '',
                    $log->ip_address ?? '',
                    $duration,
                    $log->started_at->toDateTimeString(),
                    $log->ended_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
