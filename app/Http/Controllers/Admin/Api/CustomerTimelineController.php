<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\CustomerNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerTimelineController extends Controller
{
    /**
     * Get complete timeline for a customer
     */
    public function index(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);
        $types = $request->get('types', ['call', 'appointment', 'note', 'email']);

        $timeline = collect();

        // Get calls
        if (in_array('call', $types)) {
            $calls = Call::where('customer_id', $customerId)
                ->select('id', 'start_timestamp as timestamp', 'from_number', 'to_number', 
                        'duration_sec', 'status', 'sentiment', 'summary', 'agent_name')
                ->get()
                ->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'type' => 'call',
                        'timestamp' => $call->timestamp,
                        'title' => 'Anruf von ' . $call->from_number,
                        'description' => $call->summary ?: 'Kein Zusammenfassung verfügbar',
                        'duration' => $call->duration_sec . ' Sekunden',
                        'sentiment' => $call->sentiment,
                        'status' => $call->status,
                        'icon' => 'phone',
                        'color' => $call->sentiment === 'positive' ? 'success' : 
                                 ($call->sentiment === 'negative' ? 'danger' : 'info'),
                        'details' => [
                            'agent' => $call->agent_name,
                            'from' => $call->from_number,
                            'to' => $call->to_number
                        ]
                    ];
                });
            $timeline = $timeline->merge($calls);
        }

        // Get appointments
        if (in_array('appointment', $types)) {
            $appointments = Appointment::where('customer_id', $customerId)
                ->with(['service', 'staff', 'branch'])
                ->select('id', 'starts_at as timestamp', 'ends_at', 'status', 
                        'service_id', 'staff_id', 'branch_id', 'notes', 'source')
                ->get()
                ->map(function ($appointment) {
                    $statusColors = [
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'no_show' => 'secondary'
                    ];
                    
                    $statusLabels = [
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen'
                    ];

                    return [
                        'id' => $appointment->id,
                        'type' => 'appointment',
                        'timestamp' => $appointment->timestamp,
                        'title' => 'Termin: ' . ($appointment->service->name ?? 'Unbekannt'),
                        'description' => sprintf(
                            '%s bei %s in %s',
                            Carbon::parse($appointment->timestamp)->format('d.m.Y H:i'),
                            $appointment->staff->name ?? 'Unbekannt',
                            $appointment->branch->name ?? 'Unbekannt'
                        ),
                        'status' => $statusLabels[$appointment->status] ?? $appointment->status,
                        'icon' => 'calendar',
                        'color' => $statusColors[$appointment->status] ?? 'secondary',
                        'details' => [
                            'service' => $appointment->service->name ?? null,
                            'staff' => $appointment->staff->name ?? null,
                            'branch' => $appointment->branch->name ?? null,
                            'notes' => $appointment->notes,
                            'source' => $appointment->source
                        ]
                    ];
                });
            $timeline = $timeline->merge($appointments);
        }

        // Get notes
        if (in_array('note', $types)) {
            $notes = DB::table('customer_notes')
                ->where('customer_id', $customerId)
                ->select('id', 'created_at as timestamp', 'content', 'category', 
                        'created_by', 'is_important')
                ->get()
                ->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'type' => 'note',
                        'timestamp' => $note->timestamp,
                        'title' => $note->category ? "Notiz ({$note->category})" : 'Notiz',
                        'description' => $note->content,
                        'icon' => 'document-text',
                        'color' => $note->is_important ? 'warning' : 'secondary',
                        'details' => [
                            'category' => $note->category,
                            'created_by' => $note->created_by,
                            'is_important' => $note->is_important
                        ]
                    ];
                });
            $timeline = $timeline->merge($notes);
        }

        // Get emails
        if (in_array('email', $types)) {
            $emails = DB::table('email_logs')
                ->where('customer_id', $customerId)
                ->select('id', 'sent_at as timestamp', 'subject', 'type', 'status')
                ->get()
                ->map(function ($email) {
                    return [
                        'id' => $email->id,
                        'type' => 'email',
                        'timestamp' => $email->timestamp,
                        'title' => 'E-Mail: ' . $email->subject,
                        'description' => $email->type,
                        'icon' => 'mail',
                        'color' => $email->status === 'sent' ? 'success' : 'danger',
                        'details' => [
                            'type' => $email->type,
                            'status' => $email->status
                        ]
                    ];
                });
            $timeline = $timeline->merge($emails);
        }

        // Sort by timestamp descending
        $timeline = $timeline->sortByDesc('timestamp')->values();

        // Apply pagination
        $total = $timeline->count();
        $timeline = $timeline->slice($offset, $limit)->values();

        return response()->json([
            'data' => $timeline,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'has_more' => ($offset + $limit) < $total
        ]);
    }

    /**
     * Add a note to customer
     */
    public function addNote(Request $request, $customerId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'category' => 'nullable|string|max:50',
            'is_important' => 'boolean'
        ]);

        $customer = Customer::findOrFail($customerId);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $note = DB::table('customer_notes')->insertGetId([
            'customer_id' => $customerId,
            'content' => $request->content,
            'category' => $request->category,
            'is_important' => $request->is_important ?? false,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'id' => $note,
            'message' => 'Notiz erfolgreich hinzugefügt'
        ], 201);
    }

    /**
     * Get customer activity statistics
     */
    public function statistics($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_calls' => Call::where('customer_id', $customerId)->count(),
            'total_appointments' => Appointment::where('customer_id', $customerId)->count(),
            'completed_appointments' => Appointment::where('customer_id', $customerId)
                ->where('status', 'completed')->count(),
            'no_shows' => Appointment::where('customer_id', $customerId)
                ->where('status', 'no_show')->count(),
            'total_spent' => 0, // TODO: Calculate from billing
            'last_contact' => null,
            'customer_since' => $customer->created_at->format('Y-m-d'),
            'lifetime_value' => 0 // TODO: Calculate LTV
        ];

        // Get last contact
        $lastCall = Call::where('customer_id', $customerId)
            ->orderBy('start_timestamp', 'desc')
            ->first();
        $lastAppointment = Appointment::where('customer_id', $customerId)
            ->orderBy('starts_at', 'desc')
            ->first();

        if ($lastCall && $lastAppointment) {
            $stats['last_contact'] = Carbon::parse($lastCall->start_timestamp)
                ->greaterThan(Carbon::parse($lastAppointment->starts_at)) 
                ? $lastCall->start_timestamp 
                : $lastAppointment->starts_at;
        } elseif ($lastCall) {
            $stats['last_contact'] = $lastCall->start_timestamp;
        } elseif ($lastAppointment) {
            $stats['last_contact'] = $lastAppointment->starts_at;
        }

        return response()->json($stats);
    }

    /**
     * Get customer appointments
     */
    public function appointments($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $appointments = Appointment::where('customer_id', $customerId)
            ->with(['service', 'staff', 'branch'])
            ->orderBy('starts_at', 'desc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'start_time' => $appointment->starts_at,
                    'end_time' => $appointment->ends_at,
                    'status' => $appointment->status,
                    'service' => $appointment->service,
                    'staff' => $appointment->staff,
                    'branch' => $appointment->branch,
                    'notes' => $appointment->notes,
                    'source' => $appointment->source,
                    'created_at' => $appointment->created_at
                ];
            });

        return response()->json(['data' => $appointments]);
    }

    /**
     * Get customer calls
     */
    public function calls($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $calls = Call::where('customer_id', $customerId)
            ->orderBy('start_timestamp', 'desc')
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'start_timestamp' => $call->start_timestamp,
                    'duration_sec' => $call->duration_sec,
                    'direction' => $call->direction ?? 'inbound',
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'status' => $call->status,
                    'summary' => $call->summary,
                    'sentiment' => $call->sentiment,
                    'agent_name' => $call->agent_name,
                    'created_at' => $call->created_at
                ];
            });

        return response()->json(['data' => $calls]);
    }

    /**
     * Get customer notes
     */
    public function notes($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notes = DB::table('customer_notes')
            ->leftJoin('users', 'customer_notes.created_by', '=', 'users.id')
            ->where('customer_notes.customer_id', $customerId)
            ->select(
                'customer_notes.*',
                'users.name as created_by_name'
            )
            ->orderBy('customer_notes.created_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'content' => $note->content,
                    'category' => $note->category,
                    'is_important' => (bool) $note->is_important,
                    'created_by' => [
                        'id' => $note->created_by,
                        'name' => $note->created_by_name
                    ],
                    'created_at' => $note->created_at
                ];
            });

        return response()->json(['data' => $notes]);
    }

    /**
     * Delete a note
     */
    public function deleteNote($noteId)
    {
        $note = DB::table('customer_notes')->where('id', $noteId)->first();
        
        if (!$note) {
            return response()->json(['error' => 'Note not found'], 404);
        }

        // Get customer to check permissions
        $customer = Customer::findOrFail($note->customer_id);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::table('customer_notes')->where('id', $noteId)->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }

    /**
     * Get customer documents
     */
    public function documents($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $customer->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // TODO: Implement document storage and retrieval
        // For now, return empty array as the feature is not yet implemented
        $documents = [];

        return response()->json(['data' => $documents]);
    }
}