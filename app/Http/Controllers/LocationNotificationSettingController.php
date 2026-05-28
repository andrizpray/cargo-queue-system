<?php

namespace App\Http\Controllers;

use App\Models\LocationNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationNotificationSettingController extends Controller
{
    /**
     * Get notification settings for a location
     * GET /api/locations/{locationId}/notification-settings
     */
    public function show(int $locationId): JsonResponse
    {
        $settings = LocationNotificationSetting::getSettingForLocation($locationId);

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update notification settings for a location
     * PUT /api/locations/{locationId}/notification-settings
     */
    public function update(Request $request, int $locationId): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden - Admin access required'], 403);
        }

        $settings = LocationNotificationSetting::getSettingForLocation($locationId);

        $data = $request->validate([
            'notify_admin_on_queue_created' => 'nullable|boolean',
            'notify_driver_on_status_changed' => 'nullable|boolean',
            'send_reminders' => 'nullable|boolean',
            'reminder_minutes_before' => 'nullable|integer|min:1|max:60',
            'admin_phone' => 'nullable|string|max:20',
        ]);

        $settings->update(array_filter([
            'notify_admin_on_queue_created' => $data['notify_admin_on_queue_created'] ?? null,
            'notify_driver_on_status_changed' => $data['notify_driver_on_status_changed'] ?? null,
            'send_reminders' => $data['send_reminders'] ?? null,
            'reminder_minutes_before' => $data['reminder_minutes_before'] ?? null,
            'admin_phone' => $data['admin_phone'] ?? null,
        ], fn($v) => $v !== null));

        return response()->json([
            'data' => $settings,
            'message' => 'Notification settings updated successfully',
        ]);
    }
}
