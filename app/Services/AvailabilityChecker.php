<?php

namespace App\Services;

use App\Models\CalcomEventType;
use App\Models\Staff;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AvailabilityChecker
{
    private $calcomSyncService;
    
    public function __construct(CalcomSyncService $calcomSyncService)
    {
        $this->calcomSyncService = $calcomSyncService;
    }
    
    /**
     * Parse eine Anfrage und prüfe Verfügbarkeiten intelligent
     */
    public function checkAvailabilityFromRequest($request)
    {
        // Parse die Anfrage
        $parsed = $this->parseRequest($request);
        
        // Wenn kein Event-Type gefunden wurde, verwende Standard
        if (!$parsed['event_type_id']) {
            $parsed['event_type_id'] = $this->getDefaultEventTypeId($parsed['company_id']);
        }
        
        // Prüfe Verfügbarkeit
        return $this->checkAvailability(
            $parsed['event_type_id'],
            $parsed['date_from'],
            $parsed['date_to'],
            $parsed['staff_id'],
            $parsed['branch_id']
        );
    }
    
    /**
     * Prüfe Verfügbarkeit mit allen Business-Regeln
     */
    public function checkAvailability($eventTypeId, $dateFrom, $dateTo, $staffId = null, $branchId = null)
    {
        $eventType = CalcomEventType::findOrFail($eventTypeId);
        
        // Berücksichtige Branch-Filter wenn angegeben
        if ($branchId) {
            // Prüfe ob Event-Type für diese Branch verfügbar ist
            if ($eventType->branch_id && $eventType->branch_id != $branchId) {
                return [
                    'available' => false,
                    'message' => 'Service not available at this branch',
                    'slots' => []
                ];
            }
        }
        
        // Delegiere an CalcomSyncService
        $availability = $this->calcomSyncService->checkAvailability(
            $eventTypeId,
            $dateFrom,
            $dateTo,
            $staffId
        );
        
        // Erweitere mit zusätzlichen Business-Informationen
        if ($availability['available']) {
            $availability['event_type'] = [
                'id' => $eventType->id,
                'name' => $eventType->name,
                'duration' => $eventType->duration_minutes,
                'price' => $eventType->price
            ];
            
            // Füge Branch-Info hinzu wenn relevant
            if ($eventType->branch_id) {
                $branch = Branch::find($eventType->branch_id);
                if ($branch) {
                    $availability['branch'] = [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'city' => $branch->city
                    ];
                }
            }
        }
        
        return $availability;
    }
    
    /**
     * Parse Anfrage-Daten
     */
    private function parseRequest($request)
    {
        $parsed = [
            'company_id' => null,
            'branch_id' => null,
            'event_type_id' => null,
            'staff_id' => null,
            'staff_name' => null,
            'service_name' => null,
            'date_from' => Carbon::now()->toIso8601String(),
            'date_to' => Carbon::now()->addDays(7)->toIso8601String()
        ];
        
        // Company ID
        if (isset($request['company_id'])) {
            $parsed['company_id'] = $request['company_id'];
        }
        
        // Branch ID
        if (isset($request['branch_id'])) {
            $parsed['branch_id'] = $request['branch_id'];
        }
        
        // Service/Event-Type
        if (isset($request['service_name'])) {
            $parsed['service_name'] = $request['service_name'];
            $parsed['event_type_id'] = $this->findEventTypeByName(
                $request['service_name'],
                $parsed['company_id']
            );
        } elseif (isset($request['event_type_id'])) {
            $parsed['event_type_id'] = $request['event_type_id'];
        }
        
        // Mitarbeiter
        if (isset($request['staff_name'])) {
            $parsed['staff_name'] = $request['staff_name'];
            $staff = $this->findStaffByName(
                $request['staff_name'],
                $parsed['company_id'],
                $parsed['branch_id']
            );
            if ($staff) {
                $parsed['staff_id'] = $staff->id;
            }
        } elseif (isset($request['staff_id'])) {
            $parsed['staff_id'] = $request['staff_id'];
        }
        
        // Datum
        if (isset($request['date_from'])) {
            $parsed['date_from'] = Carbon::parse($request['date_from'])->toIso8601String();
        }
        if (isset($request['date_to'])) {
            $parsed['date_to'] = Carbon::parse($request['date_to'])->toIso8601String();
        }
        
        return $parsed;
    }
    
    /**
     * Finde Event-Type nach Name
     */
    private function findEventTypeByName($name, $companyId)
    {
        $eventType = CalcomEventType::where('company_id', $companyId)
            ->where(function($query) use ($name) {
                $query->where('name', 'LIKE', '%' . $name . '%')
                      ->orWhere('slug', 'LIKE', '%' . $name . '%');
            })
            ->where('is_active', true)
            ->first();
        
        return $eventType ? $eventType->id : null;
    }
    
    /**
     * Finde Mitarbeiter nach Name
     */
    private function findStaffByName($name, $companyId, $branchId = null)
    {
        $query = Staff::where('company_id', $companyId)
            ->where('name', 'LIKE', '%' . $name . '%')
            ->where('active', true)
            ->where('is_bookable', true);
        
        if ($branchId) {
            // Prüfe ob Mitarbeiter in dieser Branch arbeitet
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('home_branch_id', $branchId)
                  ->orWhereHas('branches', function($bq) use ($branchId) {
                      $bq->where('branches.id', $branchId);
                  });
            });
        }
        
        return $query->first();
    }
    
    /**
     * Hole Standard Event-Type für Company
     */
    private function getDefaultEventTypeId($companyId)
    {
        $company = Company::find($companyId);
        
        if ($company && $company->default_event_type_id) {
            return $company->default_event_type_id;
        }
        
        // Fallback: Erster aktiver Event-Type
        $defaultEventType = CalcomEventType::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
        
        return $defaultEventType ? $defaultEventType->id : null;
    }
    
    /**
     * Finde nächsten verfügbaren Termin
     */
    public function findNextAvailableSlot($eventTypeId, $staffId = null, $branchId = null)
    {
        // Prüfe die nächsten 30 Tage
        $dateFrom = Carbon::now();
        $dateTo = Carbon::now()->addDays(30);
        
        $availability = $this->checkAvailability(
            $eventTypeId,
            $dateFrom->toIso8601String(),
            $dateTo->toIso8601String(),
            $staffId,
            $branchId
        );
        
        if (!empty($availability['slots'])) {
            // Gebe den ersten verfügbaren Slot zurück
            $nextSlot = $availability['slots'][0];
            $nextSlot['event_type'] = $availability['event_type'] ?? null;
            $nextSlot['branch'] = $availability['branch'] ?? null;
            
            return [
                'found' => true,
                'slot' => $nextSlot
            ];
        }
        
        return [
            'found' => false,
            'message' => 'No available slots found in the next 30 days'
        ];
    }
}