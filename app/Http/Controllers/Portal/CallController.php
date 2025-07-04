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
use Carbon\Carbon;
use Illuminate\Validation\Rule;

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
            ->with(['branch', 'callPortalData', 'callPortalData.assignedTo', 'customer']);

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

        // Use redesigned view
        return view('portal.calls.index-redesigned', compact('calls', 'stats', 'teamMembers'));
    }

    /**
     * Display the specified call
     */
    public function show(Call $call)
    {
        $user = Auth::guard('portal')->user();
        
        // Check permissions (skip for admin viewing)
        if (!session('is_admin_viewing')) {
            $this->authorizeViewCall($call, $user);
        }

        // Load relationships
        $call->load([
            'callPortalData.assignedTo',
            'customer',
            'customer.appointments' => function ($query) {
                $query->orderBy('starts_at', 'desc')->limit(5);
            },
            'branch',
            'callNotes.user'
        ]);

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
        if (session('is_admin_viewing') || $user->hasPermission('calls.edit_all')) {
            // Get company ID from session if admin viewing
            $companyId = session('is_admin_viewing') ? session('admin_impersonation.company_id') : $user->company_id;
            $teamMembers = PortalUser::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

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
        
        if (!session('is_admin_viewing') && !$user->hasPermission('calls.edit_all')) {
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
     * Export calls to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!session('is_admin_viewing') && !$user->hasPermission('calls.export')) {
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
        if (!session('is_admin_viewing') && !$user->hasPermission('calls.view_all')) {
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
        if (session('is_admin_viewing') || $user->hasPermission('billing.view')) {
            $todaysCalls = (clone $baseQuery)->whereDate('created_at', today())->get();
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

        if (!session('is_admin_viewing') && !$user->hasPermission('calls.view_all')) {
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

        if (!session('is_admin_viewing') && !$user->hasPermission('calls.edit_all')) {
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
        
        if (!session('is_admin_viewing') && !$user->hasPermission('calls.export')) {
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
            ->with(['branch', 'callPortalData.assignedTo', 'customer'])
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
            if (Auth::guard('portal')->user()->hasPermission('billing.view') || session('is_admin_viewing')) {
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
        // TODO: Implement PDF export using DomPDF or similar
        // For now, redirect back with message
        return back()->with('info', 'PDF Export wird in Kürze verfügbar sein.');
    }
}