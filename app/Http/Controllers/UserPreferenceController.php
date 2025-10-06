<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserPreferenceController extends Controller
{
    /**
     * Get column preferences for a resource
     */
    public function getColumnPreferences(Request $request, string $resource): JsonResponse
    {
        $userId = auth()->id();

        $order = UserPreference::getColumnOrder($userId, $resource);
        $visibility = UserPreference::getColumnVisibility($userId, $resource);

        return response()->json([
            'order' => $order,
            'visibility' => $visibility,
        ]);
    }

    /**
     * Save column preferences
     */
    public function saveColumnPreferences(Request $request): JsonResponse
    {
        $request->validate([
            'resource' => 'required|string',
            'order' => 'array',
            'visibility' => 'array',
        ]);

        $userId = auth()->id();
        $resource = $request->input('resource');

        // Save column order
        if ($request->has('order')) {
            UserPreference::saveColumnOrder($userId, $resource, $request->input('order'));
        }

        // Save column visibility
        if ($request->has('visibility')) {
            UserPreference::saveColumnVisibility($userId, $resource, $request->input('visibility'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferences saved successfully',
        ]);
    }

    /**
     * Reset column preferences for a resource
     */
    public function resetColumnPreferences(Request $request, string $resource): JsonResponse
    {
        $userId = auth()->id();

        UserPreference::resetColumnPreferences($userId, $resource);

        return response()->json([
            'success' => true,
            'message' => 'Preferences reset successfully',
        ]);
    }
}