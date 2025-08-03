<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReactCallController extends Controller
{
    /**
     * Display the React calls index
     */
    public function index(Request $request)
    {
        return view('portal.calls.react');
    }
    
    /**
     * Display the React call details
     */
    public function show($id)
    {
        // Just check if call exists and user has access
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(403, 'Unauthorized');
        }
        
        $call = Call::where('company_id', $user->company_id)
            ->where('id', $id)
            ->with(['customer', 'branch', 'staff'])
            ->firstOrFail();
        
        // Use unified layout for consistency
        return view('portal.calls.show-unified', compact('call'));
    }
    
    /**
     * API endpoint for calls list
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        
        // Build query
        $query = Call::where('company_id', $user->company_id)
            ->with(['customer', 'branch', 'appointment'])
            ->orderBy('created_at', 'desc');
            
        // Apply filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->has('date') && $request->date) {
            $query->whereDate('created_at', $request->date);
        }
        
        // Get results
        $calls = $query->paginate($perPage);
        
        // Transform data for React app
        $calls->getCollection()->transform(function ($call) {
            return [
                'id' => $call->id,
                'phone_number' => $call->phone_number,
                'status' => $call->status ?? 'new',
                'duration_sec' => $call->duration_sec ?? 0,
                'created_at' => $call->created_at->toIso8601String(),
                'updated_at' => $call->updated_at->toIso8601String(),
                'customer' => $call->customer ? [
                    'id' => $call->customer->id,
                    'name' => $call->customer->name,
                    'email' => $call->customer->email,
                ] : null,
                'branch' => $call->branch ? [
                    'id' => $call->branch->id,
                    'name' => $call->branch->name,
                ] : null,
                'appointment' => $call->appointment ? [
                    'id' => $call->appointment->id,
                    'date' => $call->appointment->date,
                    'time' => $call->appointment->time,
                    'status' => $call->appointment->status,
                ] : null,
                'assigned_to' => $call->assigned_to_id ? [
                    'id' => $call->assigned_to_id,
                    'name' => $call->assigned_to_name ?? 'User ' . $call->assigned_to_id,
                ] : null,
                'callback_scheduled_at' => null, // Column doesn't exist yet
                'notes' => $call->notes,
                'summary' => $call->summary,
                'action_items' => $call->action_items ?? [],
            ];
        });
        
        // Get stats
        $stats = [
            'total_today' => Call::where('company_id', $user->company_id)
                ->whereDate('created_at', today())
                ->count(),
            'new' => Call::where('company_id', $user->company_id)
                ->where('status', 'new')
                ->count(),
            'in_progress' => Call::where('company_id', $user->company_id)
                ->where('status', 'in_progress')
                ->count(),
            'action_required' => Call::where('company_id', $user->company_id)
                ->where('status', 'requires_action')
                ->count(),
            'callbacks_today' => 0, // Column doesn't exist yet
        ];
        
        return response()->json([
            'data' => $calls->items(),
            'stats' => $stats,
            'pagination' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
                'from' => $calls->firstItem(),
                'to' => $calls->lastItem(),
            ]
        ]);
    }
    
    /**
     * API endpoint for call details
     */
    public function apiShow($id)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $call = Call::where('company_id', $user->company_id)
            ->where('id', $id)
            ->with(['customer', 'branch', 'appointment', 'activities.user'])
            ->firstOrFail();
            
        return response()->json([
            'id' => $call->id,
            'phone_number' => $call->phone_number,
            'status' => $call->status ?? 'new',
            'duration_sec' => $call->duration_sec ?? 0,
            'created_at' => $call->created_at->toIso8601String(),
            'updated_at' => $call->updated_at->toIso8601String(),
            'customer' => $call->customer ? [
                'id' => $call->customer->id,
                'name' => $call->customer->name,
                'email' => $call->customer->email,
                'phone' => $call->customer->phone,
                'notes' => $call->customer->notes,
            ] : null,
            'branch' => $call->branch ? [
                'id' => $call->branch->id,
                'name' => $call->branch->name,
                'address' => $call->branch->address,
            ] : null,
            'appointment' => $call->appointment ? [
                'id' => $call->appointment->id,
                'date' => $call->appointment->date,
                'time' => $call->appointment->time,
                'status' => $call->appointment->status,
                'service' => $call->appointment->service,
                'duration' => $call->appointment->duration,
            ] : null,
            'assigned_to' => $call->assigned_to_id ? [
                'id' => $call->assigned_to_id,
                'name' => $call->assigned_to_name ?? 'User ' . $call->assigned_to_id,
            ] : null,
            'callback_scheduled_at' => null, // Column doesn't exist yet
            'notes' => $call->notes,
            'summary' => $call->summary,
            'action_items' => $call->action_items ?? [],
            'transcript' => $call->transcript,
            'recording_url' => $call->recording_url,
            'activities' => $call->activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at->toIso8601String(),
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                    ] : null,
                ];
            }),
        ]);
    }
}