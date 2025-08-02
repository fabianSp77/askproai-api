<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\PortalUser;

class CustomerController extends BaseAdminApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { $q->where("company_id", auth()->user()->company_id); }
            ])
            ->withCount(['appointments', 'calls']);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        // Date filter
        if ($request->has('created_from')) {
            $query->whereDate('created_at', '>=', $request->get('created_from'));
        }
        if ($request->has('created_to')) {
            $query->whereDate('created_at', '<=', $request->get('created_to'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $customers = $query->paginate($request->get('per_page', 20));

        // Transform for frontend
        $customers->getCollection()->transform(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'postal_code' => $customer->postal_code,
                'notes' => $customer->notes,
                'created_at' => $customer->created_at->format('d.m.Y H:i'),
                'appointments_count' => $customer->appointments_count,
                'calls_count' => $customer->calls_count,
                'company' => $customer->company ? [
                    'id' => $customer->company->id,
                    'name' => $customer->company->name
                ] : null,
                'tags' => $customer->tags ?? [],
                'is_vip' => $customer->is_vip ?? false,
                'portal_enabled' => false, // Portal user relationship not implemented
                'last_appointment' => $customer->appointments()
                    ->latest('starts_at')
                    ->first()?->starts_at?->format('d.m.Y H:i'),
                'no_show_count' => $customer->appointments()
                    ->where('status', 'no_show')
                    ->count(),
            ];
        });

        return response()->json($customers);
    }

    public function show($id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'appointments' => function($q) { 
                    $q->where("company_id", auth()->user()->company_id)
                      ->with(['staff', 'service'])
                      ->latest()
                      ->limit(10);
                },
                'calls' => function($q) { 
                    $q->where("company_id", auth()->user()->company_id)
                      ->latest()
                      ->limit(10);
                }
            ])
            ->withCount(['appointments', 'calls'])
            ->findOrFail($id);

        return response()->json($customer);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Check for duplicates
        $existingCustomer = Customer::where("company_id", auth()->user()->company_id)
            ->where('company_id', $validated['company_id'])
            ->where('phone', $validated['phone'])
            ->first();

        if ($existingCustomer) {
            return response()->json([
                'message' => 'Customer with this phone number already exists',
                'customer' => $existingCustomer
            ], 422);
        }

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy($id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);
        
        // Check if customer has appointments or calls
        if ($customer->appointments()->exists() || $customer->calls()->exists()) {
            return response()->json([
                'message' => 'Cannot delete customer with existing appointments or calls'
            ], 422);
        }

        $customer->delete();

        return response()->json(null, 204);
    }

    public function history($id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $history = [];

        // Get appointments
        $appointments = $customer->appointments()
            ->where("company_id", auth()->user()->company_id)
            ->with(['staff', 'service'])
            ->get()
            ->map(function ($appointment) {
                return [
                    'type' => 'appointment',
                    'date' => $appointment->starts_at,
                    'description' => "Termin: {$appointment->service->name} bei {$appointment->staff->name}",
                    'status' => $appointment->status,
                ];
            });

        // Get calls
        $calls = $customer->calls()
            ->where("company_id", auth()->user()->company_id)
            ->get()
            ->map(function ($call) {
                return [
                    'type' => 'call',
                    'date' => $call->created_at,
                    'description' => "Anruf ({$call->duration_seconds} Sekunden)",
                    'status' => $call->status,
                ];
            });

        // Merge and sort by date
        $history = collect()
            ->merge($appointments)
            ->merge($calls)
            ->sortByDesc('date')
            ->values();

        return response()->json($history);
    }

    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_id' => 'required|exists:customers,id',
            'duplicate_ids' => 'required|array',
            'duplicate_ids.*' => 'exists:customers,id',
        ]);

        $primary = Customer::where("company_id", auth()->user()->company_id)->findOrFail($validated['primary_id']);
        $duplicates = Customer::where("company_id", auth()->user()->company_id)->whereIn('id', $validated['duplicate_ids'])->get();

        foreach ($duplicates as $duplicate) {
            // Transfer appointments
            $duplicate->appointments()->update(['customer_id' => $primary->id]);
            
            // Transfer calls
            $duplicate->calls()->update(['customer_id' => $primary->id]);
            
            // Merge notes
            if ($duplicate->notes) {
                $primary->notes = $primary->notes . "\n\n--- Merged from {$duplicate->name} ---\n" . $duplicate->notes;
                $primary->save();
            }
            
            // Delete duplicate
            $duplicate->delete();
        }

        return response()->json([
            'message' => 'Customers merged successfully',
            'customer' => $primary->fresh()
        ]);
    }

    public function quickBooking(Request $request, $id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_id' => 'required|exists:services,id',
            'staff_id' => 'required|exists:staff,id',
            'starts_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Calculate end time based on service duration
        $service = \App\Models\Service::find($validated['service_id']);
        $endTime = \Carbon\Carbon::parse($validated['starts_at'])
            ->addMinutes($service->duration);

        $appointment = \App\Models\Appointment::create([
            'company_id' => $customer->company_id,
            'branch_id' => $validated['branch_id'],
            'customer_id' => $customer->id,
            'service_id' => $validated['service_id'],
            'staff_id' => $validated['staff_id'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $endTime,
            'status' => 'scheduled',
            'price' => $service->price,
            'notes' => $validated['notes'],
        ]);

        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment
        ]);
    }

    public function timeline($id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $timeline = [];

        // Customer created
        $timeline[] = [
            'type' => 'customer_created',
            'date' => $customer->created_at,
            'title' => 'Kunde erstellt',
            'description' => 'Kundenprofil wurde angelegt',
            'icon' => 'user-plus',
            'color' => 'blue',
        ];

        // Appointments
        $appointments = $customer->appointments()
            ->where("company_id", auth()->user()->company_id)
            ->with(['staff', 'service'])
            ->get();

        foreach ($appointments as $appointment) {
            $timeline[] = [
                'type' => 'appointment',
                'date' => $appointment->starts_at,
                'title' => $appointment->service->name,
                'description' => "Termin bei {$appointment->staff->name} - Status: {$appointment->status}",
                'icon' => 'calendar',
                'color' => $appointment->status === 'completed' ? 'green' : 
                          ($appointment->status === 'cancelled' ? 'red' : 'yellow'),
                'data' => [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status,
                ],
            ];
        }

        // Calls
        $calls = $customer->calls()
            ->where("company_id", auth()->user()->company_id)
            ->get();

        foreach ($calls as $call) {
            $timeline[] = [
                'type' => 'call',
                'date' => $call->created_at,
                'title' => 'Anruf',
                'description' => "Dauer: {$call->duration_seconds}s - Stimmung: {$call->sentiment}",
                'icon' => 'phone',
                'color' => 'blue',
                'data' => [
                    'call_id' => $call->id,
                    'duration' => $call->duration_seconds,
                    'sentiment' => $call->sentiment,
                ],
            ];
        }

        // Sort by date descending
        $timeline = collect($timeline)
            ->sortByDesc('date')
            ->values()
            ->toArray();

        return response()->json($timeline);
    }

    public function enablePortal(Request $request, $id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        // Check if portal user already exists
        $existingPortalUser = PortalUser::where('customer_id', $customer->id)->first();
        if ($existingPortalUser) {
            return response()->json([
                'message' => 'Portal access already enabled'
            ], 422);
        }

        // Generate temporary password
        $tempPassword = \Str::random(8);

        DB::beginTransaction();
        try {
            // Create portal user
            $portalUser = PortalUser::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'password' => bcrypt($tempPassword),
                'is_active' => true,
                'role' => 'customer',
            ]);

            // Send welcome email with credentials
            \App\Jobs\SendPortalWelcomeEmailJob::dispatch($customer, $tempPassword);

            DB::commit();

            return response()->json([
                'message' => 'Portal access enabled',
                'portal_user' => $portalUser,
                'temp_password' => $tempPassword
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to enable portal access'], 500);
        }
    }

    public function disablePortal($id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $portalUser = PortalUser::where('customer_id', $customer->id)->first();
        if (!$portalUser) {
            return response()->json([
                'message' => 'Portal access not enabled'
            ], 422);
        }

        $portalUser->update(['is_active' => false]);

        return response()->json(['message' => 'Portal access disabled']);
    }

    public function sendEmail(Request $request, $id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'template' => 'nullable|string',
        ]);

        try {
            \App\Jobs\SendCustomerEmailJob::dispatch($customer, $validated);

            return response()->json(['message' => 'Email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send email'], 500);
        }
    }

    public function sendSms(Request $request, $id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|max:160',
        ]);

        try {
            \App\Jobs\SendSmsJob::dispatch($customer->phone, $validated['message']);

            return response()->json(['message' => 'SMS sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send SMS'], 500);
        }
    }

    public function updateTags(Request $request, $id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:50',
        ]);

        $customer->update(['tags' => $validated['tags']]);

        return response()->json([
            'message' => 'Tags updated successfully',
            'tags' => $customer->tags
        ]);
    }

    public function toggleVip($id): JsonResponse
    {
        $customer = Customer::where("company_id", auth()->user()->company_id)->findOrFail($id);
        
        $customer->update(['is_vip' => !$customer->is_vip]);

        return response()->json([
            'message' => $customer->is_vip ? 'Customer marked as VIP' : 'VIP status removed',
            'is_vip' => $customer->is_vip
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $baseQuery = Customer::where("company_id", auth()->user()->company_id);
        
        if ($companyId) {
            $baseQuery->where('company_id', $companyId);
        }

        $stats = [
            'total_customers' => $baseQuery->count(),
            'new_customers' => $baseQuery->clone()
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'with_appointments' => $baseQuery->clone()
                ->has('appointments')
                ->count(),
            'with_portal_access' => 0, // Portal users relationship not properly configured
            'vip_customers' => $baseQuery->clone()
                ->where('is_vip', true)
                ->count(),
            'by_city' => [], // City column not available in customers table
            'acquisition_trend' => $baseQuery->clone()
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($stats);
    }

    public function tags(): JsonResponse
    {
        // Get all unique tags from customers
        // Note: Assuming tags are stored as JSON array in the tags column
        $allTags = [];
        
        $customers = Customer::where("company_id", auth()->user()->company_id)
            ->whereNotNull('tags')
            ->pluck('tags');
            
        foreach ($customers as $tags) {
            if (is_string($tags)) {
                $decodedTags = json_decode($tags, true);
                if (is_array($decodedTags)) {
                    $allTags = array_merge($allTags, $decodedTags);
                }
            } elseif (is_array($tags)) {
                $allTags = array_merge($allTags, $tags);
            }
        }
        
        $uniqueTags = array_values(array_unique($allTags));
        sort($uniqueTags);

        return response()->json($uniqueTags);
    }
}