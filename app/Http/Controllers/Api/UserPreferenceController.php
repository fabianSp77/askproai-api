<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    /**
     * Get column preferences for a resource
     */
    public function getColumnPreferences(Request $request, string $resource)
    {
        $userId = Auth::id();

        // Get column order preference
        $columnOrder = UserPreference::getColumnOrder($userId, $resource);

        // Get column visibility preference
        $columnVisibility = UserPreference::get($userId, "{$resource}_column_visibility", []);

        return response()->json([
            'order' => $columnOrder,
            'visibility' => $columnVisibility
        ]);
    }

    /**
     * Save column preferences
     */
    public function saveColumnPreferences(Request $request)
    {
        $validated = $request->validate([
            'resource' => 'required|string',
            'columns' => 'required|array',
            'visibility' => 'nullable|array'
        ]);

        $userId = Auth::id();
        $resource = $validated['resource'];

        // Save column order
        UserPreference::saveColumnOrder($userId, $resource, $validated['columns']);

        // Save visibility if provided
        if (isset($validated['visibility'])) {
            UserPreference::set($userId, "{$resource}_column_visibility", $validated['visibility']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Spalteneinstellungen gespeichert'
        ]);
    }

    /**
     * Reset column preferences to defaults
     */
    public function resetColumnPreferences(Request $request, string $resource)
    {
        $userId = Auth::id();

        // Delete column order preference
        UserPreference::where('user_id', $userId)
            ->where('key', "{$resource}_column_order")
            ->delete();

        // Delete column visibility preference
        UserPreference::where('user_id', $userId)
            ->where('key', "{$resource}_column_visibility")
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Spalteneinstellungen zurückgesetzt'
        ]);
    }
}