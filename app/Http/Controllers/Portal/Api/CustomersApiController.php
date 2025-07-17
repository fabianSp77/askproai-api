<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomersApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        // Build query with counts
        $query = Customer::where('company_id', $companyId)
            ->withCount(['appointments', 'calls'])
            ->withSum(['appointments' => function($q) {
                $q->where('status', 'completed');
            }], 'price');

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Tag filter
        if ($request->has('tag') && $request->tag) {
            $query->whereJsonContains('tags', $request->tag);
        }

        // Has appointments filter
        if ($request->has('has_appointments') && $request->has_appointments !== null) {
            if ($request->has_appointments === 'true' || $request->has_appointments === true) {
                $query->has('appointments');
            } else {
                $query->doesntHave('appointments');
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 25);
        $customers = $query->paginate($perPage);

        // Transform the data using eager loaded counts
        $customers->getCollection()->transform(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company_name' => $customer->company_name,
                'tags' => $customer->tags ?? [],
                'notes' => $customer->notes,
                'appointments_count' => $customer->appointments_count ?? 0,
                'calls_count' => $customer->calls_count ?? 0,
                'total_revenue' => $customer->appointments_sum_price ?? 0,
                'created_at' => $customer->created_at->format('d.m.Y'),
                'avatar_url' => $customer->avatar_url,
            ];
        });

        // Get stats with single query
        $statsQuery = Customer::where('company_id', $companyId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(YEAR(created_at) = ? AND MONTH(created_at) = ?) as new_this_month', 
                [Carbon::now()->year, Carbon::now()->month])
            ->first();
            
        $activeCustomers = Customer::where('company_id', $companyId)
            ->whereHas('appointments', function($q) {
                $q->where('created_at', '>=', Carbon::now()->subMonths(3));
            })
            ->count();
            
        $totalRevenue = DB::table('appointments')
            ->join('customers', 'appointments.customer_id', '=', 'customers.id')
            ->where('customers.company_id', $companyId)
            ->where('appointments.status', 'completed')
            ->sum('appointments.price') ?? 0;

        $stats = [
            'total_customers' => $statsQuery->total ?? 0,
            'new_this_month' => $statsQuery->new_this_month ?? 0,
            'active_customers' => $activeCustomers,
            'total_revenue' => $totalRevenue,
        ];

        return response()->json([
            'customers' => $customers,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        $customer = Customer::where('company_id', $companyId)
            ->with(['appointments' => function($q) {
                $q->latest()->limit(5);
            }, 'calls' => function($q) {
                $q->latest()->limit(5);
            }])
            ->findOrFail($id);

        // Get recent activities
        $activities = [];
        
        foreach ($customer->appointments()->latest()->limit(3)->get() as $appointment) {
            $activities[] = [
                'type' => 'appointment',
                'description' => "Termin: {$appointment->service_name}",
                'date' => $appointment->start_at->format('d.m.Y H:i'),
                'color' => 'blue',
            ];
        }
        
        foreach ($customer->calls()->latest()->limit(3)->get() as $call) {
            $activities[] = [
                'type' => 'call',
                'description' => "Anruf ({$call->duration_sec} Sek.)",
                'date' => $call->created_at->format('d.m.Y H:i'),
                'color' => 'green',
            ];
        }
        
        // Sort activities by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company_name' => $customer->company_name,
                'address' => $customer->address,
                'tags' => $customer->tags ?? [],
                'notes' => $customer->notes,
                'appointments_count' => $customer->appointments()->count(),
                'calls_count' => $customer->calls()->count(),
                'total_revenue' => $customer->appointments()
                    ->where('status', 'completed')
                    ->sum('price') ?? 0,
                'customer_since_days' => $customer->created_at->diffInDays(now()),
                'created_at' => $customer->created_at->format('d.m.Y'),
                'recent_activities' => array_slice($activities, 0, 5),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermission('customers.create')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|array',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Check for duplicates
        $existingCustomer = Customer::where('company_id', $companyId)
            ->where(function($q) use ($request) {
                $q->where('phone', $request->phone);
                if ($request->email) {
                    $q->orWhere('email', $request->email);
                }
            })
            ->first();

        if ($existingCustomer) {
            return response()->json([
                'error' => 'Ein Kunde mit dieser Telefonnummer oder E-Mail existiert bereits.'
            ], 422);
        }

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'address' => $request->address,
            'tags' => $request->tags,
            'notes' => $request->notes,
            'source' => 'manual',
        ]);

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermission('customers.edit')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|array',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        $customer = Customer::where('company_id', $companyId)->findOrFail($id);

        // Check for duplicates (excluding current customer)
        $existingCustomer = Customer::where('company_id', $companyId)
            ->where('id', '!=', $id)
            ->where(function($q) use ($request) {
                $q->where('phone', $request->phone);
                if ($request->email) {
                    $q->orWhere('email', $request->email);
                }
            })
            ->first();

        if ($existingCustomer) {
            return response()->json([
                'error' => 'Ein anderer Kunde mit dieser Telefonnummer oder E-Mail existiert bereits.'
            ], 422);
        }

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'address' => $request->address,
            'tags' => $request->tags,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Only admins can delete customers
        if (!canDeleteBusinessData()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Nur Administratoren können Kunden löschen'
            ], 403);
        }

        // For admins viewing portal, we need to determine the correct company_id
        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        if (isAdminViewingPortal() && !$companyId) {
            // Admin user doesn't have company_id, get from session
            $adminImpersonation = session('admin_impersonation');
            if ($adminImpersonation && isset($adminImpersonation['company_id'])) {
                $companyId = $adminImpersonation['company_id'];
            } else {
                // Use current_company_id from app instance
                $companyId = app('current_company_id');
            }
        }
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        $customer = Customer::where('company_id', $companyId)->findOrFail($id);

        // Check if customer has appointments or calls
        if ($customer->appointments()->exists() || $customer->calls()->exists()) {
            return response()->json([
                'error' => 'Kunde kann nicht gelöscht werden, da Termine oder Anrufe vorhanden sind.'
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermission('customers.export')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        // Build query with filters
        $query = Customer::where('company_id', $companyId);

        // Apply filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('tag') && $request->tag) {
            $query->whereJsonContains('tags', $request->tag);
        }

        // Get customers
        $customers = $query->orderBy('created_at', 'desc')->get();

        // CSV headers
        $headers = ['Name', 'Telefon', 'E-Mail', 'Firma', 'Tags', 'Termine', 'Anrufe', 'Umsatz', 'Erstellt am'];

        // Prepare CSV data
        $csvData = [];
        $csvData[] = $headers;

        foreach ($customers as $customer) {
            $csvData[] = [
                $customer->name,
                $customer->phone,
                $customer->email ?? '',
                $customer->company_name ?? '',
                implode(', ', $customer->tags ?? []),
                $customer->appointments()->count(),
                $customer->calls()->count(),
                number_format($customer->appointments()->where('status', 'completed')->sum('price') ?? 0, 2, ',', '.') . ' €',
                $customer->created_at->format('d.m.Y'),
            ];
        }

        $filename = 'kunden_export_' . now()->format('Y-m-d_His') . '.csv';
        
        return response()->streamDownload(function () use ($csvData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            foreach ($csvData as $row) {
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function tags(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Get all unique tags
        $tags = Customer::where('company_id', $companyId)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'tags' => $tags,
        ]);
    }
}