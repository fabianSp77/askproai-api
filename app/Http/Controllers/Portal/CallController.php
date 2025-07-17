<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallPortalData;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\CallNote;
use App\Models\CallAssignment;
use App\Notifications\CallAssignedNotification;
use App\Notifications\NewCallbackScheduledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\UserPreference;
use App\Helpers\CallDataFormatter;

class CallController extends Controller
{
    /**
     * Display a listing of calls based on user permissions
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // If admin viewing, get company from session
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $user->company_id;
        }
        
        // Get company phone numbers for filtering
        $companyPhoneNumbers = \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
            
        $query = Call::query()
            ->where('company_id', $companyId)
            ->whereIn('to_number', $companyPhoneNumbers)  // Only show calls to company's phone numbers
            ->with(['branch', 'callPortalData', 'callPortalData.assignedTo', 'customer', 'charge']);

        // Filter by status
        if ($request->filled('status')) {
            $query->whereHas('callPortalData', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        // Filter by urgency
        if ($request->filled('urgency')) {
            $query->where(function ($q) use ($request) {
                $q->where('urgency_level', $request->urgency)
                  ->orWhereJsonContains('metadata->customer_data->urgency', $request->urgency);
            });
        }

        // Filter by age (time since call)
        if ($request->filled('age')) {
            $hoursAgo = match($request->age) {
                '1h' => 1,
                '4h' => 4,
                '24h' => 24,
                '48h' => 48,
                '7d' => 168,
                default => null
            };
            
            if ($hoursAgo) {
                $query->where('created_at', '>=', Carbon::now()->subHours($hoursAgo));
            }
        }

        // Filter by duration
        if ($request->filled('duration')) {
            switch ($request->duration) {
                case 'short':
                    $query->where('duration_sec', '<', 60);
                    break;
                case 'medium':
                    $query->whereBetween('duration_sec', [60, 300]);
                    break;
                case 'long':
                    $query->whereBetween('duration_sec', [300, 600]);
                    break;
                case 'very_long':
                    $query->where('duration_sec', '>', 600);
                    break;
            }
        }

        // Filter by assignment
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'me' && $user) {
                $query->whereHas('callPortalData', function ($q) use ($user) {
                    $q->where('assigned_to', $user->id);
                });
            } elseif ($request->assigned_to === 'unassigned') {
                $query->whereHas('callPortalData', function ($q) {
                    $q->whereNull('assigned_to');
                });
            } else {
                $query->whereHas('callPortalData', function ($q) use ($request) {
                    $q->where('assigned_to', $request->assigned_to);
                });
            }
        }

        // Filter by branch (for multi-branch companies)
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by callback scheduling
        if ($request->boolean('has_callback')) {
            $query->whereHas('callPortalData', function ($q) {
                $q->whereNotNull('callback_scheduled_at')
                    ->where('callback_scheduled_at', '>', now());
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('retell_call_id', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('callPortalData', function ($q) use ($search) {
                        $q->where('internal_notes', 'like', "%{$search}%");
                    });
            });
        }

        // Apply permission-based filtering (skip for admin viewing)
        if (!session('is_admin_viewing') && $user && !$user->hasPermission('calls.view_all')) {
            // Only show calls assigned to user or their team
            $query->where(function ($q) use ($user) {
                $q->whereHas('callPortalData', function ($q) use ($user) {
                    $q->where('assigned_to', $user->id);
                });
                
                // If user can view team calls, include unassigned
                if ($user->hasPermission('calls.view_team')) {
                    $q->orWhereHas('callPortalData', function ($q) {
                        $q->whereNull('assigned_to');
                    });
                }
            });
        }

        // Sort by priority (new/requires_action first)
        $query->select('calls.*')
        ->leftJoin('call_portal_data as portal_data', 'calls.id', '=', 'portal_data.call_id')
        ->orderByRaw("
            CASE 
                WHEN portal_data.status = 'requires_action' THEN 1
                WHEN portal_data.status = 'new' THEN 2
                WHEN portal_data.status = 'callback_scheduled' THEN 3
                WHEN portal_data.status = 'in_progress' THEN 4
                ELSE 5
            END
        ")
        ->orderBy('calls.created_at', 'desc');

        $calls = $query->paginate(20);

        // Get statistics
        $stats = $this->getCallStatistics($user);

        // Get team members for assignment dropdown
        $teamMembers = [];
        if (session('is_admin_viewing') || ($user && $user->hasPermission('calls.edit_all'))) {
            // Get company ID from session if admin viewing
            $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
            $teamMembers = PortalUser::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        // Get user column preferences
        $userId = session('is_admin_viewing') ? session('admin_impersonation.user_id', 0) : ($user ? $user->id : 0);
        $userType = session('is_admin_viewing') ? 'admin' : 'portal';
        
        $columnPrefs = UserPreference::getPreference($userId, $userType, 'calls_columns', UserPreference::getDefaultCallsColumns());
        $viewTemplates = UserPreference::getViewTemplates();
        
        // Check if user has billing.view permission for costs column
        $canViewCosts = session('is_admin_viewing') || ($user && $user->hasPermission('billing.view'));
        if (!$canViewCosts && isset($columnPrefs['costs'])) {
            $columnPrefs['costs']['visible'] = false;
        }
        
        // Sort columns by order
        uasort($columnPrefs, function($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });
        
        // Load React SPA directly
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }

    /**
     * Display the specified call
     */
    public function show(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions (skip for admin viewing)
        if (!session('is_admin_viewing')) {
            $this->authorizeViewCall($call, $user);
        }
        
        // Always use React view for business portal
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();

        // Load relationships
        $call->load([
            'callPortalData.assignedTo',
            'customer',
            'customer.appointments' => function ($query) {
                $query->orderBy('starts_at', 'desc')->limit(5);
            },
            'branch',
            'callNotes.user',
            'charge'
        ]);

        // Ensure JSON fields are properly loaded as arrays
        if (is_string($call->webhook_data)) {
            $call->webhook_data = json_decode($call->webhook_data, true);
        }
        
        if (is_string($call->transcript_object)) {
            $decoded = json_decode($call->transcript_object, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $call->transcript_object = $decoded;
            }
        }
        
        if (is_string($call->transcript_with_tools)) {
            $decoded = json_decode($call->transcript_with_tools, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $call->transcript_with_tools = $decoded;
            }
        }
        
        // Also check metadata field for JSON decoding
        if (is_string($call->metadata)) {
            $decoded = json_decode($call->metadata, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $call->metadata = $decoded;
            }
        }

        // Get call history for this customer
        $customerCallHistory = [];
        if ($call->customer_id) {
            // Get company phone numbers for filtering
            $companyPhoneNumbers = \App\Models\PhoneNumber::where('company_id', $call->company_id)
                ->where('is_active', true)
                ->pluck('number')
                ->toArray();
                
            $customerCallHistory = Call::where('customer_id', $call->customer_id)
                ->where('id', '!=', $call->id)
                ->whereIn('to_number', $companyPhoneNumbers)  // Only show calls to company's numbers
                ->with('callPortalData')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        // Get team members for assignment
        $teamMembers = [];
        if (session('is_admin_viewing') || ($user && $user->hasPermission('calls.edit_all'))) {
            // Get company ID from session if admin viewing
            $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
            $teamMembers = PortalUser::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        // Track view activity
        $this->trackActivity($call, 'viewed', 'Anruf im Business Portal angezeigt', $user);
        
        // Use redesigned view
        return view('portal.calls.show-redesigned', compact('call', 'customerCallHistory', 'teamMembers'));
    }

    /**
     * Update call status
     */
    public function updateStatus(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        $this->authorizeEditCall($call, $user);

        $request->validate([
            'status' => ['required', Rule::in(CallPortalData::STATUSES)],
            'internal_notes' => 'nullable|string|max:2000'
        ]);

        DB::transaction(function () use ($call, $request, $user) {
            $portalData = $call->callPortalData ?? new CallPortalData(['call_id' => $call->id]);
            
            $oldStatus = $portalData->status;
            $portalData->status = $request->status;
            
            if ($request->filled('internal_notes')) {
                $portalData->internal_notes = $request->internal_notes;
            }
            
            // Auto-assign if changing from 'new'
            if ($oldStatus === 'new' && !$portalData->assigned_to) {
                $portalData->assigned_to = $user->id;
                $portalData->assigned_at = now();
            }
            
            // Track status changes
            $statusHistory = $portalData->status_history ?? [];
            $statusHistory[] = [
                'status' => $request->status,
                'changed_by' => $user->id,
                'changed_at' => now()->toIso8601String(),
                'notes' => $request->internal_notes
            ];
            $portalData->status_history = $statusHistory;
            
            $portalData->save();
            
            // Create a note for the status change
            CallNote::create([
                'call_id' => $call->id,
                'user_id' => $user->id,
                'type' => 'status_change',
                'content' => "Status geändert von '{$oldStatus}' zu '{$request->status}'"
            ]);
        });

        return back()->with('success', 'Anrufstatus wurde aktualisiert.');
    }

    /**
     * Assign call to a team member
     */
    public function assign(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        
        if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('calls.edit_all'))) {
            abort(403);
        }

        $request->validate([
            'assigned_to' => 'required|exists:portal_users,id'
        ]);

        $assignee = PortalUser::findOrFail($request->assigned_to);
        
        // Ensure assignee is from same company
        $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
        if ($assignee->company_id !== $companyId) {
            abort(403);
        }

        DB::transaction(function () use ($call, $assignee, $user) {
            $portalData = $call->callPortalData ?? new CallPortalData(['call_id' => $call->id]);
            
            $previousAssignee = $portalData->assigned_to;
            $portalData->assigned_to = $assignee->id;
            $portalData->assigned_at = now();
            
            if ($portalData->status === 'new') {
                $portalData->status = 'in_progress';
            }
            
            $portalData->save();
            
            // Create assignment record
            CallAssignment::create([
                'call_id' => $call->id,
                'assigned_by' => $user->id,
                'assigned_to' => $assignee->id,
                'previous_assignee' => $previousAssignee,
                'notes' => $request->notes
            ]);
            
            // Create note
            CallNote::create([
                'call_id' => $call->id,
                'user_id' => $user->id,
                'type' => 'assignment',
                'content' => "Anruf zugewiesen an {$assignee->name}"
            ]);
            
            // Send notification to assignee
            if ($assignee->notification_preferences['call_assigned'] ?? true) {
                $assignee->notify(new CallAssignedNotification($call, $user));
            }
        });

        return back()->with('success', "Anruf wurde {$assignee->name} zugewiesen.");
    }

