<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\PortalUser;
use Carbon\Carbon;

class AppointmentsApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Build query with optimized eager loading
        $query = Appointment::where('company_id', $company->id)
            ->with([
                'customer:id,name,phone,email',
                'staff:id,name',
                'service:id,name,duration,price',
                'branch:id,name'
            ]);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('starts_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('starts_at', '<=', $request->end_date);
        }

        // Branch filter
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Staff filter
        if ($request->has('staff_id') && $request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }

        // Service filter
        if ($request->has('service_id') && $request->service_id) {
            $query->where('service_id', $request->service_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'starts_at');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 25);
        $appointments = $query->paginate($perPage);

        // Transform the data
        $appointments->getCollection()->transform(function ($appointment) {
            return [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'customer' => $appointment->customer ? [
                    'id' => $appointment->customer->id,
                    'name' => $appointment->customer->name,
                    'phone' => $appointment->customer->phone,
                    'email' => $appointment->customer->email,
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
                'created_at' => $appointment->created_at->format('d.m.Y H:i'),
            ];
        });

        // Calculate stats with single query
        $today = Carbon::today();
        $thisWeekStart = Carbon::now()->startOfWeek();
        $thisWeekEnd = Carbon::now()->endOfWeek();

        $statsQuery = Appointment::where('company_id', $company->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(DATE(starts_at) = ?) as today', [$today])
            ->selectRaw('SUM(starts_at BETWEEN ? AND ?) as this_week', [$thisWeekStart, $thisWeekEnd])
            ->selectRaw('SUM(status = ?) as confirmed', ['confirmed'])
            ->selectRaw('SUM(status IN (?, ?)) as pending', ['scheduled', 'pending'])
            ->selectRaw('SUM(status = ?) as cancelled', ['cancelled'])
            ->first();

        $stats = [
            'total' => $statsQuery->total ?? 0,
            'today' => $statsQuery->today ?? 0,
            'this_week' => $statsQuery->this_week ?? 0,
            'confirmed' => $statsQuery->confirmed ?? 0,
            'pending' => $statsQuery->pending ?? 0,
            'cancelled' => $statsQuery->cancelled ?? 0,
        ];

        return response()->json([
            'appointments' => $appointments,
            'stats' => $stats,
        ]);
    }

    public function filters(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $company = $user->company;

        return response()->json([
            'branches' => $company->branches()->select('id', 'name')->get(),
            'staff' => $company->staff()->select('id', 'name')->get(),
            'services' => $company->services()->select('id', 'name', 'duration', 'price')->get(),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $appointment = Appointment::where('company_id', $user->company->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'status' => 'required|in:scheduled,pending,confirmed,completed,cancelled,no_show'
        ]);

        $appointment->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Status erfolgreich aktualisiert',
            'appointment' => $appointment
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $appointment = Appointment::where('company_id', $user->company_id)
            ->with(['customer', 'staff', 'service', 'branch'])
            ->findOrFail($id);

        return response()->json([
            'appointment' => [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'customer' => $appointment->customer,
                'staff' => $appointment->staff,
                'service' => $appointment->service,
                'branch' => $appointment->branch,
                'created_at' => $appointment->created_at->format('d.m.Y H:i:s'),
                'updated_at' => $appointment->updated_at->format('d.m.Y H:i:s'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'customer_phone' => 'required|string',
            'customer_name' => 'required|string',
            'service_id' => 'required|exists:services,id',
            'staff_id' => 'required|exists:staff,id',
            'branch_id' => 'required|exists:branches,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'notes' => 'nullable|string',
        ]);

        // Find or create customer
        $customer = Customer::firstOrCreate(
            [
                'phone' => $request->customer_phone,
                'company_id' => $user->company_id,
            ],
            [
                'name' => $request->customer_name,
                'email' => $request->customer_email ?? null,
            ]
        );

        // Create appointment
        $appointment = Appointment::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'customer_id' => $customer->id,
            'staff_id' => $request->staff_id,
            'service_id' => $request->service_id,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'status' => 'scheduled',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'appointment' => $appointment->load(['customer', 'staff', 'service', 'branch']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $appointment = Appointment::where('company_id', $user->company_id)->findOrFail($id);
        
        // Check if user has permission (for portal users)
        if ($user instanceof PortalUser && !$user->hasPermissionTo('appointments.edit_own')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'staff_id' => 'nullable|exists:staff,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($request->only(['service_id', 'staff_id', 'starts_at', 'ends_at', 'notes']));

        return response()->json([
            'success' => true,
            'appointment' => $appointment->load(['customer', 'staff', 'service', 'branch']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Only admins can delete appointments
        if (!canDeleteBusinessData()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Nur Administratoren können Termine löschen'
            ], 403);
        }

        // For admins viewing portal, we need to determine the correct company_id
        $companyId = $user->company_id;
        if (isAdminViewingPortal() && !$user->company_id) {
            // Admin user doesn't have company_id, get from session
            $adminImpersonation = session('admin_impersonation');
            if ($adminImpersonation && isset($adminImpersonation['company_id'])) {
                $companyId = $adminImpersonation['company_id'];
            } else {
                // Use current_company_id from app instance
                $companyId = app('current_company_id');
            }
        }

        $appointment = Appointment::where('company_id', $companyId)->findOrFail($id);
        
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Termin erfolgreich gelöscht',
        ]);
    }
}