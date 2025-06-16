<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CalcomSyncService;
use App\Services\AvailabilityChecker;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventManagementController extends Controller
{
    private $calcomSyncService;
    private $availabilityChecker;
    
    public function __construct(CalcomSyncService $calcomSyncService, AvailabilityChecker $availabilityChecker)
    {
        $this->calcomSyncService = $calcomSyncService;
        $this->availabilityChecker = $availabilityChecker;
    }
    
    /**
     * Synchronisiere Event-Types für ein Unternehmen
     */
    public function syncEventTypes($companyId)
    {
        try {
            $result = $this->calcomSyncService->syncEventTypesForCompany($companyId);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Event type sync failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Synchronisiere Team-Mitglieder für ein Unternehmen
     */
    public function syncTeamMembers($companyId)
    {
        try {
            $result = $this->calcomSyncService->syncTeamMembers($companyId);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Team sync failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Prüfe Verfügbarkeit
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'event_type_id' => 'required|exists:calcom_event_types,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
            'staff_id' => 'nullable|exists:staff,id'
        ]);
        
        try {
            $availability = $this->calcomSyncService->checkAvailability(
                $request->event_type_id,
                $request->date_from,
                $request->date_to,
                $request->staff_id
            );
            
            return response()->json($availability);
            
        } catch (\Exception $e) {
            Log::error('Availability check failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'available' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Hole Event-Types für ein Unternehmen
     */
    public function getEventTypes($companyId, Request $request)
    {
        $query = CalcomEventType::where('company_id', $companyId);
        
        // Optional Branch filter
        if ($request->has('branch_id')) {
            $query->where(function($q) use ($request) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $request->branch_id);
            });
        }
        
        // Nur aktive
        if ($request->get('active_only', true)) {
            $query->where('is_active', true);
        }
        
        $eventTypes = $query->with(['assignedStaff' => function($q) {
            $q->where('active', true)->where('is_bookable', true);
        }])->get();
        
        return response()->json([
            'success' => true,
            'event_types' => $eventTypes->map(function($eventType) {
                return [
                    'id' => $eventType->id,
                    'name' => $eventType->name,
                    'slug' => $eventType->slug,
                    'duration_minutes' => $eventType->duration_minutes,
                    'price' => $eventType->price,
                    'description' => $eventType->description,
                    'is_team_event' => $eventType->is_team_event,
                    'assigned_staff_count' => $eventType->assignedStaff->count(),
                    'assigned_staff' => $eventType->assignedStaff->map(function($staff) {
                        return [
                            'id' => $staff->id,
                            'name' => $staff->name,
                            'email' => $staff->email
                        ];
                    })
                ];
            })
        ]);
    }
    
    /**
     * Verwalte Mitarbeiter-Event-Type Zuordnungen
     */
    public function manageStaffEventAssignments(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.staff_id' => 'required|exists:staff,id',
            'assignments.*.event_type_id' => 'required|exists:calcom_event_types,id',
            'assignments.*.action' => 'required|in:add,remove,update',
            'assignments.*.custom_duration' => 'nullable|integer|min:5',
            'assignments.*.custom_price' => 'nullable|numeric|min:0'
        ]);
        
        DB::beginTransaction();
        
        try {
            $results = [];
            
            foreach ($request->assignments as $assignment) {
                $staffId = $assignment['staff_id'];
                $eventTypeId = $assignment['event_type_id'];
                $action = $assignment['action'];
                
                switch ($action) {
                    case 'add':
                        DB::table('staff_event_types')->insertOrIgnore([
                            'staff_id' => $staffId,
                            'event_type_id' => $eventTypeId,
                            'custom_duration' => $assignment['custom_duration'] ?? null,
                            'custom_price' => $assignment['custom_price'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $results[] = ['staff_id' => $staffId, 'event_type_id' => $eventTypeId, 'status' => 'added'];
                        break;
                        
                    case 'remove':
                        DB::table('staff_event_types')
                            ->where('staff_id', $staffId)
                            ->where('event_type_id', $eventTypeId)
                            ->delete();
                        $results[] = ['staff_id' => $staffId, 'event_type_id' => $eventTypeId, 'status' => 'removed'];
                        break;
                        
                    case 'update':
                        DB::table('staff_event_types')
                            ->where('staff_id', $staffId)
                            ->where('event_type_id', $eventTypeId)
                            ->update([
                                'custom_duration' => $assignment['custom_duration'] ?? null,
                                'custom_price' => $assignment['custom_price'] ?? null,
                                'updated_at' => now()
                            ]);
                        $results[] = ['staff_id' => $staffId, 'event_type_id' => $eventTypeId, 'status' => 'updated'];
                        break;
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Staff assignment update failed', [
                'error' => $e->getMessage(),
                'assignments' => $request->assignments
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Hole Staff-Event-Type Matrix für ein Unternehmen
     */
    public function getStaffEventMatrix($companyId)
    {
        $staff = Staff::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        $eventTypes = CalcomEventType::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        // Hole alle Zuordnungen
        $assignments = DB::table('staff_event_types')
            ->whereIn('staff_id', $staff->pluck('id'))
            ->whereIn('event_type_id', $eventTypes->pluck('id'))
            ->get()
            ->keyBy(function($item) {
                return $item->staff_id . '-' . $item->event_type_id;
            });
        
        // Baue Matrix
        $matrix = [
            'staff' => $staff->map(function($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'email' => $s->email,
                    'branch' => $s->branch ? $s->branch->name : null
                ];
            }),
            'event_types' => $eventTypes->map(function($et) {
                return [
                    'id' => $et->id,
                    'name' => $et->name,
                    'duration' => $et->duration_minutes,
                    'price' => $et->price
                ];
            }),
            'assignments' => []
        ];
        
        // Fülle Zuordnungen
        foreach ($staff as $s) {
            foreach ($eventTypes as $et) {
                $key = $s->id . '-' . $et->id;
                $assignment = $assignments->get($key);
                
                $matrix['assignments'][] = [
                    'staff_id' => $s->id,
                    'event_type_id' => $et->id,
                    'assigned' => !is_null($assignment),
                    'custom_duration' => $assignment->custom_duration ?? null,
                    'custom_price' => $assignment->custom_price ?? null,
                    'is_primary' => $assignment->is_primary ?? false
                ];
            }
        }
        
        return response()->json($matrix);
    }
}