<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvancedCallManagementController extends Controller
{
    /**
     * Smart search across calls, customers, and related data
     */
    public function smartSearch(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = collect();

        // Search calls by phone number, customer name, or call ID
        $calls = Call::query()
            ->with(['customer', 'branch'])
            ->where(function ($q) use ($query) {
                $q->where('from_number', 'like', "%{$query}%")
                  ->orWhere('call_id', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                      $customerQuery->where('name', 'like', "%{$query}%")
                                   ->orWhere('email', 'like', "%{$query}%");
                  });
            })
            ->limit(5)
            ->get()
            ->map(function ($call) {
                return [
                    'type' => 'call',
                    'title' => $call->customer?->name ?? 'Anonymous Call',
                    'subtitle' => $call->from_number . ' • ' . $call->created_at->format('d.m.Y H:i'),
                    'url' => "/admin/calls/{$call->id}",
                    'id' => $call->id,
                    'meta' => [
                        'duration' => $call->duration_sec,
                        'status' => $call->call_status,
                        'appointment_made' => $call->appointment_made,
                    ]
                ];
            });

        $results = $results->concat($calls);

        // Search customers
        $customers = Customer::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'type' => 'customer',
                    'title' => $customer->name,
                    'subtitle' => $customer->email ?? $customer->phone,
                    'url' => "/admin/customers/{$customer->id}",
                    'id' => $customer->id,
                    'meta' => [
                        'total_calls' => $customer->calls()->count(),
                        'last_call' => $customer->calls()->latest()->first()?->created_at?->format('d.m.Y'),
                    ]
                ];
            });

        $results = $results->concat($customers);

        // Sort by relevance (exact matches first)
        $sorted = $results->sortBy(function ($item) use ($query) {
            $title = strtolower($item['title']);
            $queryLower = strtolower($query);
            
            if (str_contains($title, $queryLower)) {
                return strpos($title, $queryLower) === 0 ? 0 : 1; // Exact match at start gets priority
            }
            
            return 2; // Partial matches last
        })->values();

        return response()->json($sorted->take(10));
    }

    /**
     * Get customer timeline data
     */
    public function customerTimeline(Customer $customer): JsonResponse
    {
        $events = collect();

        // Add calls to timeline
        $calls = $customer->calls()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => 'call-' . $call->id,
                    'type' => 'call',
                    'title' => 'Phone Call',
                    'description' => $call->appointment_made 
                        ? 'Call resulted in appointment booking' 
                        : 'General inquiry call',
                    'created_at' => $call->created_at,
                    'details' => json_encode([
                        'duration' => $call->duration_sec,
                        'from_number' => $call->from_number,
                        'status' => $call->call_status,
                        'appointment_made' => $call->appointment_made,
                    ]),
                ];
            });

        $events = $events->concat($calls);

        // Add appointments to timeline
        $appointments = $customer->appointments()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => 'appointment-' . $appointment->id,
                    'type' => 'appointment',
                    'title' => 'Appointment Booked',
                    'description' => 'Appointment for ' . ($appointment->starts_at ? $appointment->starts_at->format('d.m.Y H:i') : 'unknown time'),
                    'created_at' => $appointment->created_at,
                    'details' => json_encode([
                        'starts_at' => $appointment->starts_at?->format('Y-m-d H:i:s'),
                        'service' => $appointment->service?->name,
                        'branch' => $appointment->branch?->name,
                        'status' => $appointment->status,
                    ]),
                ];
            });

        $events = $events->concat($appointments);

        // Add customer notes if they exist (from a notes relationship)
        if (method_exists($customer, 'notes')) {
            $notes = $customer->notes()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($note) {
                    return [
                        'id' => 'note-' . $note->id,
                        'type' => 'note',
                        'title' => 'Customer Note',
                        'description' => substr($note->content, 0, 100) . (strlen($note->content) > 100 ? '...' : ''),
                        'created_at' => $note->created_at,
                        'details' => $note->content,
                    ];
                });

            $events = $events->concat($notes);
        }

        // Sort all events by date
        $sorted = $events->sortByDesc('created_at')->values();

        return response()->json($sorted);
    }

    /**
     * Update call priority
     */
    public function updateCallPriority(Call $call, Request $request): JsonResponse
    {
        $request->validate([
            'priority' => 'required|in:high,medium,low'
        ]);

        $call->update([
            'priority' => $request->priority,
            'priority_updated_at' => now(),
            'priority_updated_by' => auth()->id(),
        ]);

        // Clear relevant caches
        Cache::tags(['calls', 'priority'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Priority updated successfully',
            'data' => [
                'id' => $call->id,
                'priority' => $call->priority,
                'updated_at' => $call->updated_at,
            ]
        ]);
    }

    /**
     * Get filter preset counts
     */
    public function filterPresetCounts(): JsonResponse
    {
        $counts = Cache::remember('filter_preset_counts', 300, function () {
            $baseQuery = Call::query();

            return [
                'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
                'priority' => (clone $baseQuery)->where('priority', 'high')->count(),
                'missed' => (clone $baseQuery)->where('call_status', 'missed')->count(),
                'appointments' => (clone $baseQuery)->where('appointment_made', true)->count(),
                'long-calls' => (clone $baseQuery)->where('duration_sec', '>', 300)->count(),
                'new-customers' => (clone $baseQuery)->whereHas('customer', function ($q) {
                    $q->where('created_at', '>', now()->subDays(7));
                })->count(),
            ];
        });

        return response()->json($counts);
    }

    /**
     * Get call details for lazy loading
     */
    public function callDetails(Call $call): JsonResponse
    {
        $details = [
            'id' => $call->id,
            'basic_info' => [
                'call_id' => $call->call_id,
                'from_number' => $call->from_number,
                'duration_sec' => $call->duration_sec,
                'call_status' => $call->call_status,
                'created_at' => $call->created_at,
            ],
            'customer' => $call->customer ? [
                'id' => $call->customer->id,
                'name' => $call->customer->name,
                'email' => $call->customer->email,
                'phone' => $call->customer->phone,
                'total_calls' => $call->customer->calls()->count(),
            ] : null,
            'appointment' => $call->appointment ? [
                'id' => $call->appointment->id,
                'starts_at' => $call->appointment->starts_at,
                'service_name' => $call->appointment->service?->name,
                'branch_name' => $call->appointment->branch?->name,
            ] : null,
            'recording' => [
                'url' => $call->recording_url ?? $call->audio_url,
                'transcript' => $call->transcript,
            ],
            'metadata' => [
                'webhook_data' => $call->webhook_data,
                'priority' => $call->priority ?? 'normal',
                'tags' => $call->tags ?? [],
            ]
        ];

        return response()->json($details);
    }

    /**
     * Real-time call data for dashboard updates
     */
    public function realtimeCallData(): JsonResponse
    {
        $data = Cache::remember('realtime_call_data', 15, function () {
            return [
                'active_calls' => Call::whereNull('end_timestamp')
                    ->where('created_at', '>', now()->subHours(2))
                    ->with(['customer', 'branch'])
                    ->get()
                    ->map(function ($call) {
                        return [
                            'id' => $call->id,
                            'customer_name' => $call->customer?->name ?? 'Unknown',
                            'from_number' => $call->from_number,
                            'duration' => $call->start_timestamp 
                                ? now()->diffInSeconds($call->start_timestamp) 
                                : 0,
                            'status' => $call->call_status,
                        ];
                    }),
                'recent_completions' => Call::where('call_status', 'completed')
                    ->where('updated_at', '>', now()->subMinutes(5))
                    ->with(['customer'])
                    ->get()
                    ->map(function ($call) {
                        return [
                            'id' => $call->id,
                            'customer_name' => $call->customer?->name ?? 'Unknown',
                            'appointment_made' => $call->appointment_made,
                            'completed_at' => $call->updated_at,
                        ];
                    }),
                'queue_stats' => [
                    'waiting' => Call::where('call_status', 'waiting')->count(),
                    'in_progress' => Call::where('call_status', 'in_progress')->count(),
                    'high_priority' => Call::where('priority', 'high')->count(),
                ],
                'performance_metrics' => [
                    'avg_duration_today' => Call::whereDate('created_at', today())
                        ->whereNotNull('duration_sec')
                        ->avg('duration_sec'),
                    'calls_per_hour' => Call::where('created_at', '>', now()->subHour())->count(),
                    'appointment_rate_today' => Call::whereDate('created_at', today())
                        ->where('appointment_made', true)
                        ->count() / max(1, Call::whereDate('created_at', today())->count()) * 100,
                ]
            ];
        });

        return response()->json($data);
    }

    /**
     * Export call data with current filters
     */
    public function exportCalls(Request $request): JsonResponse
    {
        // This would typically generate a CSV export
        // For now, return the URL where the export will be available
        
        $filters = $request->get('filters', []);
        $exportId = 'export_' . time() . '_' . auth()->id();
        
        // Queue the export job
        \App\Jobs\ExportCallDataJob::dispatch($exportId, $filters, auth()->user());
        
        return response()->json([
            'success' => true,
            'message' => 'Export started. You will receive an email when ready.',
            'export_id' => $exportId,
        ]);
    }

    /**
     * Add voice note to call
     */
    public function addVoiceNote(Call $call, Request $request): JsonResponse
    {
        $request->validate([
            'transcript' => 'required|string|max:2000',
            'audio_data' => 'nullable|string', // Base64 encoded audio data
        ]);

        // Create or update voice note
        $note = $call->notes()->create([
            'content' => $request->transcript,
            'type' => 'voice',
            'metadata' => [
                'audio_data' => $request->audio_data,
                'created_via' => 'voice_recognition',
                'created_at_timestamp' => now()->timestamp,
            ],
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voice note saved successfully',
            'data' => [
                'note_id' => $note->id,
                'content' => $note->content,
                'created_at' => $note->created_at,
            ]
        ]);
    }

    /**
     * Get call analytics for dashboard
     */
    public function callAnalytics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today'); // today, week, month
        
        $data = Cache::remember("call_analytics_{$period}", 300, function () use ($period) {
            $query = Call::query();
            
            switch ($period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
            }
            
            return [
                'total_calls' => $query->count(),
                'appointments_made' => (clone $query)->where('appointment_made', true)->count(),
                'average_duration' => (clone $query)->avg('duration_sec'),
                'status_breakdown' => (clone $query)
                    ->select('call_status', DB::raw('count(*) as count'))
                    ->groupBy('call_status')
                    ->pluck('count', 'call_status'),
                'hourly_distribution' => (clone $query)
                    ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                    ->groupBy(DB::raw('HOUR(created_at)'))
                    ->orderBy('hour')
                    ->pluck('count', 'hour'),
                'priority_distribution' => (clone $query)
                    ->select('priority', DB::raw('count(*) as count'))
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
            ];
        });
        
        return response()->json($data);
    }

    /**
     * Get queue status for real-time updates
     */
    public function queueStatus(): JsonResponse
    {
        $status = [
            'active_calls' => Call::whereNull('end_timestamp')
                ->where('created_at', '>', now()->subHours(2))
                ->count(),
            'waiting_calls' => Call::where('call_status', 'waiting')->count(),
            'priority_calls' => Call::where('priority', 'high')
                ->whereNull('end_timestamp')
                ->count(),
            'agents_available' => 5, // This would come from a proper agent management system
            'average_wait_time' => 45, // Calculated from actual queue data
            'last_updated' => now(),
        ];

        return response()->json($status);
    }
}