<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReactAppointmentController extends Controller
{
    /**
     * Display the React appointments index
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $appointments = Appointment::where('company_id', $user->company_id)
            ->with(['customer', 'staff', 'service', 'branch'])
            ->orderBy('starts_at', 'desc')
            ->paginate(20);
            
        // Use unified layout for consistency
        return view('portal.appointments.index-unified', compact('appointments'));
    }
    
    /**
     * Display the React appointment creation form
     */
    public function create(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        // Get data for the form
        $customers = Customer::where('company_id', $user->company_id)->get();
        $staff = Staff::where('company_id', $user->company_id)->get();
        $services = Service::where('company_id', $user->company_id)->get();
        $branches = Branch::where('company_id', $user->company_id)->get();
        
        // Use unified layout for consistency
        return view('portal.appointments.create-unified', compact('customers', 'staff', 'services', 'branches'));
    }
    
    /**
     * API endpoint for appointments list
     */
    public function apiIndex(Request $request)
    {
        try {
            $user = Auth::guard('portal')->user();
            
            if (!$user || !$user->company_id) {
                Log::error('ReactAppointmentController: No authenticated user or company_id', [
                    'user' => $user ? $user->id : 'null',
                    'company_id' => $user ? $user->company_id : 'null'
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $perPage = $request->get('per_page', 25);
            
            // Build query
            $query = Appointment::where('company_id', $user->company_id)
                ->with(['customer', 'staff', 'service', 'branch'])
                ->orderBy('date', 'desc')
                ->orderBy('time', 'desc');
                
            // Apply filters
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('customer', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            }
            
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            if ($request->has('branch_id') && $request->branch_id !== 'all') {
                $query->where('branch_id', $request->branch_id);
            }
            
            if ($request->has('staff_id') && $request->staff_id !== 'all') {
                $query->where('staff_id', $request->staff_id);
            }
            
            // Support both date_from/date_to and start_date/end_date
            $dateFrom = $request->get('date_from') ?? $request->get('start_date');
            $dateTo = $request->get('date_to') ?? $request->get('end_date');
            
            if ($dateFrom) {
                $query->where('date', '>=', $dateFrom);
            }
            
            if ($dateTo) {
                $query->where('date', '<=', $dateTo);
            }
            
            // Get results
            $appointments = $query->paginate($perPage);
            
            // Transform data for React app
            $appointments->getCollection()->transform(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'date' => $appointment->date,
                    'time' => $appointment->time,
                    'duration' => $appointment->duration ?? 60,
                    'status' => $appointment->status ?? 'scheduled',
                    'notes' => $appointment->notes,
                    'created_at' => $appointment->created_at ? $appointment->created_at->toIso8601String() : null,
                    'updated_at' => $appointment->updated_at ? $appointment->updated_at->toIso8601String() : null,
                    'customer' => $appointment->customer ? [
                        'id' => $appointment->customer->id,
                        'name' => $appointment->customer->name,
                        'email' => $appointment->customer->email,
                        'phone' => $appointment->customer->phone,
                    ] : null,
                    'staff' => $appointment->staff ? [
                        'id' => $appointment->staff->id,
                        'name' => $appointment->staff->name,
                    ] : null,
                    'service' => $appointment->service ? [
                        'id' => $appointment->service->id,
                        'name' => $appointment->service->name,
                        'duration' => $appointment->service->duration,
                        'price' => $appointment->service->price,
                    ] : null,
                    'branch' => $appointment->branch ? [
                        'id' => $appointment->branch->id,
                        'name' => $appointment->branch->name,
                    ] : null,
                ];
            });
            
            return response()->json([
                'appointments' => [
                    'data' => $appointments->items(),
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                ],
                'stats' => [
                    'total' => $appointments->total(),
                    'scheduled' => Appointment::where('company_id', $user->company_id)->where('status', 'scheduled')->count(),
                    'confirmed' => Appointment::where('company_id', $user->company_id)->where('status', 'confirmed')->count(),
                    'completed' => Appointment::where('company_id', $user->company_id)->where('status', 'completed')->count(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('ReactAppointmentController::apiIndex error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Ein Fehler ist aufgetreten beim Laden der Termine',
                'message' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get available time slots
     */
    public function availableSlots(Request $request)
    {
        try {
            $user = Auth::guard('portal')->user();
            
            if (!$user || !$user->company_id) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // For now, return mock data
            $slots = [];
            $date = $request->get('date', now()->format('Y-m-d'));
            $staffId = $request->get('staff_id');
            
            // Generate time slots from 8:00 to 18:00
            for ($hour = 8; $hour < 18; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $time = sprintf('%02d:%02d', $hour, $minute);
                    $slots[] = [
                        'time' => $time,
                        'available' => true, // In real implementation, check against existing appointments
                    ];
                }
            }
            
            return response()->json([
                'slots' => $slots,
                'date' => $date,
            ]);
            
        } catch (\Exception $e) {
            Log::error('ReactAppointmentController::availableSlots error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Fehler beim Laden der verfügbaren Zeitslots'
            ], 500);
        }
    }
    
    /**
     * Get filter options
     */
    public function getFilters(Request $request)
    {
        try {
            $user = Auth::guard('portal')->user();
            
            if (!$user || !$user->company_id) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // Get branches
            $branches = Branch::where('company_id', $user->company_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
                
            // Get staff
            $staff = Staff::where('company_id', $user->company_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
                
            // Get services
            $services = Service::where('company_id', $user->company_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
                
            return response()->json([
                'branches' => $branches,
                'staff' => $staff,
                'services' => $services,
                'statuses' => [
                    ['value' => 'scheduled', 'label' => 'Geplant'],
                    ['value' => 'confirmed', 'label' => 'Bestätigt'],
                    ['value' => 'completed', 'label' => 'Abgeschlossen'],
                    ['value' => 'cancelled', 'label' => 'Storniert'],
                    ['value' => 'no_show', 'label' => 'Nicht erschienen'],
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('ReactAppointmentController::getFilters error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Fehler beim Laden der Filter-Optionen'
            ], 500);
        }
    }
    
    /**
     * Store new appointment
     */
    public function apiStore(Request $request)
    {
        try {
            $user = Auth::guard('portal')->user();
            
            if (!$user || !$user->company_id) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'staff_id' => 'required|exists:staff,id',
                'service_id' => 'required|exists:services,id',
                'branch_id' => 'required|exists:branches,id',
                'date' => 'required|date',
                'time' => 'required|date_format:H:i',
                'duration' => 'nullable|integer|min:15|max:480',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            $validated['company_id'] = $user->company_id;
            $validated['status'] = 'scheduled';
            $validated['created_by'] = $user->id;
            
            $appointment = Appointment::create($validated);
            $appointment->load(['customer', 'staff', 'service', 'branch']);
            
            return response()->json([
                'success' => true,
                'appointment' => [
                    'id' => $appointment->id,
                    'date' => $appointment->date,
                    'time' => $appointment->time,
                    'duration' => $appointment->duration ?? 60,
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'customer' => $appointment->customer ? [
                        'id' => $appointment->customer->id,
                        'name' => $appointment->customer->name,
                        'email' => $appointment->customer->email,
                        'phone' => $appointment->customer->phone,
                    ] : null,
                    'staff' => $appointment->staff ? [
                        'id' => $appointment->staff->id,
                        'name' => $appointment->staff->name,
                    ] : null,
                    'service' => $appointment->service ? [
                        'id' => $appointment->service->id,
                        'name' => $appointment->service->name,
                        'duration' => $appointment->service->duration,
                        'price' => $appointment->service->price,
                    ] : null,
                    'branch' => $appointment->branch ? [
                        'id' => $appointment->branch->id,
                        'name' => $appointment->branch->name,
                    ] : null,
                ],
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validierungsfehler',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('ReactAppointmentController::apiStore error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Fehler beim Erstellen des Termins'
            ], 500);
        }
    }
    
    /**
     * Update appointment status
     */
    public function updateStatus(Request $request, $appointmentId)
    {
        try {
            $user = Auth::guard('portal')->user();
            
            if (!$user || !$user->company_id) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $appointment = Appointment::where('company_id', $user->company_id)
                ->findOrFail($appointmentId);
                
            $validated = $request->validate([
                'status' => 'required|in:scheduled,confirmed,completed,cancelled,no_show'
            ]);
            
            $appointment->status = $validated['status'];
            $appointment->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Status erfolgreich aktualisiert',
                'appointment' => [
                    'id' => $appointment->id,
                    'status' => $appointment->status,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('ReactAppointmentController::updateStatus error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Fehler beim Aktualisieren des Status'
            ], 500);
        }
    }
}