    /**
     * Add a note to the call
     */
    public function addNote(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        $this->authorizeEditCall($call, $user);

        $request->validate([
            'content' => 'required|string|max:2000',
            'type' => ['nullable', Rule::in(['general', 'customer_feedback', 'internal', 'action_required'])]
        ]);

        $note = CallNote::create([
            'call_id' => $call->id,
            'user_id' => $user->id,
            'type' => $request->type ?? 'general',
            'content' => $request->content
        ]);

        return back()->with('success', 'Notiz wurde hinzugefügt.');
    }

    /**
     * Schedule a callback
     */
    public function scheduleCallback(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        $this->authorizeEditCall($call, $user);

        $request->validate([
            'callback_date' => 'required|date|after:now',
            'callback_time' => 'required|date_format:H:i',
            'callback_notes' => 'nullable|string|max:500'
        ]);

        $callbackDateTime = Carbon::parse($request->callback_date . ' ' . $request->callback_time);

        DB::transaction(function () use ($call, $callbackDateTime, $request, $user) {
            $portalData = $call->callPortalData ?? new CallPortalData(['call_id' => $call->id]);
            
            $portalData->callback_scheduled_at = $callbackDateTime;
            $portalData->callback_scheduled_by = $user->id;
            $portalData->callback_notes = $request->callback_notes;
            $portalData->status = 'callback_scheduled';
            
            $portalData->save();
            
            // Create note
            CallNote::create([
                'call_id' => $call->id,
                'user_id' => $user->id,
                'type' => 'callback_scheduled',
                'content' => "Rückruf geplant für " . $callbackDateTime->format('d.m.Y H:i')
            ]);
            
            // Send notification to assigned user
            if ($portalData->assignedTo && $portalData->assignedTo->notification_preferences['callback_reminder'] ?? true) {
                $portalData->assignedTo->notify(new NewCallbackScheduledNotification($call, $callbackDateTime));
            }
        });

        return back()->with('success', 'Rückruf wurde geplant.');
    }

