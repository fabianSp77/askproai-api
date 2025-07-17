<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\CallExportService;
use App\Jobs\SendCallSummaryJob;

class CallsApiController extends BaseApiController
{
    public function index(Request $request)
    {
        // Get authenticated user (portal or web)
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Build query with eager loading
        $query = Call::where('company_id', $company->id)
            ->with([
                'branch:id,name',
                'callAssignments' => function($q) {
                    $q->latest()->limit(1)->with('assignedTo:id,name');
                }
            ]);

        // Search filter with operators support
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            
            // Check for operators in search
            $hasOperators = false;
            $remainingSearch = $search;
            
            // Extract "von:" operator
            if (preg_match('/von:(\S+)/i', $search, $matches)) {
                $hasOperators = true;
                $fromNumber = $matches[1];
                $query->where('from_number', 'like', "%{$fromNumber}%");
                $remainingSearch = str_replace($matches[0], '', $remainingSearch);
            }
            
            // Extract "an:" operator
            if (preg_match('/an:(\S+)/i', $search, $matches)) {
                $hasOperators = true;
                $toNumber = $matches[1];
                $query->where('to_number', 'like', "%{$toNumber}%");
                $remainingSearch = str_replace($matches[0], '', $remainingSearch);
            }
            
            // If there's remaining text after removing operators, search in general fields
            $remainingSearch = trim($remainingSearch);
            if ($remainingSearch && !$hasOperators) {
                // No operators, do general search
                $query->where(function($q) use ($remainingSearch) {
                    $q->where('from_number', 'like', "%{$remainingSearch}%")
                      ->orWhere('to_number', 'like', "%{$remainingSearch}%")
                      ->orWhere('transcript', 'like', "%{$remainingSearch}%")
                      ->orWhere('summary', 'like', "%{$remainingSearch}%")
                      ->orWhere('extracted_name', 'like', "%{$remainingSearch}%");
                });
            } elseif ($remainingSearch) {
                // Has operators but also additional text, search in content fields
                $query->where(function($q) use ($remainingSearch) {
                    $q->where('transcript', 'like', "%{$remainingSearch}%")
                      ->orWhere('summary', 'like', "%{$remainingSearch}%");
                });
            }
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Branch filter
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 25);
        $calls = $query->paginate($perPage);

        // Transform the data
        $calls->getCollection()->transform(function ($call) {
            // Get current assignment from eager loaded data
            $currentAssignment = $call->callAssignments->first();
            
            // Get assigned user info from eager loaded data
            $assignedTo = null;
            if ($currentAssignment && $currentAssignment->assignedTo) {
                $assignedTo = [
                    'id' => $currentAssignment->assignedTo->id,
                    'name' => $currentAssignment->assignedTo->name,
                ];
            }
            
            return [
                'id' => $call->id,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'branch' => $call->branch ? [
                    'id' => $call->branch->id,
                    'name' => $call->branch->name,
                ] : null,
                'status' => $call->status,
                'call_status' => null,
                'duration_sec' => $call->duration_sec,
                'duration_formatted' => $call->duration_sec ? gmdate('i:s', $call->duration_sec) : '-',
                'created_at' => $call->created_at->format('d.m.Y H:i'),
                'created_at_iso' => $call->created_at->toISOString(),
                'summary' => $call->summary,
                'transcript' => $call->transcript,
                'assigned_to' => $assignedTo,
                'has_notes' => false,
                'notes_count' => 0,
                'analysis_score' => $call->analysis_score,
                'start_price' => $call->start_price,
                'total_cost' => $call->total_cost,
                // Additional fields for list view
                'recording_url' => $call->recording_url,
                'extracted_name' => $call->extracted_name,
                'extracted_email' => $call->extracted_email,
                'extracted_phone' => $call->extracted_phone,
                'urgency_level' => $call->urgency_level,
                'detected_language' => $call->detected_language,
            ];
        });

        // Get filter options
        $statuses = ['all', 'completed', 'in_progress', 'ended', 'error'];
        $branches = $company->branches()->select('id', 'name')->get();
        $callStatuses = [];

