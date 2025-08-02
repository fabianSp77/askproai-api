<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use App\Events\CallReceived;
use App\Events\CallStatusUpdated;
use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\CustomerUpdated;
use App\Events\DashboardStatsUpdated;
use App\Events\NotificationCreated;
use App\Events\TeamMemberStatusChanged;

/**
 * MCP Server for WebSocket and Real-time Communication
 * Handles broadcasting, presence channels, and real-time updates
 */
class WebSocketMCPServer extends BaseMCPServer
{
    /**
     * Get the server name
     */
    public function getName(): string
    {
        return 'websocket-mcp-server';
    }
    
    /**
     * Get the server description
     */
    public function getDescription(): string
    {
        return 'Handles WebSocket connections, broadcasting, and real-time updates';
    }
    
    /**
     * Get available tools
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'broadcast',
                'description' => 'Broadcast an event to specific channels',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'event' => ['type' => 'string', 'required' => true],
                        'channel' => ['type' => 'string', 'required' => true],
                        'data' => ['type' => 'object', 'required' => true],
                        'to_user' => ['type' => 'integer', 'description' => 'Broadcast to specific user only'],
                        'except_user' => ['type' => 'integer', 'description' => 'Broadcast to all except this user']
                    ],
                    'required' => ['event', 'channel', 'data']
                ]
            ],
            [
                'name' => 'broadcastCallUpdate',
                'description' => 'Broadcast a call update to relevant channels',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'integer', 'required' => true],
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'branch_id' => ['type' => 'integer'],
                        'status' => ['type' => 'string'],
                        'data' => ['type' => 'object', 'required' => true]
                    ],
                    'required' => ['call_id', 'company_id', 'data']
                ]
            ],
            [
                'name' => 'broadcastAppointmentUpdate',
                'description' => 'Broadcast an appointment update',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'required' => true],
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'branch_id' => ['type' => 'integer'],
                        'staff_id' => ['type' => 'integer'],
                        'event_type' => ['type' => 'string', 'enum' => ['created', 'updated', 'cancelled', 'completed']],
                        'data' => ['type' => 'object', 'required' => true]
                    ],
                    'required' => ['appointment_id', 'company_id', 'data']
                ]
            ],
            [
                'name' => 'broadcastDashboardUpdate',
                'description' => 'Broadcast dashboard statistics update',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'branch_id' => ['type' => 'integer'],
                        'stats' => ['type' => 'object', 'required' => true]
                    ],
                    'required' => ['company_id', 'stats']
                ]
            ],
            [
                'name' => 'broadcastNotification',
                'description' => 'Send a real-time notification to users',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'type' => ['type' => 'string', 'required' => true],
                        'title' => ['type' => 'string', 'required' => true],
                        'message' => ['type' => 'string', 'required' => true],
                        'action_url' => ['type' => 'string'],
                        'icon' => ['type' => 'string']
                    ],
                    'required' => ['company_id', 'type', 'title', 'message']
                ]
            ],
            [
                'name' => 'getPresenceChannel',
                'description' => 'Get users currently in a presence channel',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'channel' => ['type' => 'string', 'required' => true]
                    ],
                    'required' => ['channel']
                ]
            ],
            [
                'name' => 'joinPresenceChannel',
                'description' => 'Join a user to a presence channel',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'channel' => ['type' => 'string', 'required' => true],
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'user_info' => ['type' => 'object']
                    ],
                    'required' => ['channel', 'user_id']
                ]
            ],
            [
                'name' => 'leavePresenceChannel',
                'description' => 'Remove a user from a presence channel',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'channel' => ['type' => 'string', 'required' => true],
                        'user_id' => ['type' => 'integer', 'required' => true]
                    ],
                    'required' => ['channel', 'user_id']
                ]
            ],
            [
                'name' => 'getActiveConnections',
                'description' => 'Get count of active WebSocket connections',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer']
                    ]
                ]
            ],
            [
                'name' => 'whisper',
                'description' => 'Send a whisper (client-to-client) message',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'channel' => ['type' => 'string', 'required' => true],
                        'event' => ['type' => 'string', 'required' => true],
                        'data' => ['type' => 'object', 'required' => true]
                    ],
                    'required' => ['channel', 'event', 'data']
                ]
            ]
        ];
    }
    
    /**
     * Handle tool execution
     */
    public function handleTool(string $toolName, array $arguments): array
    {
        switch ($toolName) {
            case 'broadcast':
                return $this->broadcast($arguments);
            case 'broadcastCallUpdate':
                return $this->broadcastCallUpdate($arguments);
            case 'broadcastAppointmentUpdate':
                return $this->broadcastAppointmentUpdate($arguments);
            case 'broadcastDashboardUpdate':
                return $this->broadcastDashboardUpdate($arguments);
            case 'broadcastNotification':
                return $this->broadcastNotification($arguments);
            case 'getPresenceChannel':
                return $this->getPresenceChannel($arguments);
            case 'joinPresenceChannel':
                return $this->joinPresenceChannel($arguments);
            case 'leavePresenceChannel':
                return $this->leavePresenceChannel($arguments);
            case 'getActiveConnections':
                return $this->getActiveConnections($arguments);
            case 'whisper':
                return $this->whisper($arguments);
            default:
                return [
                    'success' => false,
                    'message' => "Unknown tool: {$toolName}"
                ];
        }
    }
    