    /**
     * Send call summary email
     */
    public function sendSummary(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        
        if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('calls.edit_all'))) {
            abort(403);
        }

        // Ensure call belongs to user's company
        $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
        if ($call->company_id !== $companyId) {
            abort(403);
        }

        $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
            'message' => 'nullable|string|max:500',
            'include_transcript' => 'nullable|boolean',
            'include_csv' => 'nullable|boolean',
        ]);

        try {
            // Dispatch the job
            \App\Jobs\SendCallSummaryJob::dispatch(
                $call,
                $request->recipients,
                $request->message
            );

            return response()->json([
                'success' => true,
                'message' => 'Zusammenfassung wird an ' . count($request->recipients) . ' Empfänger gesendet.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send call summary', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Senden der Zusammenfassung.',
            ], 500);
        }
    }

    /**
     * Export calls to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('calls.export'))) {
            abort(403);
        }

        // Get company ID from session if admin viewing
        $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
        
        // Get company phone numbers for filtering
        $companyPhoneNumbers = \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
        
        $query = Call::query()
            ->where('company_id', $companyId)
            ->whereIn('to_number', $companyPhoneNumbers)
            ->with(['branch', 'callPortalData.assignedTo', 'customer']);

        // Apply same filters as index
        // ... (same filter logic as in index method)

        $calls = $query->get();

        $csvData = [];
        $csvData[] = ['Datum', 'Uhrzeit', 'Telefonnummer', 'Kunde', 'Status', 'Zugewiesen an', 'Filiale', 'Dauer'];

        foreach ($calls as $call) {
            $csvData[] = [
                $call->created_at->format('d.m.Y'),
                $call->created_at->format('H:i'),
                $call->phone_number,
                $call->customer->name ?? 'Unbekannt',
                $call->callPortalData->status ?? 'new',
                $call->callPortalData->assignedTo->name ?? 'Nicht zugewiesen',
                $call->branch->name ?? '',
                gmdate('H:i:s', $call->duration_sec ?? 0)
            ];
        }

        $filename = 'anrufe_export_' . now()->format('Y-m-d_His') . '.csv';
        
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

    /**
     * Export multiple calls with filters
     */
    public function exportBatch(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('calls.export'))) {
            abort(403);
        }

        $request->validate([
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'format' => 'nullable|in:csv,xlsx,json',
        ]);

        try {
            $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
            
            $exportService = new \App\Services\CallExportService();
            
            $filters = $request->filters ?? [];
            $filters['company_id'] = $companyId;
            
            $csv = $exportService->exportWithFilters($filters, $request->columns);
            
            $filename = $exportService->generateFilename('anrufe_batch');
            
            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to export calls batch', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Export.',
            ], 500);
        }
    }

    /**
     * Get call statistics for dashboard
     */
    private function getCallStatistics(?PortalUser $user)
    {
        // Get company ID from session if admin viewing
        $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
        
        // Get company phone numbers for filtering
        $companyPhoneNumbers = \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
            
        $baseQuery = Call::where('company_id', $companyId)
            ->whereIn('to_number', $companyPhoneNumbers);
        
        // Apply permission-based filtering (skip for admin viewing)
        if (!session('is_admin_viewing') && $user && !$user->hasPermission('calls.view_all')) {
            $baseQuery->whereHas('callPortalData', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }

        $stats = [
            'total_today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'new' => (clone $baseQuery)->whereHas('callPortalData', function ($q) {
                $q->where('status', 'new');
            })->count(),
            'in_progress' => (clone $baseQuery)->whereHas('callPortalData', function ($q) {
                $q->where('status', 'in_progress');
            })->count(),
            'requires_action' => (clone $baseQuery)->whereHas('callPortalData', function ($q) {
                $q->where('status', 'requires_action');
            })->count(),
            'callbacks_today' => (clone $baseQuery)->whereHas('callPortalData', function ($q) {
                $q->whereDate('callback_scheduled_at', today());
            })->count(),
        ];

        // Calculate costs for today (only if user has permission)
        if (session('is_admin_viewing') || ($user && $user->hasPermission('billing.view'))) {
            $todaysCalls = (clone $baseQuery)
                ->whereDate('created_at', today())
                ->with('charge') // Eager load charge relation
                ->get();
            $todaysCosts = 0;
            
            foreach ($todaysCalls as $call) {
                if ($call->charge) {
                    $todaysCosts += $call->charge->amount_charged;
                } elseif ($call->duration_sec) {
                    $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                    if ($pricing) {
                        $todaysCosts += $pricing->calculatePrice($call->duration_sec);
                    } else {
                        $billingRate = \App\Models\BillingRate::where('company_id', $call->company_id)->active()->first();
                        if ($billingRate) {
                            $todaysCosts += $billingRate->calculateCharge($call->duration_sec);
                        }
                    }
                }
            }
            
            $stats['costs_today'] = $todaysCosts;
        }

        return $stats;
    }

    /**
     * Authorize viewing a call
     */
    private function authorizeViewCall(Call $call, $user)
    {
        // If admin viewing, check company from session
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            if ($call->company_id !== $companyId) {
                abort(404);
            }
            return;
        }
        
        if ($call->company_id !== $user->company_id) {
            abort(404);
        }

        if (!session('is_admin_viewing') && $user && !$user->hasPermission('calls.view_all')) {
            $portalData = $call->callPortalData;
            if (!$portalData || $portalData->assigned_to !== $user->id) {
                if (!$user->hasPermission('calls.view_team')) {
                    abort(403);
                }
            }
        }
    }

    /**
     * Authorize editing a call
     */
    private function authorizeEditCall(Call $call, PortalUser $user)
    {
        // If admin viewing, check company from session
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            if ($call->company_id !== $companyId) {
                abort(404);
            }
            return;
        }
        
        if ($call->company_id !== $user->company_id) {
            abort(404);
        }

        if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('calls.edit_all'))) {
            $portalData = $call->callPortalData;
            if (!$portalData || $portalData->assigned_to !== $user->id) {
                abort(403);
            }
        }
    }

    /**
     * Export selected calls to CSV or PDF
     */
    public function exportBulk(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!session('is_admin_viewing') && (!$user || !$user->hasPermission('calls.export'))) {
            abort(403);
        }

        $request->validate([
            'call_ids' => 'required|array',
            'call_ids.*' => 'exists:calls,id',
            'format' => 'required|in:csv,pdf'
        ]);

        // Get company ID from session if admin viewing
        $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
        
        // Get selected calls
        $calls = Call::whereIn('id', $request->call_ids)
            ->where('company_id', $companyId)
            ->with(['branch', 'callPortalData.assignedTo', 'customer', 'charge'])
            ->get();

        if ($request->format === 'csv') {
            return $this->exportBulkCsv($calls);
        } else {
            return $this->exportBulkPdf($calls);
        }
    }

    /**
     * Export calls to CSV
     */
    private function exportBulkCsv($calls)
    {
        $csvData = [];
        $csvData[] = ['Datum', 'Uhrzeit', 'Telefonnummer', 'Kunde', 'Firma', 'Anliegen', 'Dringlichkeit', 'Status', 'Dauer', 'Kosten', 'Zugewiesen an', 'Filiale'];

        foreach ($calls as $call) {
            // Calculate cost if user has permission
            $cost = null;
            if (session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('billing.view'))) {
                if ($call->charge) {
                    $cost = $call->charge->amount_charged;
                } elseif ($call->duration_sec) {
                    $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                    if ($pricing) {
                        $cost = $pricing->calculatePrice($call->duration_sec);
                    }
                }
            }

            $csvData[] = [
                $call->created_at->format('d.m.Y'),
                $call->created_at->format('H:i'),
                $call->phone_number,
                $call->extracted_name ?? $call->customer->name ?? 'Unbekannt',
                $call->metadata['customer_data']['company'] ?? $call->customer->company_name ?? '',
                $call->reason_for_visit ?? '',
                $call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? '',
                $call->callPortalData->status ?? 'new',
                gmdate('H:i:s', $call->duration_sec ?? 0),
                $cost ? number_format($cost, 2, ',', '.') . ' €' : '',
                $call->callPortalData->assignedTo->name ?? 'Nicht zugewiesen',
                $call->branch->name ?? ''
            ];
        }

        $filename = 'anrufe_export_' . now()->format('Y-m-d_His') . '.csv';
        
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

    /**
     * Export calls to PDF
     */
    private function exportBulkPdf($calls)
    {
        // Calculate costs for each call if user has permission
        $showCosts = session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('billing.view'));
        $totalCost = 0;
        
        foreach ($calls as $call) {
            $cost = null;
            if ($showCosts) {
                if ($call->charge) {
                    $cost = $call->charge->amount_charged;
                } elseif ($call->duration_sec) {
                    $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                    if ($pricing) {
                        $cost = $pricing->calculatePrice($call->duration_sec);
                    } else {
                        $billingRate = \App\Models\BillingRate::where('company_id', $call->company_id)->active()->first();
                        if ($billingRate) {
                            $cost = $billingRate->calculateCharge($call->duration_sec);
                        }
                    }
                }
            }
            // Add cost to call object for use in view
            $call->cost = $cost;
            $totalCost += $cost ?? 0;
        }
        
        // Generate PDF using Browsershot
        $html = view('portal.calls.export-pdf', [
            'calls' => $calls,
            'showCosts' => $showCosts,
            'totalCost' => $totalCost,
            'showSummary' => true
        ])->render();
        
        $filename = 'anrufe_export_' . now()->format('Y-m-d_His') . '.pdf';
        $tempPath = storage_path('app/temp/' . $filename);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        try {
            \Spatie\Browsershot\Browsershot::html($html)
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->savePdf($tempPath);
            
            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PDF export failed: ' . $e->getMessage());
            return back()->with('error', 'PDF Export fehlgeschlagen. Bitte versuchen Sie es erneut.');
        }
    }
    
    /**
     * Update user column preferences
     */
    public function updateColumnPreferences(Request $request)
    {
        $request->validate([
            'columns' => 'required|array',
            'columns.*.visible' => 'required|boolean',
            'columns.*.order' => 'required|integer|min:1',
        ]);
        
        $user = Auth::guard('portal')->user();
        $userId = session('is_admin_viewing') ? session('admin_impersonation.user_id', 0) : ($user ? $user->id : 0);
        $userType = session('is_admin_viewing') ? 'admin' : 'portal';
        
        // Get current preferences
        $currentPrefs = UserPreference::getPreference($userId, $userType, 'calls_columns', UserPreference::getDefaultCallsColumns());
        
        // Update with new values
        foreach ($request->columns as $key => $values) {
            if (isset($currentPrefs[$key])) {
                $currentPrefs[$key]['visible'] = $values['visible'] ?? false;
                $currentPrefs[$key]['order'] = $values['order'] ?? 999;
            }
        }
        
        // Save preferences
        UserPreference::setPreference($userId, $userType, 'calls_columns', $currentPrefs);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Apply a predefined view template
     */
    public function applyViewTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required|string|in:compact,standard,detailed,management',
        ]);
        
        $user = Auth::guard('portal')->user();
        $userId = session('is_admin_viewing') ? session('admin_impersonation.user_id', 0) : ($user ? $user->id : 0);
        $userType = session('is_admin_viewing') ? 'admin' : 'portal';
        
        $templates = UserPreference::getViewTemplates();
        $template = $templates[$request->template] ?? null;
        
        if (!$template) {
            return response()->json(['error' => 'Invalid template'], 400);
        }
        
        // Get default columns
        $defaultColumns = UserPreference::getDefaultCallsColumns();
        
        // Apply template
        foreach ($defaultColumns as $key => &$column) {
            $column['visible'] = in_array($key, $template['columns']);
        }
        
        // Save preferences
        UserPreference::setPreference($userId, $userType, 'calls_columns', $defaultColumns);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Format call data for copying/exporting
     */
    public function formatCall(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions (skip for admin viewing)
        if (!session('is_admin_viewing')) {
            $this->authorizeViewCall($call, $user);
        }
        
        // Load necessary relationships
        $call->load([
            'callPortalData.assignedTo',
            'customer',
            'branch',
            'callNotes.user',
            'charge',
            'appointment'
        ]);
        
        $format = $request->input('format', 'complete');
        $options = [];
        
        switch ($format) {
            case 'summary':
                $formatted = CallDataFormatter::formatSummaryForClipboard($call);
                break;
                
            case 'complete':
                $options = [
                    'include_transcript' => $request->input('include_transcript', true),
                    'include_metadata' => $request->input('include_metadata', false),
                    'format' => 'text'
                ];
                $formatted = CallDataFormatter::formatForClipboard($call, $options);
                break;
                
            case 'html':
                $options = [
                    'include_transcript' => $request->input('include_transcript', false),
                    'include_metadata' => $request->input('include_metadata', false),
                    'format' => 'html'
                ];
                $formatted = CallDataFormatter::formatForClipboard($call, $options);
                break;
                
            case 'custom':
                $fields = $request->input('fields', []);
                $options = [
                    'include_transcript' => $fields['transcript'] ?? false,
                    'include_metadata' => $fields['metadata'] ?? false,
                    'format' => 'text'
                ];
                
                // TODO: Implement custom field selection logic in CallDataFormatter
                $formatted = CallDataFormatter::formatForClipboard($call, $options);
                break;
                
            default:
                $formatted = CallDataFormatter::formatForClipboard($call);
        }
        
        return response()->json([
            'success' => true,
            'formatted' => $formatted
        ]);
    }
    
    /**
     * Translate call content (summary, transcript, etc.)
     */
    public function translate(Request $request, Call $call)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$user->hasPermission('calls.view_own')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Validate request
        $request->validate([
            'text' => 'required|string',
            'target_language' => 'required|string|in:de,en,es,fr,it' // Add more languages as needed
        ]);
        
        try {
            // Here we would normally call a translation API (Google Translate, DeepL, etc.)
            // For now, we'll simulate a translation for the demo
            
            $text = $request->input('text');
            $targetLang = $request->input('target_language');
            
            // Simple mock translation - in production, use a real translation service
            if ($targetLang === 'de') {
                // For demo purposes, if the text contains certain English phrases, translate them
                $translations = [
                    'Customer called about' => 'Kunde rief an wegen',
                    'appointment' => 'Termin',
                    'urgent' => 'dringend',
                    'callback requested' => 'Rückruf erbeten',
                    'The customer' => 'Der Kunde',
                    'requested' => 'bat um',
                    'They need' => 'Sie benötigen',
                    'wants to' => 'möchte',
                    'schedule' => 'vereinbaren',
                    'reschedule' => 'verschieben',
                    'cancel' => 'absagen',
                    'The client' => 'Der Kunde',
                    'follow up' => 'nachfassen',
                    'called regarding' => 'rief an bezüglich',
                ];
                
                $translatedText = $text;
                foreach ($translations as $en => $de) {
                    $translatedText = str_ireplace($en, $de, $translatedText);
                }
                
                // If no translation happened, return a generic German translation
                if ($translatedText === $text && str_contains(strtolower($text), 'customer')) {
                    $translatedText = "Der Kunde hat angerufen. " . $text;
                }
            } else {
                // For other languages, just return the original text with a note
                $translatedText = $text . " [Translation to {$targetLang} not available]";
            }
            
            // Log translation request for monitoring
            Log::info('Call translation requested', [
                'call_id' => $call->id,
                'user_id' => $user->id,
                'target_language' => $targetLang,
                'text_length' => strlen($text)
            ]);
            
            // Track translation activity
            $this->trackActivity($call, 'translated', 'Zusammenfassung wurde übersetzt (EN → DE)', $user);
            
            return response()->json([
                'success' => true,
                'translated_text' => $translatedText,
                'source_language' => 'en', // Auto-detected in real implementation
                'target_language' => $targetLang
            ]);
            
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Translation service temporarily unavailable'
            ], 500);
        }
    }
    
    /**
     * Export a single call to PDF
     */
    public function exportPdf(Call $call)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions
        if (!$user->hasPermission('calls.view_own')) {
            abort(403);
        }
        
        // Calculate cost if user has permission
        $showCosts = session('is_admin_viewing') || $user->hasPermission('billing.view');
        $cost = null;
        
        if ($showCosts) {
            if ($call->charge) {
                $cost = $call->charge->amount_charged;
            } elseif ($call->duration_sec) {
                $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                if ($pricing) {
                    $cost = $pricing->calculatePrice($call->duration_sec);
                } else {
                    $billingRate = \App\Models\BillingRate::where('company_id', $call->company_id)->active()->first();
                    if ($billingRate) {
                        $cost = $billingRate->calculateCharge($call->duration_sec);
                    }
                }
            }
        }
        
        // Add cost to call object for use in view
        $call->cost = $cost;
        
        // Load relationships
        $call->load(['branch', 'callPortalData', 'callPortalData.assignedTo', 'customer', 'charge', 'callNotes.user']);
        
        // Track export activity
        $this->trackActivity($call, 'exported', 'Anruf als PDF exportiert', $user);
        
        // Generate PDF using Browsershot
        $html = view('portal.calls.export-pdf-single', [
            'call' => $call,
            'showCosts' => $showCosts
        ])->render();
        
        $filename = 'anruf_' . $call->id . '_' . now()->format('Y-m-d_His') . '.pdf';
        $tempPath = storage_path('app/temp/' . $filename);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        try {
            \Spatie\Browsershot\Browsershot::html($html)
                ->format('A4')
                ->margins(20, 20, 20, 20)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->savePdf($tempPath);
            
            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PDF export failed: ' . $e->getMessage());
            
            // Fallback to simple HTML-to-PDF if Browsershot fails
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.calls.export-pdf-single', [
                    'call' => $call,
                    'showCosts' => $showCosts
                ]);
                
                return $pdf->download($filename);
            } catch (\Exception $e2) {
                Log::error('PDF export fallback also failed: ' . $e2->getMessage());
                return back()->with('error', 'PDF Export fehlgeschlagen. Bitte nutzen Sie die Druckfunktion als Alternative.');
            }
        }
    }
    
    /**
     * Track activity in the notes system
     */
    private function trackActivity(Call $call, string $type, string $content, ?PortalUser $user = null)
    {
        // Only track if user is authenticated
        if (!$user) {
            return;
        }
        
        // Enhanced activity types for comprehensive tracking
        $activityTypes = [
            'viewed' => 'view',
            'status_changed' => 'status_change',
            'assigned' => 'assignment',
            'note_added' => 'note',
            'exported' => 'export',
            'translated' => 'translation',
            'printed' => 'print',
            'callback_scheduled' => 'callback',
            'email_sent' => 'email',
            'customer_updated' => 'customer_update'
        ];
        
        $noteType = $activityTypes[$type] ?? 'activity';
        
        // Create activity note
        CallNote::create([
            'call_id' => $call->id,
            'user_id' => $user->id,
            'type' => $noteType,
            'content' => '[Portal-Aktivität] ' . $content,
            'metadata' => [
                'activity_type' => $type,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
                'timestamp' => now()->toIso8601String()
            ]
        ]);
        
        // Update last activity timestamp on call portal data
        if ($call->callPortalData) {
            $call->callPortalData->update([
                'last_activity_at' => now(),
                'last_activity_by' => $user->id
            ]);
        }
    }
}