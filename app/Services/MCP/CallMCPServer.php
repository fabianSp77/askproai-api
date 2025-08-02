<?php

namespace App\Services\MCP;

use App\Models\Call;
use App\Models\CallPortalData;
use App\Models\Customer;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CallMCPServer
{
    protected string $name = 'Call Management MCP Server';
    protected string $version = '1.0.0';

    /**
     * Get available tools for this MCP server.
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'listCalls',
                'description' => 'List calls with filtering and pagination',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'page' => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 20],
                        'status' => ['type' => 'string', 'enum' => ['new', 'in_progress', 'completed', 'requires_action', 'callback_scheduled']],
                        'date_from' => ['type' => 'string', 'format' => 'date'],
                        'date_to' => ['type' => 'string', 'format' => 'date'],
                        'search' => ['type' => 'string'],
                        'branch_id' => ['type' => 'integer'],
                        'assigned_to' => ['type' => 'integer']
                    ]
                ]
            ],
            [
                'name' => 'getCall',
                'description' => 'Get detailed information about a specific call',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string', 'required' => true]
                    ],
                    'required' => ['call_id']
                ]
            ],
            [
                'name' => 'updateCallStatus',
                'description' => 'Update the status of a call',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string', 'required' => true],
                        'status' => ['type' => 'string', 'required' => true, 'enum' => ['new', 'in_progress', 'completed', 'requires_action', 'callback_scheduled']],
                        'notes' => ['type' => 'string']
                    ],
                    'required' => ['call_id', 'status']
                ]
            ],
            [
                'name' => 'assignCall',
                'description' => 'Assign a call to a team member',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string', 'required' => true],
                        'user_id' => ['type' => 'integer', 'required' => true],
                        'notes' => ['type' => 'string']
                    ],
                    'required' => ['call_id', 'user_id']
                ]
            ],
            [
                'name' => 'addCallNote',
                'description' => 'Add a note to a call',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string', 'required' => true],
                        'note' => ['type' => 'string', 'required' => true],
                        'is_internal' => ['type' => 'boolean', 'default' => true]
                    ],
                    'required' => ['call_id', 'note']
                ]
            ],
            [
                'name' => 'scheduleCallback',
                'description' => 'Schedule a callback for a call',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'call_id' => ['type' => 'string', 'required' => true],
                        'callback_at' => ['type' => 'string', 'format' => 'date-time', 'required' => true],
                        'notes' => ['type' => 'string']
                    ],
                    'required' => ['call_id', 'callback_at']
                ]
            ],
            [
                'name' => 'getCallStats',
                'description' => 'Get call statistics for the current user or company',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'enum' => ['today', 'week', 'month'], 'default' => 'today'],
                        'branch_id' => ['type' => 'integer']
                    ]
                ]
            ],
            [
                'name' => 'exportCalls',
                'description' => 'Export calls data in various formats',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'format' => ['type' => 'string', 'enum' => ['csv', 'excel', 'pdf'], 'default' => 'csv'],
                        'call_ids' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'filters' => ['type' => 'object']
                    ]
                ]
            ]
        ];
    }

    /**
     * Handle tool execution.
     */
    public function executeTool(string $name, array $arguments): array
    {
        try {
            switch ($name) {
                case 'listCalls':
                    return $this->listCalls($arguments);
                case 'getCall':
                    return $this->getCall($arguments);
                case 'updateCallStatus':
                    return $this->updateCallStatus($arguments);
                case 'assignCall':
                    return $this->assignCall($arguments);
                case 'addCallNote':
                    return $this->addCallNote($arguments);
                case 'scheduleCallback':
                    return $this->scheduleCallback($arguments);
                case 'getCallStats':
                    return $this->getCallStats($arguments);
                case 'exportCalls':
                    return $this->exportCalls($arguments);
                default:
                    throw new \Exception("Unknown tool: {$name}");
            }
        } catch (\Exception $e) {
            Log::error("CallMCPServer error in {$name}", [
                'error' => $e->getMessage(),
                'arguments' => $arguments
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List calls with filtering and pagination.
     */
    protected function listCalls(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $query = Call::with(['customer', 'branch', 'appointment', 'callPortalData']);

        // Apply company filter
        $query->where('company_id', $user->company_id);

        // Get company phone numbers for filtering
        $companyPhoneNumbers = PhoneNumber::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
        
        $query->whereIn('to_number', $companyPhoneNumbers);

        // Apply filters
        if (!empty($params['status'])) {
            $query->whereHas('callPortalData', function ($q) use ($params) {
                $q->where('status', $params['status']);
            });
        }

        if (!empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }

        if (!empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('from_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($params['branch_id'])) {
            $query->where('branch_id', $params['branch_id']);
        }

        if (!empty($params['assigned_to'])) {
            $query->whereHas('callPortalData', function ($q) use ($params) {
                $q->where('assigned_to', $params['assigned_to']);
            });
        }

        // Apply permission-based filtering
        if ($user->hasPermission('calls.view_own') && !$user->hasPermission('calls.view_all')) {
            $query->whereHas('callPortalData', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }

        // Paginate
        $perPage = $params['per_page'] ?? 20;
        $calls = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage);

        // Transform calls data
        $transformedCalls = $calls->map(function ($call) {
            $portalData = $call->callPortalData;
            return [
                'id' => $call->id,
                'phone_number' => $call->phone_number,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'duration_sec' => $call->duration_sec,
                'status' => $portalData->status ?? 'new',
                'priority' => $portalData->priority ?? 'medium',
                'assigned_to' => $portalData->assigned_to ?? null,
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
                    'starts_at' => $call->appointment->starts_at
                ] : null,
                'created_at' => $call->created_at->toIso8601String(),
                'transcript_summary' => $call->transcript_summary,
                'callback_scheduled_at' => $portalData->callback_scheduled_at ?? null,
                'notes_count' => $portalData->notes_count ?? 0
            ];
        });

        return [
            'success' => true,
            'data' => $transformedCalls,
            'meta' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
                'from' => $calls->firstItem(),
                'to' => $calls->lastItem()
            ]
        ];
    }

    /**
     * Get detailed information about a specific call.
     */
    protected function getCall(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $call = Call::with(['customer', 'branch', 'appointment', 'callPortalData'])
                    ->where('company_id', $user->company_id)
                    ->where('id', $params['call_id'])
                    ->first();

        if (!$call) {
            throw new \Exception('Call not found');
        }

        // Check permissions
        $portalData = $call->callPortalData;
        if ($user->hasPermission('calls.view_own') && !$user->hasPermission('calls.view_all')) {
            if ($portalData && $portalData->assigned_to !== $user->id) {
                throw new \Exception('Unauthorized to view this call');
            }
        }

        return [
            'success' => true,
            'data' => [
                'id' => $call->id,
                'phone_number' => $call->phone_number,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'duration_sec' => $call->duration_sec,
                'status' => $portalData->status ?? 'new',
                'priority' => $portalData->priority ?? 'medium',
                'assigned_to' => $portalData->assigned_to ?? null,
                'customer' => $call->customer,
                'branch' => $call->branch,
                'appointment' => $call->appointment,
                'transcript' => $call->transcript,
                'transcript_summary' => $call->transcript_summary,
                'recording_url' => $call->recording_url,
                'portal_data' => $portalData,
                'created_at' => $call->created_at->toIso8601String(),
                'updated_at' => $call->updated_at->toIso8601String()
            ]
        ];
    }

    /**
     * Update the status of a call.
     */
    protected function updateCallStatus(array $params): array
    {
        $user = Auth::guard('portal')->user();
        
        DB::beginTransaction();
        try {
            $call = Call::where('company_id', $user->company_id)
                        ->where('id', $params['call_id'])
                        ->firstOrFail();

            // Get or create portal data
            $portalData = CallPortalData::firstOrCreate(
                ['call_id' => $call->id],
                [
                    'status' => 'new',
                    'priority' => 'medium',
                    'created_by' => $user->id
                ]
            );

            // Update status
            $oldStatus = $portalData->status;
            $portalData->status = $params['status'];
            $portalData->updated_by = $user->id;
            $portalData->save();

            // Add history entry
            $history = json_decode($portalData->status_history ?? '[]', true);
            $history[] = [
                'from' => $oldStatus,
                'to' => $params['status'],
                'changed_by' => $user->id,
                'changed_at' => now()->toIso8601String(),
                'notes' => $params['notes'] ?? null
            ];
            $portalData->status_history = json_encode($history);
            $portalData->save();

            // Add note if provided
            if (!empty($params['notes'])) {
                $this->addCallNote([
                    'call_id' => $params['call_id'],
                    'note' => "Status changed from {$oldStatus} to {$params['status']}: {$params['notes']}",
                    'is_internal' => true
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'call_id' => $call->id,
                    'status' => $portalData->status,
                    'previous_status' => $oldStatus
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign a call to a team member.
     */
    protected function assignCall(array $params): array
    {
        $user = Auth::guard('portal')->user();
        
        // Verify the target user exists and belongs to the same company
        $targetUser = \App\Models\User::where('id', $params['user_id'])
                                      ->where('company_id', $user->company_id)
                                      ->firstOrFail();

        $call = Call::where('company_id', $user->company_id)
                    ->where('id', $params['call_id'])
                    ->firstOrFail();

        // Get or create portal data
        $portalData = CallPortalData::firstOrCreate(
            ['call_id' => $call->id],
            [
                'status' => 'new',
                'priority' => 'medium',
                'created_by' => $user->id
            ]
        );

        $previousAssignee = $portalData->assigned_to;
        $portalData->assigned_to = $params['user_id'];
        $portalData->assigned_by = $user->id;
        $portalData->assigned_at = now();
        $portalData->save();

        // Add assignment note
        $note = "Call assigned to {$targetUser->name}";
        if ($previousAssignee) {
            $prevUser = \App\Models\User::find($previousAssignee);
            $note .= " (previously assigned to " . ($prevUser ? $prevUser->name : 'Unknown') . ")";
        }
        if (!empty($params['notes'])) {
            $note .= ": {$params['notes']}";
        }

        $this->addCallNote([
            'call_id' => $params['call_id'],
            'note' => $note,
            'is_internal' => true
        ]);

        return [
            'success' => true,
            'data' => [
                'call_id' => $call->id,
                'assigned_to' => $targetUser->id,
                'assigned_to_name' => $targetUser->name,
                'previous_assignee' => $previousAssignee
            ]
        ];
    }

    /**
     * Add a note to a call.
     */
    protected function addCallNote(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $call = Call::where('company_id', $user->company_id)
                    ->where('id', $params['call_id'])
                    ->firstOrFail();

        // Get or create portal data
        $portalData = CallPortalData::firstOrCreate(
            ['call_id' => $call->id],
            [
                'status' => 'new',
                'priority' => 'medium',
                'created_by' => $user->id
            ]
        );

        // Add note to notes array
        $notes = json_decode($portalData->notes ?? '[]', true);
        $notes[] = [
            'id' => uniqid(),
            'text' => $params['note'],
            'is_internal' => $params['is_internal'] ?? true,
            'created_by' => $user->id,
            'created_by_name' => $user->name,
            'created_at' => now()->toIso8601String()
        ];
        
        $portalData->notes = json_encode($notes);
        $portalData->notes_count = count($notes);
        
        // Update internal notes if this is internal
        if ($params['is_internal'] ?? true) {
            $internalNotes = $portalData->internal_notes ?? '';
            $portalData->internal_notes = $internalNotes . "\n[" . now()->format('d.m.Y H:i') . " - {$user->name}] " . $params['note'];
        }
        
        $portalData->save();

        return [
            'success' => true,
            'data' => [
                'call_id' => $call->id,
                'note_id' => end($notes)['id'],
                'notes_count' => count($notes)
            ]
        ];
    }

    /**
     * Schedule a callback for a call.
     */
    protected function scheduleCallback(array $params): array
    {
        $user = Auth::guard('portal')->user();
        
        DB::beginTransaction();
        try {
            $call = Call::where('company_id', $user->company_id)
                        ->where('id', $params['call_id'])
                        ->firstOrFail();

            // Get or create portal data
            $portalData = CallPortalData::firstOrCreate(
                ['call_id' => $call->id],
                [
                    'status' => 'new',
                    'priority' => 'medium',
                    'created_by' => $user->id
                ]
            );

            // Update callback schedule
            $portalData->callback_scheduled_at = Carbon::parse($params['callback_at']);
            $portalData->status = 'callback_scheduled';
            $portalData->next_action_date = Carbon::parse($params['callback_at']);
            $portalData->save();

            // Add note about callback
            $note = "Callback scheduled for " . Carbon::parse($params['callback_at'])->format('d.m.Y H:i');
            if (!empty($params['notes'])) {
                $note .= ": {$params['notes']}";
            }

            $this->addCallNote([
                'call_id' => $params['call_id'],
                'note' => $note,
                'is_internal' => false
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'call_id' => $call->id,
                    'callback_scheduled_at' => $portalData->callback_scheduled_at->toIso8601String(),
                    'status' => $portalData->status
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get call statistics.
     */
    protected function getCallStats(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $period = $params['period'] ?? 'today';
        
        // Get company phone numbers
        $companyPhoneNumbers = PhoneNumber::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();

        $query = Call::where('company_id', $user->company_id)
                     ->whereIn('to_number', $companyPhoneNumbers);

        // Apply period filter
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereDate('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->whereDate('created_at', '>=', now()->startOfMonth());
                break;
        }

        // Apply branch filter if provided
        if (!empty($params['branch_id'])) {
            $query->where('branch_id', $params['branch_id']);
        }

        // Get total calls
        $totalCalls = $query->count();

        // Get calls by status
        $callsByStatus = DB::table('calls')
            ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
            ->where('calls.company_id', $user->company_id)
            ->whereIn('calls.to_number', $companyPhoneNumbers)
            ->when($period === 'today', function ($q) {
                $q->whereDate('calls.created_at', today());
            })
            ->when($period === 'week', function ($q) {
                $q->whereDate('calls.created_at', '>=', now()->startOfWeek());
            })
            ->when($period === 'month', function ($q) {
                $q->whereDate('calls.created_at', '>=', now()->startOfMonth());
            })
            ->groupBy('call_portal_data.status')
            ->select('call_portal_data.status', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'status')
            ->toArray();

        // Calculate average duration
        $avgDuration = $query->avg('duration_sec') ?? 0;

        // Get trend (compare with previous period)
        $previousPeriodQuery = Call::where('company_id', $user->company_id)
                                   ->whereIn('to_number', $companyPhoneNumbers);
        
        switch ($period) {
            case 'today':
                $previousPeriodQuery->whereDate('created_at', today()->subDay());
                break;
            case 'week':
                $previousPeriodQuery->whereBetween('created_at', [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek()
                ]);
                break;
            case 'month':
                $previousPeriodQuery->whereBetween('created_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
                break;
        }

        $previousTotal = $previousPeriodQuery->count();
        $trend = $previousTotal > 0 ? round((($totalCalls - $previousTotal) / $previousTotal) * 100, 1) : 0;

        return [
            'success' => true,
            'data' => [
                'period' => $period,
                'total_calls' => $totalCalls,
                'calls_by_status' => $callsByStatus,
                'average_duration_sec' => round($avgDuration),
                'trend_percentage' => $trend,
                'new_calls' => $callsByStatus['new'] ?? 0,
                'in_progress' => $callsByStatus['in_progress'] ?? 0,
                'completed' => $callsByStatus['completed'] ?? 0,
                'requires_action' => $callsByStatus['requires_action'] ?? 0,
                'callbacks_scheduled' => $callsByStatus['callback_scheduled'] ?? 0
            ]
        ];
    }

    /**
     * Export calls data.
     */
    protected function exportCalls(array $params): array
    {
        $user = Auth::guard('portal')->user();
        $format = $params['format'] ?? 'csv';
        
        // Build query
        $query = Call::with(['customer', 'branch', 'appointment', 'callPortalData'])
                     ->where('company_id', $user->company_id);

        // Apply filters if provided
        if (!empty($params['call_ids'])) {
            $query->whereIn('id', $params['call_ids']);
        } elseif (!empty($params['filters'])) {
            // Apply the same filters as listCalls
            $this->applyFilters($query, $params['filters']);
        }

        $calls = $query->get();

        // Transform data for export
        $exportData = $calls->map(function ($call) {
            $portalData = $call->callPortalData;
            return [
                'ID' => $call->id,
                'Datum' => $call->created_at->format('d.m.Y H:i'),
                'Telefonnummer' => $call->phone_number,
                'Kunde' => $call->customer->name ?? 'Unbekannt',
                'Dauer (Sek)' => $call->duration_sec,
                'Status' => $portalData->status ?? 'new',
                'Priorität' => $portalData->priority ?? 'medium',
                'Zugewiesen an' => $portalData->assignedTo->name ?? '-',
                'Filiale' => $call->branch->name ?? '-',
                'Zusammenfassung' => $call->transcript_summary ?? '-'
            ];
        });

        // Generate export file based on format
        switch ($format) {
            case 'csv':
                $fileName = 'calls_export_' . now()->format('Y-m-d_His') . '.csv';
                $filePath = storage_path('app/exports/' . $fileName);
                
                // Create CSV
                $handle = fopen($filePath, 'w');
                fputcsv($handle, array_keys($exportData->first()->toArray() ?? []));
                foreach ($exportData as $row) {
                    fputcsv($handle, $row->toArray());
                }
                fclose($handle);
                
                return [
                    'success' => true,
                    'data' => [
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'download_url' => url('/business/api/calls/download-export/' . $fileName),
                        'format' => 'csv',
                        'rows_count' => $exportData->count()
                    ]
                ];
                
            case 'excel':
                // Excel export would require additional package like PhpSpreadsheet
                throw new \Exception('Excel export not yet implemented');
                
            case 'pdf':
                // PDF export would require additional package like DomPDF
                throw new \Exception('PDF export not yet implemented');
                
            default:
                throw new \Exception('Unsupported export format');
        }
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->whereHas('callPortalData', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
    }
}