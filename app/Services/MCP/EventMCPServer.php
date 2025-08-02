<?php

namespace App\Services\MCP;

use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Events\CallCreated;
use App\Events\CallUpdated;
use App\Events\CallCompleted;
use App\Events\CallFailed;
use App\Events\CustomerCreated;
use App\Events\CustomerMerged;
use App\Events\DashboardStatsUpdated;
use App\Events\NotificationCreated;
use App\Models\EventLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class EventMCPServer extends BaseMCPServer
{
    public function getName(): string
    {
        return 'event-mcp';
    }

    public function getDescription(): string
    {
        return 'Comprehensive event management system for tracking, replaying, and analyzing business events';
    }

    public function getTools(): array
    {
        return [
            [
                'name' => 'dispatchEvent',
                'description' => 'Dispatch a business event with tracking and audit trail',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'event_name' => [
                            'type' => 'string',
                            'description' => 'Event name (e.g., appointment.created, call.completed)'
                        ],
                        'payload' => [
                            'type' => 'object',
                            'description' => 'Event payload data'
                        ],
                        'user_id' => [
                            'type' => 'integer',
                            'description' => 'User ID who triggered the event'
                        ],
                        'company_id' => [
                            'type' => 'integer',
                            'description' => 'Company ID for multi-tenant context'
                        ],
                        'metadata' => [
                            'type' => 'object',
                            'description' => 'Additional metadata (IP, user agent, etc.)'
                        ]
                    ],
                    'required' => ['event_name', 'payload']
                ]
            ],
            [
                'name' => 'queryEvents',
                'description' => 'Query event history with filters',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'event_names' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Filter by event names'
                        ],
                        'company_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by company'
                        ],
                        'user_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by user'
                        ],
                        'entity_type' => [
                            'type' => 'string',
                            'description' => 'Filter by entity type (appointment, call, customer)'
                        ],
                        'entity_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by entity ID'
                        ],
                        'date_from' => [
                            'type' => 'string',
                            'description' => 'Start date (YYYY-MM-DD)'
                        ],
                        'date_to' => [
                            'type' => 'string',
                            'description' => 'End date (YYYY-MM-DD)'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Limit results (default: 100)'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'replayEvents',
                'description' => 'Replay events for debugging or recovery',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'event_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'description' => 'Specific event IDs to replay'
                        ],
                        'filters' => [
                            'type' => 'object',
                            'description' => 'Filters to select events to replay'
                        ],
                        'dry_run' => [
                            'type' => 'boolean',
                            'description' => 'Simulate replay without executing'
                        ]
                    ],
                    'required' => ['event_ids']
                ]
            ],
            [
                'name' => 'getEventTimeline',
                'description' => 'Get timeline of events for an entity',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'entity_type' => [
                            'type' => 'string',
                            'description' => 'Entity type (appointment, call, customer)'
                        ],
                        'entity_id' => [
                            'type' => 'integer',
                            'description' => 'Entity ID'
                        ],
                        'include_related' => [
                            'type' => 'boolean',
                            'description' => 'Include related entity events'
                        ]
                    ],
                    'required' => ['entity_type', 'entity_id']
                ]
            ],
            [
                'name' => 'getEventStats',
                'description' => 'Get event statistics and analytics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => [
                            'type' => 'integer',
                            'description' => 'Company ID for filtering'
                        ],
                        'period' => [
                            'type' => 'string',
                            'enum' => ['today', 'week', 'month', 'year'],
                            'description' => 'Time period for stats'
                        ],
                        'group_by' => [
                            'type' => 'string',
                            'enum' => ['event_name', 'user', 'hour', 'day'],
                            'description' => 'Group statistics by'
                        ]
                    ],
                    'required' => ['period']
                ]
            ],
            [
                'name' => 'createEventSubscription',
                'description' => 'Create webhook subscription for specific events',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'event_names' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Events to subscribe to'
                        ],
                        'webhook_url' => [
                            'type' => 'string',
                            'description' => 'Webhook URL to call'
                        ],
                        'filters' => [
                            'type' => 'object',
                            'description' => 'Additional filters for events'
                        ],
                        'active' => [
                            'type' => 'boolean',
                            'description' => 'Whether subscription is active'
                        ]
                    ],
                    'required' => ['event_names', 'webhook_url']
                ]
            ],
            [
                'name' => 'getEventSchema',
                'description' => 'Get schema definition for an event type',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'event_name' => [
                            'type' => 'string',
                            'description' => 'Event name to get schema for'
                        ]
                    ],
                    'required' => ['event_name']
                ]
            ],
            [
                'name' => 'createCustomEvent',
                'description' => 'Create a custom business event',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Custom event name'
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => 'Event category'
                        ],
                        'schema' => [
                            'type' => 'object',
                            'description' => 'JSON schema for event payload'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Event description'
                        ]
                    ],
                    'required' => ['name', 'category', 'schema']
                ]
            ]
        ];
    }

    public function executeTool(string $toolName, array $args): array
    {
        $this->trackUsage($toolName, $args);

        try {
            return match ($toolName) {
                'dispatchEvent' => $this->dispatchEvent($args),
                'queryEvents' => $this->queryEvents($args),
                'replayEvents' => $this->replayEvents($args),
                'getEventTimeline' => $this->getEventTimeline($args),
                'getEventStats' => $this->getEventStats($args),
                'createEventSubscription' => $this->createEventSubscription($args),
                'getEventSchema' => $this->getEventSchema($args),
                'createCustomEvent' => $this->createCustomEvent($args),
                default => ['error' => "Unknown tool: {$toolName}"]
            };
        } catch (\Exception $e) {
            Log::error("EventMCPServer error in {$toolName}", [
                'error' => $e->getMessage(),
                'args' => $args
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    private function dispatchEvent(array $args): array
    {
        $eventName = $args['event_name'];
        $payload = $args['payload'];
        $userId = $args['user_id'] ?? auth()->id();
        $companyId = $args['company_id'] ?? auth()->user()?->company_id;
        $metadata = $args['metadata'] ?? [];

        // Log the event
        $eventLog = DB::table('event_logs')->insertGetId([
            'event_name' => $eventName,
            'payload' => json_encode($payload),
            'user_id' => $userId,
            'company_id' => $companyId,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        // Dispatch appropriate Laravel event
        $event = $this->createLaravelEvent($eventName, $payload);
        if ($event) {
            Event::dispatch($event);
        }

        // Trigger webhooks
        $this->triggerWebhooks($eventName, $payload, $companyId);

        return [
            'event_id' => $eventLog,
            'event_name' => $eventName,
            'dispatched_at' => now()->toIso8601String()
        ];
    }

    private function queryEvents(array $args): array
    {
        $query = DB::table('event_logs');

        if (!empty($args['event_names'])) {
            $query->whereIn('event_name', $args['event_names']);
        }

        if (isset($args['company_id'])) {
            $query->where('company_id', $args['company_id']);
        }

        if (isset($args['user_id'])) {
            $query->where('user_id', $args['user_id']);
        }

        if (isset($args['entity_type']) && isset($args['entity_id'])) {
            $query->whereJsonContains('payload->entity_type', $args['entity_type'])
                  ->whereJsonContains('payload->entity_id', $args['entity_id']);
        }

        if (!empty($args['date_from'])) {
            $query->where('created_at', '>=', $args['date_from']);
        }

        if (!empty($args['date_to'])) {
            $query->where('created_at', '<=', $args['date_to'] . ' 23:59:59');
        }

        $limit = $args['limit'] ?? 100;
        $events = $query->orderBy('created_at', 'desc')
                       ->limit($limit)
                       ->get();

        return [
            'events' => $events->map(fn($e) => [
                'id' => $e->id,
                'event_name' => $e->event_name,
                'payload' => json_decode($e->payload, true),
                'user_id' => $e->user_id,
                'company_id' => $e->company_id,
                'metadata' => json_decode($e->metadata, true),
                'created_at' => $e->created_at
            ])->toArray(),
            'count' => $events->count()
        ];
    }

    private function replayEvents(array $args): array
    {
        $eventIds = $args['event_ids'];
        $dryRun = $args['dry_run'] ?? false;

        $events = DB::table('event_logs')
                    ->whereIn('id', $eventIds)
                    ->orderBy('created_at')
                    ->get();

        $replayed = [];
        $errors = [];

        foreach ($events as $event) {
            try {
                if (!$dryRun) {
                    $laravelEvent = $this->createLaravelEvent(
                        $event->event_name,
                        json_decode($event->payload, true)
                    );
                    
                    if ($laravelEvent) {
                        Event::dispatch($laravelEvent);
                    }
                }

                $replayed[] = [
                    'id' => $event->id,
                    'event_name' => $event->event_name,
                    'status' => 'replayed'
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $event->id,
                    'event_name' => $event->event_name,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'dry_run' => $dryRun,
            'replayed' => $replayed,
            'errors' => $errors,
            'total' => count($events)
        ];
    }

    private function getEventTimeline(array $args): array
    {
        $entityType = $args['entity_type'];
        $entityId = $args['entity_id'];
        $includeRelated = $args['include_related'] ?? false;

        $query = DB::table('event_logs')
                   ->whereJsonContains('payload->entity_type', $entityType)
                   ->whereJsonContains('payload->entity_id', $entityId);

        // Include related events if requested
        if ($includeRelated) {
            // This would need to be customized based on entity relationships
            // For now, we'll just get events from the same time period
            $mainEvent = $query->first();
            if ($mainEvent) {
                $startTime = \Carbon\Carbon::parse($mainEvent->created_at)->subHours(2);
                $endTime = \Carbon\Carbon::parse($mainEvent->created_at)->addHours(2);
                
                $query->orWhereBetween('created_at', [$startTime, $endTime]);
            }
        }

        $events = $query->orderBy('created_at')->get();

        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'timeline' => $events->map(fn($e) => [
                'id' => $e->id,
                'event_name' => $e->event_name,
                'payload' => json_decode($e->payload, true),
                'user' => $e->user_id ? User::find($e->user_id)?->name : null,
                'created_at' => $e->created_at,
                'time_ago' => \Carbon\Carbon::parse($e->created_at)->diffForHumans()
            ])->toArray()
        ];
    }

    private function getEventStats(array $args): array
    {
        $companyId = $args['company_id'] ?? auth()->user()?->company_id;
        $period = $args['period'];
        $groupBy = $args['group_by'] ?? 'event_name';

        $query = DB::table('event_logs');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Apply period filter
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfDay()
        };

        $query->where('created_at', '>=', $startDate);

        // Group by logic
        $stats = match ($groupBy) {
            'event_name' => $query->select('event_name', DB::raw('count(*) as count'))
                                 ->groupBy('event_name')
                                 ->orderBy('count', 'desc')
                                 ->get(),
            'user' => $query->select('user_id', DB::raw('count(*) as count'))
                           ->whereNotNull('user_id')
                           ->groupBy('user_id')
                           ->orderBy('count', 'desc')
                           ->get()
                           ->map(function ($item) {
                               $item->user_name = User::find($item->user_id)?->name;
                               return $item;
                           }),
            'hour' => $query->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                           ->groupBy('hour')
                           ->orderBy('hour')
                           ->get(),
            'day' => $query->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                          ->groupBy('date')
                          ->orderBy('date')
                          ->get(),
            default => collect()
        };

        return [
            'period' => $period,
            'group_by' => $groupBy,
            'total_events' => $query->count(),
            'stats' => $stats->toArray()
        ];
    }

    private function createEventSubscription(array $args): array
    {
        $subscriptionId = DB::table('event_subscriptions')->insertGetId([
            'event_names' => json_encode($args['event_names']),
            'webhook_url' => $args['webhook_url'],
            'filters' => json_encode($args['filters'] ?? []),
            'active' => $args['active'] ?? true,
            'company_id' => auth()->user()?->company_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'subscription_id' => $subscriptionId,
            'event_names' => $args['event_names'],
            'webhook_url' => $args['webhook_url'],
            'active' => $args['active'] ?? true
        ];
    }

    private function getEventSchema(array $args): array
    {
        $eventName = $args['event_name'];

        // Define schemas for known events
        $schemas = [
            'appointment.created' => [
                'type' => 'object',
                'properties' => [
                    'appointment_id' => ['type' => 'integer'],
                    'customer_id' => ['type' => 'integer'],
                    'staff_id' => ['type' => 'integer'],
                    'service_id' => ['type' => 'integer'],
                    'branch_id' => ['type' => 'integer'],
                    'starts_at' => ['type' => 'string', 'format' => 'date-time'],
                    'ends_at' => ['type' => 'string', 'format' => 'date-time'],
                    'status' => ['type' => 'string']
                ],
                'required' => ['appointment_id', 'customer_id', 'starts_at']
            ],
            'call.completed' => [
                'type' => 'object',
                'properties' => [
                    'call_id' => ['type' => 'integer'],
                    'from_number' => ['type' => 'string'],
                    'to_number' => ['type' => 'string'],
                    'duration' => ['type' => 'integer'],
                    'status' => ['type' => 'string'],
                    'recording_url' => ['type' => 'string'],
                    'transcript' => ['type' => 'string']
                ],
                'required' => ['call_id', 'from_number', 'duration']
            ],
            'customer.created' => [
                'type' => 'object',
                'properties' => [
                    'customer_id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'source' => ['type' => 'string']
                ],
                'required' => ['customer_id', 'phone']
            ]
        ];

        if (!isset($schemas[$eventName])) {
            // Check custom events
            $customEvent = DB::table('custom_events')
                            ->where('name', $eventName)
                            ->first();
            
            if ($customEvent) {
                return [
                    'event_name' => $eventName,
                    'schema' => json_decode($customEvent->schema, true),
                    'description' => $customEvent->description,
                    'category' => $customEvent->category
                ];
            }

            return ['error' => 'Event schema not found'];
        }

        return [
            'event_name' => $eventName,
            'schema' => $schemas[$eventName]
        ];
    }

    private function createCustomEvent(array $args): array
    {
        $existingEvent = DB::table('custom_events')
                          ->where('name', $args['name'])
                          ->first();

        if ($existingEvent) {
            return ['error' => 'Event already exists'];
        }

        $eventId = DB::table('custom_events')->insertGetId([
            'name' => $args['name'],
            'category' => $args['category'],
            'schema' => json_encode($args['schema']),
            'description' => $args['description'],
            'company_id' => auth()->user()?->company_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'event_id' => $eventId,
            'name' => $args['name'],
            'category' => $args['category'],
            'created' => true
        ];
    }

    private function createLaravelEvent(string $eventName, array $payload)
    {
        // Map event names to Laravel event classes
        return match ($eventName) {
            'appointment.created' => new AppointmentCreated($payload['appointment_id']),
            'appointment.updated' => new AppointmentUpdated($payload['appointment_id']),
            'appointment.cancelled' => new AppointmentCancelled($payload['appointment_id']),
            'appointment.rescheduled' => new AppointmentRescheduled($payload['appointment_id']),
            'call.created' => new CallCreated($payload['call_id']),
            'call.updated' => new CallUpdated($payload['call_id']),
            'call.completed' => new CallCompleted($payload['call_id']),
            'call.failed' => new CallFailed($payload['call_id']),
            'customer.created' => new CustomerCreated($payload['customer_id']),
            'customer.merged' => new CustomerMerged($payload['from_id'], $payload['to_id']),
            default => null
        };
    }

    private function triggerWebhooks(string $eventName, array $payload, ?int $companyId): void
    {
        $subscriptions = DB::table('event_subscriptions')
                          ->where('active', true)
                          ->where(function ($query) use ($companyId) {
                              $query->whereNull('company_id')
                                    ->orWhere('company_id', $companyId);
                          })
                          ->get();

        foreach ($subscriptions as $subscription) {
            $eventNames = json_decode($subscription->event_names, true);
            
            if (in_array($eventName, $eventNames) || in_array('*', $eventNames)) {
                // Queue webhook call
                dispatch(new \App\Jobs\CallWebhook(
                    $subscription->webhook_url,
                    [
                        'event' => $eventName,
                        'payload' => $payload,
                        'timestamp' => now()->toIso8601String()
                    ]
                ));
            }
        }
    }
}