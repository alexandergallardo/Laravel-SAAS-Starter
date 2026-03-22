<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Get the full notification history page with optional unread filter.
     */
    public function page(Request $request): Response
    {
        $filter = $request->input('filter', 'all');

        $query = $filter === 'unread'
            ? $request->user()->unreadNotifications()
            : $request->user()->notifications();

        return Inertia::render('notifications/index', [
            'notifications' => $query->paginate(15)->withQueryString(),
            'filter' => $filter,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get the authenticated user's recent notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->take(15)
            ->get();

        $unreadCount = $request->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return back();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'error' => 'Notification not found.'], 404);
        }

        return back()->with('error', 'Notification not found.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $deleted = $request->user()
            ->notifications()
            ->where('id', $id)
            ->delete();

        if ($deleted) {
            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return back();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'error' => 'Notification not found.'], 404);
        }

        return back()->with('error', 'Notification not found.');
    }

    /**
     * Delete all read notifications for the authenticated user.
     */
    public function clearRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
