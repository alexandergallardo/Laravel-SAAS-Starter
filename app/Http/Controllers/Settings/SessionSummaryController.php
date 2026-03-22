<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SessionSummaryController extends Controller
{
    /**
     * Get a summary of the user's active sessions.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->get();

        $totalSessions = $sessions->count();
        $currentSession = $sessions->first(fn ($s) => $s->id === $currentSessionId);

        // Get other active sessions count (excluding current)
        $otherSessionsCount = $totalSessions - 1;

        // Get last activity from other sessions
        $lastOtherActivity = $sessions
            ->where('id', '!=', $currentSessionId)
            ->first();

        return response()->json([
            'total_sessions' => $totalSessions,
            'other_sessions_count' => max(0, $otherSessionsCount),
            'current_session' => $currentSession ? [
                'id' => $currentSession->id,
                'ip_address' => $currentSession->ip_address,
                'device' => $this->parseDevice($currentSession->user_agent ?? ''),
                'platform' => $this->parsePlatform($currentSession->user_agent ?? ''),
                'browser' => $this->parseBrowser($currentSession->user_agent ?? ''),
                'last_active' => Carbon::createFromTimestamp($currentSession->last_activity)->diffForHumans(),
            ] : null,
            'last_other_activity' => $lastOtherActivity ? [
                'ip_address' => $lastOtherActivity->ip_address,
                'device' => $this->parseDevice($lastOtherActivity->user_agent ?? ''),
                'platform' => $this->parsePlatform($lastOtherActivity->user_agent ?? ''),
                'last_active' => Carbon::createFromTimestamp($lastOtherActivity->last_activity)->diffForHumans(),
            ] : null,
        ]);
    }

    /**
     * Parse device type from user agent string.
     */
    private function parseDevice(string $ua): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPod/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/iPad|Tablet/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Parse platform name from user agent string.
     */
    private function parsePlatform(string $ua): string
    {
        if (str_contains($ua, 'Windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) {
            return 'macOS';
        }
        if (str_contains($ua, 'Linux')) {
            return 'Linux';
        }
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            return 'iOS';
        }
        if (str_contains($ua, 'Android')) {
            return 'Android';
        }

        return 'Unknown';
    }

    /**
     * Parse browser name from user agent string.
     */
    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg/')) {
            return 'Edge';
        }
        if (str_contains($ua, 'Chrome') && ! str_contains($ua, 'Edg/')) {
            return 'Chrome';
        }
        if (str_contains($ua, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')) {
            return 'Safari';
        }

        return 'Unknown';
    }
}
