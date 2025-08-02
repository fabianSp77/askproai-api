<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\EventMCPServer;
use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventsApiController extends Controller
{
    use UsesMCPServers;

    public function __construct(
        private EventMCPServer $eventMCP
    ) {}

    /**
     * Get event history
     */
    public function index(Request $request)
    {
        $filters = [
            'company_id' => auth()->user()->company_id,
            'limit' => $request->input('limit', 100)
        ];

        if ($request->has('event_names')) {
            $filters['event_names'] = explode(',', $request->input('event_names'));
        }

        if ($request->has('entity_type')) {
            $filters['entity_type'] = $request->input('entity_type');
        }

        if ($request->has('entity_id')) {
            $filters['entity_id'] = $request->input('entity_id');
        }

        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }

        $result = $this->executeMCPTask('query events', $filters);

        return response()->json($result);
    }

    /**
     * Get event timeline for an entity
     */
    public function timeline(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer'
        ]);

        $result = $this->executeMCPTask('get event timeline', [
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id'),
            'include_related' => $request->boolean('include_related', false)
        ]);

        return response()->json($result);
    }

    /**
     * Get event statistics
     */
    public function stats(Request $request)
    {
        $request->validate([
            'period' => 'required|in:today,week,month,year',
            'group_by' => 'nullable|in:event_name,user,hour,day'
        ]);

        $result = $this->executeMCPTask('get event stats', [
            'company_id' => auth()->user()->company_id,
            'period' => $request->input('period'),
            'group_by' => $request->input('group_by', 'event_name')
        ]);

        return response()->json($result);
    }

    /**
     * Get event subscriptions
     */
    public function subscriptions()
    {
        $subscriptions = DB::table('event_subscriptions')
            ->where('company_id', auth()->user()->company_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'event_names' => json_decode($subscription->event_names, true),
                    'webhook_url' => $subscription->webhook_url,
                    'filters' => json_decode($subscription->filters, true),
                    'active' => (bool) $subscription->active,
                    'retry_count' => $subscription->retry_count,
                    'last_triggered_at' => $subscription->last_triggered_at,
                    'created_at' => $subscription->created_at
                ];
            });

        return response()->json([
            'subscriptions' => $subscriptions
        ]);
    }

    /**
     * Create event subscription
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'event_names' => 'required|array',
            'event_names.*' => 'required|string',
            'webhook_url' => 'required|url',
            'filters' => 'nullable|array',
            'active' => 'nullable|boolean'
        ]);

        $result = $this->executeMCPTask('create event subscription', [
            'event_names' => $request->input('event_names'),
            'webhook_url' => $request->input('webhook_url'),
            'filters' => $request->input('filters', []),
            'active' => $request->boolean('active', true)
        ]);

        return response()->json($result);
    }

    /**
     * Update event subscription
     */
    public function updateSubscription(Request $request, $id)
    {
        $subscription = DB::table('event_subscriptions')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $updates = [];

        if ($request->has('active')) {
            $updates['active'] = $request->boolean('active');
        }

        if ($request->has('webhook_url')) {
            $updates['webhook_url'] = $request->input('webhook_url');
        }

        if ($request->has('event_names')) {
            $updates['event_names'] = json_encode($request->input('event_names'));
        }

        if ($request->has('filters')) {
            $updates['filters'] = json_encode($request->input('filters'));
        }

        $updates['updated_at'] = now();

        DB::table('event_subscriptions')
            ->where('id', $id)
            ->update($updates);

        return response()->json(['success' => true]);
    }

    /**
     * Delete event subscription
     */
    public function deleteSubscription($id)
    {
        $deleted = DB::table('event_subscriptions')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get webhook logs
     */
    public function webhookLogs(Request $request)
    {
        $query = DB::table('webhook_logs')
            ->whereIn('url', function ($subquery) {
                $subquery->select('webhook_url')
                    ->from('event_subscriptions')
                    ->where('company_id', auth()->user()->company_id);
            });

        if ($request->has('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->has('success')) {
            $query->where('success', $request->boolean('success'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'logs' => $logs
        ]);
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url',
            'event_name' => 'required|string',
            'payload' => 'nullable|array'
        ]);

        try {
            $response = \Http::timeout(10)
                ->post($request->input('webhook_url'), [
                    'event' => $request->input('event_name'),
                    'payload' => $request->input('payload', []),
                    'timestamp' => now()->toIso8601String(),
                    'test' => true
                ]);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available event schemas
     */
    public function schemas()
    {
        $standardEvents = [
            'appointment.created',
            'appointment.updated',
            'appointment.cancelled',
            'appointment.rescheduled',
            'call.created',
            'call.updated',
            'call.completed',
            'call.failed',
            'customer.created',
            'customer.merged'
        ];

        $schemas = [];
        foreach ($standardEvents as $event) {
            $result = $this->eventMCP->executeTool('getEventSchema', [
                'event_name' => $event
            ]);
            
            if (!isset($result['error'])) {
                $schemas[] = $result;
            }
        }

        // Add custom events
        $customEvents = DB::table('custom_events')
            ->where('company_id', auth()->user()->company_id)
            ->where('active', true)
            ->get()
            ->map(function ($event) {
                return [
                    'event_name' => $event->name,
                    'schema' => json_decode($event->schema, true),
                    'category' => $event->category,
                    'description' => $event->description
                ];
            });

        return response()->json([
            'standard_events' => $schemas,
            'custom_events' => $customEvents
        ]);
    }
}