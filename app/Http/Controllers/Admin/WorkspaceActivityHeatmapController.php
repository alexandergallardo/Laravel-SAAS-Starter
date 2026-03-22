<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceActivityHeatmapController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $end = CarbonImmutable::today();
        $start = $end->subWeeks(52)->startOfWeek();

        // Daily API request counts
        $rows = ApiRequestLog::query()
            ->select(DB::raw('DATE(requested_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('requested_at', [$start, $end->endOfDay()])
            ->groupBy(DB::raw('DATE(requested_at)'))
            ->orderBy(DB::raw('DATE(requested_at)'))
            ->get()
            ->keyBy('date');

        // Build 52-week grid (Sunday-based weeks)
        $weeks = [];
        $current = $start->startOfWeek(0); // Sunday
        while ($current->lte($end)) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $day = $current->addDays($d);
                $dateStr = $day->toDateString();
                $week[] = [
                    'date' => $dateStr,
                    'count' => (int) ($rows[$dateStr]->count ?? 0),
                    'is_future' => $day->gt($end),
                ];
            }
            $weeks[] = $week;
            $current = $current->addWeek();
        }

        $totalRequests = $rows->sum('count');
        $maxCount = $rows->max('count') ?: 1;

        return Inertia::render('admin/workspace-activity-heatmap', [
            'weeks' => $weeks,
            'totalRequests' => (int) $totalRequests,
            'maxCount' => (int) $maxCount,
            'dateRange' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ]);
    }
}
