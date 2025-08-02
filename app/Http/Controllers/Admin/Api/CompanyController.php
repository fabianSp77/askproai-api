<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyController extends BaseAdminApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::query()
            ->withCount(['branches', 'appointments', 'customers', 'calls']);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('active', $request->get('status') === 'active');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $companies = $query->paginate($request->get('per_page', 10));

        return response()->json($companies);
    }

    public function show($id): JsonResponse
    {
        $company = Company::query()
            ->with([
                'branches' => function($q) {
                    $q->withoutGlobalScopes()->with('phoneNumbers', 'staff');
                },
                'services',
                'phoneNumbers'
            ])
            ->withCount(['branches', 'appointments', 'customers', 'calls'])
            ->findOrFail($id);

        // Add additional stats
        $company->stats = [
            'total_revenue' => $company->appointments()
                ->whereIn('status', ['completed', 'confirmed'])
                ->sum('price'),
            'appointments_today' => $company->appointments()
                ->whereDate('start_time', today())
                ->count(),
            'calls_today' => $company->calls()
                ->whereDate('created_at', today())
                ->count(),
            'active_staff' => $company->staff()->where('active', true)->count(),
        ];

        return response()->json($company);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'timezone' => 'required|string',
            'language' => 'required|string',
            'active' => 'boolean',
        ]);

        $company = Company::create($validated);

        return response()->json($company, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:companies,email,' . $id,
            'phone' => 'sometimes|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'timezone' => 'sometimes|string',
            'language' => 'sometimes|string',
            'active' => 'sometimes|boolean',
            'retell_api_key' => 'nullable|string',
            'retell_agent_id' => 'nullable|string',
            'calcom_api_key' => 'nullable|string',
            'calcom_team_slug' => 'nullable|string',
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    public function destroy($id): JsonResponse
    {
        $company = Company::findOrFail($id);
        
        // Check if company has data
        if ($company->appointments()->exists() || $company->calls()->exists()) {
            return response()->json([
                'message' => 'Cannot delete company with existing data. Please deactivate instead.'
            ], 422);
        }

        $company->delete();

        return response()->json(null, 204);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total_companies' => Company::query()->count(),
            'active_companies' => Company::query()->where('active', true)->count(),
            'total_appointments' => \App\Models\Appointment::withoutGlobalScopes()->count(),
            'appointments_today' => \App\Models\Appointment::withoutGlobalScopes()->whereDate('start_time', today())->count(),
            'total_calls' => \App\Models\Call::withoutGlobalScopes()->count(),
            'calls_today' => \App\Models\Call::withoutGlobalScopes()->whereDate('created_at', today())->count(),
        ];

        return response()->json($stats);
    }

    public function activate($id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);
        $company->update(['active' => true]);

        return response()->json([
            'message' => 'Company activated successfully',
            'company' => $company
        ]);
    }

    public function deactivate($id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);
        $company->update(['active' => false]);

        return response()->json([
            'message' => 'Company deactivated successfully',
            'company' => $company
        ]);
    }

    public function syncCalcom($id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);

        if (!$company->calcom_api_key) {
            return response()->json([
                'message' => 'Cal.com API key not configured'
            ], 422);
        }

        try {
            // Trigger Cal.com sync job
            \App\Jobs\SyncCalcomEventTypesJob::dispatch($company);

            return response()->json([
                'message' => 'Cal.com sync initiated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync Cal.com',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function validateApiKeys(Request $request, $id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);
        $results = [];

        // Validate Retell API Key
        if ($company->retell_api_key) {
            try {
                $retellService = new \App\Services\RetellService($company);
                $retellService->validateApiKey();
                $results['retell'] = ['valid' => true, 'message' => 'Retell API key is valid'];
            } catch (\Exception $e) {
                $results['retell'] = ['valid' => false, 'message' => $e->getMessage()];
            }
        } else {
            $results['retell'] = ['valid' => false, 'message' => 'No API key configured'];
        }

        // Validate Cal.com API Key
        if ($company->calcom_api_key) {
            try {
                $calcomService = new \App\Services\CalcomService();
                $calcomService->setApiKey($company->calcom_api_key);
                $calcomService->me(); // Test API call
                $results['calcom'] = ['valid' => true, 'message' => 'Cal.com API key is valid'];
            } catch (\Exception $e) {
                $results['calcom'] = ['valid' => false, 'message' => $e->getMessage()];
            }
        } else {
            $results['calcom'] = ['valid' => false, 'message' => 'No API key configured'];
        }

        return response()->json($results);
    }

    public function getEventTypes($id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);

        if (!$company->calcom_api_key) {
            return response()->json([
                'message' => 'Cal.com API key not configured'
            ], 422);
        }

        try {
            $calcomService = new \App\Services\CalcomService();
            $calcomService->setApiKey($company->calcom_api_key);
            $eventTypes = $calcomService->getEventTypes();

            return response()->json($eventTypes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch event types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getWorkingHours($id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);
        
        $workingHours = $company->workingHours()
            ->orderBy('day_of_week')
            ->get()
            ->groupBy('day_of_week')
            ->map(function ($hours) {
                return $hours->map(function ($hour) {
                    return [
                        'start' => $hour->start_time,
                        'end' => $hour->end_time,
                        'is_working_day' => $hour->is_working_day
                    ];
                });
            });

        return response()->json($workingHours);
    }

    public function updateWorkingHours(Request $request, $id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);
        
        $validated = $request->validate([
            'working_hours' => 'required|array',
            'working_hours.*.day_of_week' => 'required|integer|between:0,6',
            'working_hours.*.is_working_day' => 'required|boolean',
            'working_hours.*.start_time' => 'required_if:working_hours.*.is_working_day,true|date_format:H:i',
            'working_hours.*.end_time' => 'required_if:working_hours.*.is_working_day,true|date_format:H:i|after:working_hours.*.start_time',
        ]);

        // Delete existing working hours
        $company->workingHours()->delete();

        // Create new working hours
        foreach ($validated['working_hours'] as $hours) {
            $company->workingHours()->create($hours);
        }

        return response()->json([
            'message' => 'Working hours updated successfully'
        ]);
    }

    public function getNotificationSettings($id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);
        
        return response()->json([
            'email_notifications_enabled' => $company->email_notifications_enabled ?? true,
            'sms_notifications_enabled' => $company->sms_notifications_enabled ?? false,
            'whatsapp_notifications_enabled' => $company->whatsapp_notifications_enabled ?? false,
            'notification_email_recipients' => $company->notification_email_recipients ?? [],
            'appointment_reminder_hours' => $company->appointment_reminder_hours ?? 24,
            'call_summary_enabled' => $company->call_summary_enabled ?? true,
        ]);
    }

    public function updateNotificationSettings(Request $request, $id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);
        
        $validated = $request->validate([
            'email_notifications_enabled' => 'boolean',
            'sms_notifications_enabled' => 'boolean',
            'whatsapp_notifications_enabled' => 'boolean',
            'notification_email_recipients' => 'array',
            'notification_email_recipients.*' => 'email',
            'appointment_reminder_hours' => 'integer|min:1|max:72',
            'call_summary_enabled' => 'boolean',
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Notification settings updated successfully'
        ]);
    }
}