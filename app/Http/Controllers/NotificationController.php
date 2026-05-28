<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get notification history
     * GET /api/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $perPage = $request->integer('per_page', 15);
        $type = $request->input('type');
        $status = $request->input('status');

        $query = NotificationLog::query();

        // Non-admins can only see their own notifications
        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        } else {
            // Admins can filter by user_id
            if ($request->has('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }
        }

        if ($type) {
            $query->where('notification_type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Get a specific notification
     * GET /api/notifications/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $notification = NotificationLog::find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        // Non-admins can only view their own notifications
        if (!$user->hasRole('admin') && $notification->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $notification,
        ]);
    }

    /**
     * Get notification statistics
     * GET /api/notifications/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = NotificationLog::query();

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'delivered' => (clone $query)->where('status', 'delivered')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'by_type' => [],
        ];

        $byType = (clone $query)
            ->selectRaw('notification_type, count(*) as count')
            ->groupBy('notification_type')
            ->pluck('count', 'notification_type');

        $stats['by_type'] = $byType;

        return response()->json(['data' => $stats]);
    }
}
