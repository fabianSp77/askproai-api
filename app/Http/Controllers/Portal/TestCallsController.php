<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestCallsController extends Controller
{
    /**
     * Test API endpoint - very simple version
     */
    public function apiIndex(Request $request)
    {
        // Get authenticated user
        $user = Auth::guard('portal')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        // Return simple test data
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'phone_number' => '+49 123 456789',
                    'status' => 'completed',
                    'duration_sec' => 180,
                    'created_at' => now()->subHours(2)->toIso8601String(),
                    'customer' => [
                        'id' => 1,
                        'name' => 'Test Kunde'
                    ],
                    'branch' => null,
                    'appointment' => null,
                    'assigned_to' => null,
                    'callback_scheduled_at' => null
                ],
                [
                    'id' => 2,
                    'phone_number' => '+49 987 654321',
                    'status' => 'new',
                    'duration_sec' => 0,
                    'created_at' => now()->subMinutes(30)->toIso8601String(),
                    'customer' => null,
                    'branch' => null,
                    'appointment' => null,
                    'assigned_to' => null,
                    'callback_scheduled_at' => null
                ]
            ],
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => 2,
            'from' => 1,
            'to' => 2,
            'links' => [
                ['url' => null, 'label' => '&laquo; Previous', 'active' => false],
                ['url' => '#', 'label' => '1', 'active' => true],
                ['url' => null, 'label' => 'Next &raquo;', 'active' => false]
            ]
        ]);
    }
}