<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Update user's WhatsApp phone number
     * PUT /api/users/{id}/phone
     */
    public function updatePhone(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Allow users to update their own phone or admins to update any user's phone
        $authenticatedUser = $request->user();
        
        if (!$authenticatedUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($authenticatedUser->id !== $id && !$authenticatedUser->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden - You can only update your own phone number'], 403);
        }

        $data = $request->validate([
            'whatsapp_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[1-9]\d{1,14}$/', // E.164 format
            ],
            'whatsapp_notifications_enabled' => 'nullable|boolean',
        ]);

        $user->update([
            'whatsapp_phone' => $data['whatsapp_phone'] ?? $user->whatsapp_phone,
            'whatsapp_notifications_enabled' => $data['whatsapp_notifications_enabled'] ?? $user->whatsapp_notifications_enabled,
        ]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'whatsapp_phone' => $user->whatsapp_phone,
                'whatsapp_notifications_enabled' => $user->whatsapp_notifications_enabled,
            ],
            'message' => 'WhatsApp phone number updated successfully',
        ]);
    }

    /**
     * Get user's profile with WhatsApp settings
     * GET /api/users/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $authenticatedUser = $request->user();
        
        if (!$authenticatedUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($authenticatedUser->id !== $id && !$authenticatedUser->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'whatsapp_phone' => $user->whatsapp_phone,
                'whatsapp_notifications_enabled' => $user->whatsapp_notifications_enabled,
                'created_at' => $user->created_at,
            ],
        ]);
    }
}
