<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleCallController extends Controller
{
    /**
     * API endpoint for calls list - Simple Direct Database Version
     */
    public function apiIndex(Request $request)
    {
        try {
            // Get company ID from authenticated user
            $user = Auth::guard('portal')->user();
            
            if (!$user || !$user->company_id) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'No valid session or company context'
                ], 401);
            }
        
        $companyId = $user->company_id;
        
        // Build query
        $query = Call::where('company_id', $companyId)
            ->with(['customer', 'branch', 'appointment']);
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // Order by newest first
        $query->orderBy('created_at', 'desc');
        
        // Paginate
        $perPage = $request->get('per_page', 20);
        $calls = $query->paginate($perPage);
        
        // Transform the data
        $calls->getCollection()->transform(function ($call) {
            return [
                'id' => $call->id,
                'phone_number' => $call->phone_number,
                'status' => $call->status,
                'duration_sec' => $call->duration_sec,
                'created_at' => $call->created_at->toIso8601String(),
                'customer' => $call->customer ? [
                    'id' => $call->customer->id,
                    'name' => $call->customer->name,
                    'email' => $call->customer->email
                ] : null,
                'branch' => $call->branch ? [
                    'id' => $call->branch->id,
                    'name' => $call->branch->name
                ] : null,
                'appointment' => $call->appointment ? [
                    'id' => $call->appointment->id,
                    'scheduled_at' => $call->appointment->scheduled_at
                ] : null,
                'assigned_to' => null, // TODO: Implement assignedTo relationship
                'callback_scheduled_at' => $call->callback_scheduled_at
            ];
        });
        
        return response()->json($calls);
        
        } catch (\Exception $e) {
            \Log::error('SimpleCallController::apiIndex error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Server Error',
                'message' => 'Failed to load calls',
                'debug' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * API endpoint for call details
     */
    public function apiShow(Request $request, $id)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $call = Call::where('company_id', $user->company_id)
            ->where('id', $id)
            ->with(['customer', 'branch', 'appointment', 'notes.user'])
            ->first();
        
        if (!$call) {
            return response()->json(['error' => 'Call not found'], 404);
        }
        
        return response()->json([
            'data' => [
                'id' => $call->id,
                'phone_number' => $call->phone_number,
                'status' => $call->status,
                'duration_sec' => $call->duration_sec,
                'transcript' => $call->transcript,
                'summary' => $call->summary,
                'created_at' => $call->created_at,
                'customer' => $call->customer,
                'branch' => $call->branch,
                'appointment' => $call->appointment,
                'notes' => $call->notes->map(function($note) {
                    return [
                        'id' => $note->id,
                        'content' => $note->content,
                        'created_at' => $note->created_at,
                        'user' => [
                            'id' => $note->user->id,
                            'name' => $note->user->name
                        ]
                    ];
                })
            ]
        ]);
    }
}