    /**
     * Generic broadcast method
     */
    protected function broadcast(array $params): array
    {
        try {
            $channel = $params['channel'];
            $event = $params['event'];
            $data = $params['data'];
            
            // Use Redis for broadcasting
            $payload = [
                'event' => $event,
                'data' => $data,
                'socket' => $params['except_user'] ?? null
            ];
            
            Redis::publish($channel, json_encode($payload));
            
            Log::info('WebSocketMCP: Broadcasted event', [
                'channel' => $channel,
                'event' => $event
            ]);
            
            return [
                'success' => true,
                'message' => 'Event broadcasted successfully',
                'channel' => $channel,
                'event' => $event
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to broadcast', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to broadcast: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Broadcast call updates
     */
    protected function broadcastCallUpdate(array $params): array
    {
        try {
            $event = new CallStatusUpdated(
                $params['call_id'],
                $params['status'] ?? 'updated',
                $params['data']
            );
            
            // Broadcast to company channel
            broadcast($event)->toOthers();
            
            // Also broadcast to branch channel if specified
            if (isset($params['branch_id'])) {
                $this->broadcast([
                    'channel' => "branch.{$params['branch_id']}",
                    'event' => 'CallUpdated',
                    'data' => $params['data']
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Call update broadcasted',
                'call_id' => $params['call_id']
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to broadcast call update', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to broadcast call update: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Broadcast appointment updates
     */
    protected function broadcastAppointmentUpdate(array $params): array
    {
        try {
            $eventClass = match($params['event_type'] ?? 'updated') {
                'created' => AppointmentCreated::class,
                'updated' => AppointmentUpdated::class,
                default => AppointmentUpdated::class
            };
            
            $event = new $eventClass($params['appointment_id'], $params['data']);
            broadcast($event)->toOthers();
            
            // Broadcast to specific channels
            $channels = [
                "company.{$params['company_id']}",
            ];
            
            if (isset($params['branch_id'])) {
                $channels[] = "branch.{$params['branch_id']}";
            }
            
            if (isset($params['staff_id'])) {
                $channels[] = "staff.{$params['staff_id']}";
            }
            
            foreach ($channels as $channel) {
                $this->broadcast([
                    'channel' => $channel,
                    'event' => 'AppointmentUpdated',
                    'data' => $params['data']
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Appointment update broadcasted',
                'appointment_id' => $params['appointment_id'],
                'channels' => $channels
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to broadcast appointment update', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to broadcast appointment update: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Broadcast dashboard statistics update
     */
    protected function broadcastDashboardUpdate(array $params): array
    {
        try {
            $event = new DashboardStatsUpdated(
                $params['company_id'],
                $params['stats'],
                $params['branch_id'] ?? null
            );
            
            broadcast($event)->toOthers();
            
            return [
                'success' => true,
                'message' => 'Dashboard update broadcasted',
                'company_id' => $params['company_id']
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to broadcast dashboard update', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to broadcast dashboard update: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Broadcast notification to users
     */
    protected function broadcastNotification(array $params): array
    {
        try {
            $notification = [
                'id' => uniqid('notif_'),
                'type' => $params['type'],
                'title' => $params['title'],
                'message' => $params['message'],
                'action_url' => $params['action_url'] ?? null,
                'icon' => $params['icon'] ?? 'bell',
                'created_at' => now()->toIso8601String()
            ];
            
            // Broadcast to company channel
            $this->broadcast([
                'channel' => "company.{$params['company_id']}.notifications",
                'event' => 'NotificationReceived',
                'data' => $notification
            ]);
            
            // Broadcast to specific user channels if provided
            if (!empty($params['user_ids'])) {
                foreach ($params['user_ids'] as $userId) {
                    $this->broadcast([
                        'channel' => "user.{$userId}.notifications",
                        'event' => 'NotificationReceived',
                        'data' => $notification
                    ]);
                }
            }
            
            // Store notification in cache for later retrieval
            $cacheKey = "notifications:company:{$params['company_id']}";
            $notifications = Cache::get($cacheKey, []);
            array_unshift($notifications, $notification);
            $notifications = array_slice($notifications, 0, 100); // Keep last 100
            Cache::put($cacheKey, $notifications, now()->addDays(7));
            
            return [
                'success' => true,
                'message' => 'Notification broadcasted',
                'notification_id' => $notification['id']
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to broadcast notification', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to broadcast notification: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get users in a presence channel
     */
    protected function getPresenceChannel(array $params): array
    {
        try {
            $channel = $params['channel'];
            $cacheKey = "presence:{$channel}";
            
            $users = Cache::get($cacheKey, []);
            
            return [
                'success' => true,
                'channel' => $channel,
                'users' => array_values($users),
                'count' => count($users)
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to get presence channel', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get presence channel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Join a presence channel
     */
    protected function joinPresenceChannel(array $params): array
    {
        try {
            $channel = $params['channel'];
            $userId = $params['user_id'];
            $userInfo = $params['user_info'] ?? ['id' => $userId];
            
            $cacheKey = "presence:{$channel}";
            $users = Cache::get($cacheKey, []);
            
            // Add or update user
            $users[$userId] = array_merge($userInfo, [
                'id' => $userId,
                'joined_at' => now()->toIso8601String()
            ]);
            
            Cache::put($cacheKey, $users, now()->addHours(24));
            
            // Broadcast join event
            $this->broadcast([
                'channel' => $channel,
                'event' => 'presence:joining',
                'data' => ['user' => $users[$userId]]
            ]);
            
            return [
                'success' => true,
                'message' => 'Joined presence channel',
                'channel' => $channel,
                'user_id' => $userId
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to join presence channel', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to join presence channel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Leave a presence channel
     */
    protected function leavePresenceChannel(array $params): array
    {
        try {
            $channel = $params['channel'];
            $userId = $params['user_id'];
            
            $cacheKey = "presence:{$channel}";
            $users = Cache::get($cacheKey, []);
            
            if (isset($users[$userId])) {
                $leavingUser = $users[$userId];
                unset($users[$userId]);
                
                Cache::put($cacheKey, $users, now()->addHours(24));
                
                // Broadcast leave event
                $this->broadcast([
                    'channel' => $channel,
                    'event' => 'presence:leaving',
                    'data' => ['user' => $leavingUser]
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Left presence channel',
                'channel' => $channel,
                'user_id' => $userId
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to leave presence channel', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to leave presence channel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get active WebSocket connections count
     */
    protected function getActiveConnections(array $params): array
    {
        try {
            $companyId = $params['company_id'] ?? null;
            
            // Get all presence channels
            $pattern = $companyId ? "presence:company.{$companyId}*" : "presence:*";
            $keys = Cache::getRedis()->keys($pattern);
            
            $totalConnections = 0;
            $channelStats = [];
            
            foreach ($keys as $key) {
                $users = Cache::get(str_replace(config('cache.prefix') . ':', '', $key), []);
                $count = count($users);
                $totalConnections += $count;
                
                $channel = str_replace('presence:', '', $key);
                $channelStats[$channel] = $count;
            }
            
            return [
                'success' => true,
                'total_connections' => $totalConnections,
                'channels' => $channelStats,
                'timestamp' => now()->toIso8601String()
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to get active connections', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get active connections: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send a whisper message (client-to-client)
     */
    protected function whisper(array $params): array
    {
        try {
            $channel = $params['channel'];
            $event = "client-{$params['event']}";
            $data = $params['data'];
            
            // Whispers are client events, prefix with "client-"
            $this->broadcast([
                'channel' => $channel,
                'event' => $event,
                'data' => $data
            ]);
            
            return [
                'success' => true,
                'message' => 'Whisper sent',
                'channel' => $channel,
                'event' => $event
            ];
        } catch (\Exception $e) {
            Log::error('WebSocketMCP: Failed to send whisper', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send whisper: ' . $e->getMessage()
            ];
        }
    }
}