<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            // Return empty response for optional endpoints
            return response()->json([
                'notifications' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                    'last_page' => 1
                ],
                'unread_count' => 0,
                'category_counts' => []
            ]);
        }

        $query = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id);

        // Apply filters
        if ($request->has('unread') && $request->unread) {
            $query->unread();
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Remove expired notifications
        $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });

        // Get notifications
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get unread count
        $unreadCount = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->unread()
            ->count();

        // Get category counts
        $categoryCounts = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->unread()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'category_counts' => $categoryCounts
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 200);
        }

        $notification = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'notification' => $notification
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->unread();

        // Filter by category if provided
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $count = $query->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    public function delete($id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notification = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function deleteAll(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id);

        // Only delete read notifications by default
        if (!$request->has('include_unread') || !$request->include_unread) {
            $query->read();
        }

        // Filter by category if provided
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $count = $query->delete();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    public function getPreferences()
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $preferences = $user->notification_preferences ?? [
            'email' => [
                'appointments' => true,
                'calls' => true,
                'invoices' => true,
                'team' => true,
                'system' => true
            ],
            'push' => [
                'appointments' => true,
                'calls' => true,
                'invoices' => false,
                'team' => false,
                'system' => true
            ],
            'sound' => true,
            'desktop' => true
        ];

        return response()->json([
            'preferences' => $preferences
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'preferences' => 'required|array',
            'preferences.email' => 'nullable|array',
            'preferences.push' => 'nullable|array',
            'preferences.sound' => 'nullable|boolean',
            'preferences.desktop' => 'nullable|boolean'
        ]);

        $user->notification_preferences = $request->preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'preferences' => $user->notification_preferences
        ]);
    }

    // Test notification creation (for development)
    public function createTest(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Only allow in development
        if (app()->environment('production')) {
            return response()->json(['error' => 'Not available in production'], 403);
        }

        $types = [
            'appointment.created' => [
                'title' => 'Neuer Termin erstellt',
                'message' => 'Ein neuer Termin wurde für morgen um 14:00 Uhr erstellt.',
                'category' => 'appointment',
                'priority' => 'high',
                'action_url' => '/appointments/123',
                'action_text' => 'Termin anzeigen'
            ],
            'call.received' => [
                'title' => 'Neuer Anruf eingegangen',
                'message' => 'Ein neuer Anruf von +49 123 456789 wurde aufgezeichnet.',
                'category' => 'call',
                'priority' => 'medium',
                'action_url' => '/calls/456',
                'action_text' => 'Anruf anhören'
            ],
            'invoice.created' => [
                'title' => 'Neue Rechnung verfügbar',
                'message' => 'Ihre Rechnung für Januar 2025 ist jetzt verfügbar.',
                'category' => 'invoice',
                'priority' => 'low',
                'action_url' => '/billing',
                'action_text' => 'Rechnung anzeigen'
            ],
            'system.alert' => [
                'title' => 'Systemwartung geplant',
                'message' => 'Eine Systemwartung ist für heute Nacht um 02:00 Uhr geplant.',
                'category' => 'system',
                'priority' => 'urgent',
                'action_url' => null,
                'action_text' => null
            ]
        ];

        $type = $request->type ?? array_rand($types);
        $data = $types[$type] ?? $types['appointment.created'];

        $notification = Notification::create([
            'type' => $type,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => $data,
            'category' => $data['category'],
            'priority' => $data['priority'],
            'action_url' => $data['action_url'],
            'action_text' => $data['action_text'],
            'expires_at' => $request->expires_in ? now()->addMinutes($request->expires_in) : null
        ]);

        // Broadcast notification via WebSocket if available
        // broadcast(new NotificationCreated($notification))->toOthers();

        return response()->json([
            'success' => true,
            'notification' => $notification
        ]);
    }
}