        return response()->json([
            'calls' => $calls,
            'filters' => [
                'statuses' => $statuses,
                'branches' => $branches,
                'call_statuses' => $callStatuses,
            ],
            'stats' => [
                'total_calls' => $company->calls()->count(),
                'total_today' => $company->calls()->whereDate('created_at', today())->count(),
                'today_calls' => $company->calls()->whereDate('created_at', today())->count(), // Keep for backwards compatibility
                'new' => $company->calls()->where('status', 'new')->count(),
                'action_required' => $company->calls()->whereIn('status', ['error', 'needs_attention'])->count(),
                'avg_duration' => $this->calculateAverageDuration($company),
            ],
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
        
        $call = Call::where('company_id', $companyId)
            ->with([
                'branch:id,name',
                'customer:id,name,email,phone',
                'callNotes.user:id,name',
                'callAssignments.assignedTo:id,name',
                'callAssignments.assignedBy:id,name',
            ])
            ->findOrFail($id);

        // Transform detailed data
        $currentAssignment = $call->callAssignments()->latest()->first();
        
        // Prepare comprehensive call data
        $callData = [
            'id' => $call->id,
            'from_number' => $call->from_number,
            'to_number' => $call->to_number,
            'branch' => $call->branch,
            'status' => $call->status,
            'call_status' => null,
            'duration_sec' => $call->duration_sec,
            'duration_formatted' => $call->duration_sec ? gmdate('i:s', $call->duration_sec) : '-',
            'created_at' => $call->created_at->format('d.m.Y H:i:s'),
            'created_at_iso' => $call->created_at->toISOString(),
            'ended_at' => $call->ended_at ? $call->ended_at->format('d.m.Y H:i:s') : null,
            'summary' => $call->summary ?? $call->call_summary,
            'transcript' => $call->transcript,
            'transcript_object' => $call->transcript_object,
            'assigned_to' => $currentAssignment ? $currentAssignment->assignedTo : null,
            'notes' => $call->callNotes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'content' => $note->content,
                    'user' => $note->user ? [
                        'id' => $note->user->id,
                        'name' => $note->user->name,
                    ] : null,
                    'created_at' => $note->created_at->format('d.m.Y H:i'),
                ];
            }),
            'assignment_history' => $call->callAssignments->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'assigned_to' => $assignment->assignedTo ? [
                        'id' => $assignment->assignedTo->id,
                        'name' => $assignment->assignedTo->name,
                    ] : null,
                    'assigned_by' => $assignment->assignedBy ? [
                        'id' => $assignment->assignedBy->id,
                        'name' => $assignment->assignedBy->name,
                    ] : null,
                    'notes' => $assignment->notes,
                    'created_at' => $assignment->created_at->format('d.m.Y H:i'),
                ];
            }),
            'analysis_score' => $call->analysis_score,
            'start_price' => $call->start_price,
            'total_cost' => $call->total_cost,
            'metadata' => $call->metadata,
            'recording_url' => $call->recording_url,
            'customer' => $call->customer,
            'extracted_data' => $call->metadata,
            // Additional extracted fields
            'extracted_name' => $call->extracted_name,
            'extracted_email' => $call->extracted_email,
            'extracted_phone' => $call->extracted_phone ?? $call->telefonnummer,
            'urgency_level' => $call->urgency_level,
            'reason_for_visit' => $call->reason_for_visit,
            'appointment_requested' => $call->appointment_requested,
            'appointment_made' => $call->appointment_made,
            'user_sentiment' => $call->user_sentiment,
            'detected_language' => $call->detected_language,
            'language_confidence' => $call->language_confidence,
            'call_successful' => $call->call_successful,
            // Custom analysis data
            'custom_analysis_data' => $call->custom_analysis_data,
            // Appointment related fields
            'datum_termin' => $call->datum_termin,
            'uhrzeit_termin' => $call->uhrzeit_termin,
            'dienstleistung' => $call->dienstleistung,
            // Cost breakdown
            'cost_breakdown' => $call->cost_breakdown,
            // Additional metadata
            'agent_name' => $call->agent_name,
            'agent_version' => $call->agent_version,
            'disconnection_reason' => $call->disconnection_reason,
        ];

        return response()->json([
            'call' => $callData,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,cancelled,no_show',
        ]);

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        $call = Call::where('company_id', $companyId)->findOrFail($id);
        
        // Check if user has permission (for portal users)
        if ($user instanceof PortalUser && !$user->hasPermission('calls.edit_own')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $call->status = $request->status;
        $call->save();

        return response()->json([
            'success' => true,
            'status' => $call->status,
        ]);
    }

    public function addNote(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        $call = Call::where('company_id', $companyId)->findOrFail($id);
        
        // Check if user has permission (for portal users)
        if ($user instanceof PortalUser && !$user->hasPermission('calls.edit_own')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $note = $call->callNotes()->create([
            'content' => $request->content,
            'user_id' => $user->id,
            'type' => 'general',
        ]);

        return response()->json([
            'success' => true,
            'note' => [
                'id' => $note->id,
                'content' => $note->content,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'created_at' => $note->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function exportPdf(Request $request, $id)
    {
        // Use the HTML export controller instead for better compatibility
        $htmlExportController = new CallsHtmlExportController();
        return $htmlExportController->exportHtml($request, $id);
    }

    public function exportCsv(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user has permission (for portal users)
        if ($user instanceof PortalUser && !$user->hasPermission('calls.export')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Get selected fields
        $selectedFields = [];
        if ($request->has('fields')) {
            $selectedFields = explode(',', $request->get('fields'));
        }
        
        // Default fields if none selected
        if (empty($selectedFields)) {
            $selectedFields = ['date', 'time', 'phone_number', 'duration', 'status', 'branch'];
        }

        $company = $user->company;
        
        // Build query with filters
        $query = Call::where('company_id', $company->id)
            ->with(['branch', 'callPortalData.assignedTo', 'customer']);

        // Apply filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('from_number', 'like', "%{$search}%")
                  ->orWhere('transcript', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Get calls
        $calls = $query->orderBy('created_at', 'desc')->get();

        // Check if user should see costs
        $showCosts = $user instanceof PortalUser ? $user->hasPermission('billing.view') : true;

        // Define field mappings
        $fieldMappings = [
            'date' => ['label' => 'Datum', 'getValue' => fn($call) => $call->created_at->format('d.m.Y')],
            'time' => ['label' => 'Uhrzeit', 'getValue' => fn($call) => $call->created_at->format('H:i')],
            'phone_number' => ['label' => 'Telefonnummer', 'getValue' => fn($call) => $call->from_number ?? ''],
            'customer_name' => ['label' => 'Kunde', 'getValue' => fn($call) => $call->extracted_name ?? ($call->customer ? $call->customer->name : 'Unbekannt')],
            'customer_email' => ['label' => 'E-Mail', 'getValue' => fn($call) => $call->customer ? $call->customer->email : ''],
            'customer_company' => ['label' => 'Firma', 'getValue' => fn($call) => (isset($call->metadata['customer_data']['company']) ? $call->metadata['customer_data']['company'] : null) ?? ($call->customer ? $call->customer->company_name : '')],
            'customer_number' => ['label' => 'Kundennummer', 'getValue' => fn($call) => $call->customer ? $call->customer->customer_number : ''],
            'summary' => ['label' => 'Zusammenfassung', 'getValue' => fn($call) => $call->summary ?? ''],
            'reason' => ['label' => 'Anliegen', 'getValue' => fn($call) => $call->reason_for_visit ?? $call->summary ?? ''],
            'notes' => ['label' => 'Notizen', 'getValue' => fn($call) => $call->notes->pluck('content')->join('; ')],
            'transcript' => ['label' => 'Transkript', 'getValue' => fn($call) => $call->transcript ?? ''],
            'duration' => ['label' => 'Dauer', 'getValue' => fn($call) => gmdate('H:i:s', $call->duration_sec ?? 0)],
            'status' => ['label' => 'Status', 'getValue' => fn($call) => optional($call->callPortalData)->status ?? 'new'],
            'assigned_to' => ['label' => 'Zugewiesen an', 'getValue' => fn($call) => optional($call->callPortalData)->assignedTo->name ?? 'Nicht zugewiesen'],
            'branch' => ['label' => 'Filiale', 'getValue' => fn($call) => optional($call->branch)->name ?? ''],
            'urgency' => ['label' => 'Dringlichkeit', 'getValue' => fn($call) => $call->urgency_level ?? (isset($call->metadata['customer_data']['urgency']) ? $call->metadata['customer_data']['urgency'] : '')],
            'cost' => ['label' => 'Kosten', 'getValue' => fn($call) => $showCosts && $call->duration_sec ? number_format($call->total_cost ?? 0, 2, ',', '.') . ' €' : ''],
            'price_per_minute' => ['label' => 'Preis pro Minute', 'getValue' => fn($call) => $showCosts ? number_format($call->price_per_minute ?? 0.39, 2, ',', '.') . ' €' : ''],
        ];

        // Build CSV headers based on selected fields
        $headers = [];
        foreach ($selectedFields as $field) {
            if (isset($fieldMappings[$field])) {
                // Skip cost fields if user doesn't have permission
                if (in_array($field, ['cost', 'price_per_minute']) && !$showCosts) {
                    continue;
                }
                $headers[] = $fieldMappings[$field]['label'];
            }
        }

        // Prepare CSV data
        $csvData = [];
        $csvData[] = $headers;

        foreach ($calls as $call) {
            $row = [];
            foreach ($selectedFields as $field) {
                if (isset($fieldMappings[$field])) {
                    // Skip cost fields if user doesn't have permission
                    if (in_array($field, ['cost', 'price_per_minute']) && !$showCosts) {
                        continue;
                    }
                    $row[] = $fieldMappings[$field]['getValue']($call);
                }
            }
            $csvData[] = $row;
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
     * Send call summary email
     */
    public function sendSummary(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        $call = Call::where('company_id', $companyId)->findOrFail($id);
        
        // Check if user has permission (for portal users)
        if ($user instanceof PortalUser && !$user->hasPermission('calls.edit_all')) {
            return response()->json(['error' => 'Forbidden'], 403);
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
            SendCallSummaryJob::dispatch(
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
     * Export multiple calls with filters
     */
    public function exportBatch(Request $request)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user has permission (for portal users)
        if ($user instanceof PortalUser && !$user->hasPermission('calls.export')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'format' => 'nullable|in:csv,xlsx,json',
        ]);

        try {
            $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
            
            if (!$companyId) {
                return response()->json(['error' => 'Company not found'], 404);
            }
            
            $exportService = new CallExportService();
            
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
     * Get navigation info for previous/next calls
     */
    public function getNavigation(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companyId = $user->company_id ?? ($user->company ? $user->company->id : null);
        
        if (!$companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }
        
        // Get current call
        $currentCall = Call::where('company_id', $companyId)->findOrFail($id);
        
        // Build query for navigation - matching the index filter logic
        $baseQuery = Call::where('company_id', $companyId);
        
        // Apply filters from request to maintain context
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $baseQuery->where(function($q) use ($search) {
                $q->where('from_number', 'like', "%{$search}%")
                  ->orWhere('to_number', 'like', "%{$search}%")
                  ->orWhere('transcript', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('extracted_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $baseQuery->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('branch_id') && $request->branch_id) {
            $baseQuery->where('branch_id', $request->branch_id);
        }
        
        // Get sort order from request or use default
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Get previous call
        $previousQuery = clone $baseQuery;
        if ($sortOrder === 'desc') {
            $previousCall = $previousQuery
                ->where($sortBy, '>', $currentCall->$sortBy)
                ->orderBy($sortBy, 'asc')
                ->first();
        } else {
            $previousCall = $previousQuery
                ->where($sortBy, '<', $currentCall->$sortBy)
                ->orderBy($sortBy, 'desc')
                ->first();
        }
        
        // Get next call
        $nextQuery = clone $baseQuery;
        if ($sortOrder === 'desc') {
            $nextCall = $nextQuery
                ->where($sortBy, '<', $currentCall->$sortBy)
                ->orderBy($sortBy, 'desc')
                ->first();
        } else {
            $nextCall = $nextQuery
                ->where($sortBy, '>', $currentCall->$sortBy)
                ->orderBy($sortBy, 'asc')
                ->first();
        }
        
        // Get position in list
        $position = 1;
        $totalQuery = clone $baseQuery;
        if ($sortOrder === 'desc') {
            $position = $totalQuery->where($sortBy, '>', $currentCall->$sortBy)->count() + 1;
        } else {
            $position = $totalQuery->where($sortBy, '<', $currentCall->$sortBy)->count() + 1;
        }
        
        $total = $baseQuery->count();
        
        return response()->json([
            'navigation' => [
                'previous' => $previousCall ? [
                    'id' => $previousCall->id,
                    'from_number' => $previousCall->from_number,
                    'extracted_name' => $previousCall->extracted_name,
                    'created_at' => $previousCall->created_at->format('d.m.Y H:i'),
                ] : null,
                'next' => $nextCall ? [
                    'id' => $nextCall->id,
                    'from_number' => $nextCall->from_number,
                    'extracted_name' => $nextCall->extracted_name,
                    'created_at' => $nextCall->created_at->format('d.m.Y H:i'),
                ] : null,
                'position' => $position,
                'total' => $total,
            ],
        ]);
    }
    
    /**
     * Calculate average call duration only for calls with duration > 0
     */
    private function calculateAverageDuration($company)
    {
        $callsWithDuration = $company->calls()
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0);
            
        if (!$callsWithDuration->exists()) {
            return 0;
        }
        
        return round($callsWithDuration->avg('duration_sec'));
    }
}