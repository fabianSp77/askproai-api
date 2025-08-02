<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimpleAnalyticsController extends Controller
{
    /**
     * Display analytics dashboard with mock data
     */
    public function index(Request $request)
    {
        // Mock analytics data
        $analytics = [
            'call_stats' => [
                'total_calls' => 156,
                'total_duration' => 12340, // seconds
                'average_duration' => 79,
                'calls_today' => 12,
                'calls_this_week' => 45,
                'calls_this_month' => 156
            ],
            'appointment_stats' => [
                'total_appointments' => 89,
                'completed' => 67,
                'cancelled' => 12,
                'no_show' => 10,
                'conversion_rate' => 57.1
            ],
            'top_services' => [
                ['name' => 'Allgemeine Beratung', 'count' => 45],
                ['name' => 'Kontrolle', 'count' => 32],
                ['name' => 'Behandlung', 'count' => 28],
                ['name' => 'Notfall', 'count' => 15]
            ],
            'hourly_distribution' => [
                ['hour' => 8, 'calls' => 5],
                ['hour' => 9, 'calls' => 12],
                ['hour' => 10, 'calls' => 18],
                ['hour' => 11, 'calls' => 22],
                ['hour' => 12, 'calls' => 15],
                ['hour' => 13, 'calls' => 8],
                ['hour' => 14, 'calls' => 16],
                ['hour' => 15, 'calls' => 19],
                ['hour' => 16, 'calls' => 21],
                ['hour' => 17, 'calls' => 15],
                ['hour' => 18, 'calls' => 5]
            ],
            'recent_calls' => [
                [
                    'id' => 1,
                    'customer_name' => 'Max Müller',
                    'phone' => '+49 151 12345678',
                    'duration' => 120,
                    'status' => 'completed',
                    'created_at' => now()->subHours(1)
                ],
                [
                    'id' => 2,
                    'customer_name' => 'Anna Schmidt',
                    'phone' => '+49 171 98765432',
                    'duration' => 89,
                    'status' => 'completed',
                    'created_at' => now()->subHours(2)
                ]
            ]
        ];
        
        // Date range
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfDay()->format('Y-m-d'));
        
        return view('portal.analytics.simple-index', compact('analytics', 'startDate', 'endDate'));
    }
}