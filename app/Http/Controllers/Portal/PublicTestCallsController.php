<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicTestCallsController extends Controller
{
    /**
     * Public test endpoint - no auth required for testing
     */
    public function apiIndex(Request $request)
    {
        // Return test data without authentication
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
                        'name' => 'Max Mustermann',
                        'email' => 'max@example.com'
                    ],
                    'branch' => [
                        'id' => 1,
                        'name' => 'Hauptfiliale'
                    ],
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
                ],
                [
                    'id' => 3,
                    'phone_number' => '+49 555 123456',
                    'status' => 'requires_action',
                    'duration_sec' => 120,
                    'created_at' => now()->subHours(1)->toIso8601String(),
                    'customer' => [
                        'id' => 2,
                        'name' => 'Erika Musterfrau',
                        'email' => 'erika@example.com'
                    ],
                    'branch' => null,
                    'appointment' => null,
                    'assigned_to' => null,
                    'callback_scheduled_at' => null
                ]
            ],
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => 3,
            'from' => 1,
            'to' => 3,
            'links' => [
                ['url' => null, 'label' => '&laquo; Previous', 'active' => false],
                ['url' => '#', 'label' => '1', 'active' => true],
                ['url' => null, 'label' => 'Next &raquo;', 'active' => false]
            ]
        ]);
    }
}