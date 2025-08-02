<?php

namespace App\Services\MCP;

use App\Models\Call;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * MCP Service für intelligente Daten-Synchronisation
 * 
 * Nutzt die MCP-Server für:
 * - Anrufe von Retell.ai (RetellMCPServer)
 * - Termine von Cal.com (CalcomMCPServer)
 */
class SyncMCPService
{
    private RetellMCPServer $retellMCP;
    private CalcomMCPServer $calcomMCP;
    
    public function __construct(
        RetellMCPServer $retellMCP,
        CalcomMCPServer $calcomMCP
    ) {
        $this->retellMCP = $retellMCP;
        $this->calcomMCP = $calcomMCP;
    }
    
    /**
     * Synchronisiere Anrufe über MCP Server
     */
    public function syncCalls(array $filters = []): array
    {
        $defaultFilters = [
            'date_from' => now()->subDays(7),
            'date_to' => now(),
            'limit' => 100,
            'status' => null,
            'has_appointment' => null,
            'min_duration' => 0,
            'agent_id' => null,
            'skip_existing' => true,
        ];
        
        $filters = array_merge($defaultFilters, $filters);
        
        Log::info('MCP: Starting call sync via MCP Server', $filters);
        
        // Verwende MCP Server für Import
        $companyId = auth()->user()->company_id;
        
        $result = $this->retellMCP->importCalls([
            'company_id' => $companyId,
            'days' => $filters['date_from']->diffInDays(now()),
            'limit' => $filters['limit']
        ]);
        
        if (isset($result['error'])) {
            Log::error('MCP: Call sync failed', $result);
            return [
                'total' => 0,
                'new' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 1,
                'error_message' => $result['error']
            ];
        }
        
        return [
            'total' => $result['imported'] + $result['skipped'],
            'new' => $result['imported'],
            'updated' => 0,
            'skipped' => $result['skipped'],
            'errors' => count($result['errors'] ?? [])
        ];
    }
    
    /**
     * Synchronisiere Termine über MCP Server
     */
    public function syncAppointments(array $filters = []): array
    {
        $defaultFilters = [
            'date_from' => now()->subDays(30),
            'date_to' => now()->addDays(90),
            'limit' => 500,
            'status' => null,
            'event_type_id' => null,
            'skip_existing' => true,
            'include_cancelled' => false,
        ];
        
        $filters = array_merge($defaultFilters, $filters);
        
        Log::info('MCP: Starting appointment sync via MCP Server', $filters);
        
        $companyId = auth()->user()->company_id;
        
        // Verwende MCP Server für Bookings
        $result = $this->calcomMCP->getBookings([
            'company_id' => $companyId,
            'date_from' => $filters['date_from']->format('Y-m-d'),
            'date_to' => $filters['date_to']->format('Y-m-d'),
            'status' => $filters['status']
        ]);
        
        if (isset($result['error'])) {
            Log::error('MCP: Appointment sync failed', $result);
            return [
                'total' => 0,
                'new' => 0,
                'updated' => 0,
                'skipped' => 0,
                'cancelled' => 0,
                'errors' => 1,
                'error_message' => $result['error']
            ];
        }
        
        // Process bookings
        $stats = [
            'total' => 0,
            'new' => 0,
            'updated' => 0,
            'skipped' => 0,
            'cancelled' => 0,
            'errors' => 0
        ];
        
        $bookings = $result['bookings'] ?? [];
        
        foreach ($bookings as $booking) {
            if ($stats['total'] >= $filters['limit']) {
                break;
            }
            
            $stats['total']++;
            
            // Check if appointment exists
            $exists = Appointment::where('calcom_booking_id', $booking['id'] ?? null)->exists();
            
            if ($exists && $filters['skip_existing']) {
                $stats['skipped']++;
                continue;
            }
            
            // Create or update appointment
            try {
                $appointment = Appointment::updateOrCreate(
                    ['calcom_booking_id' => $booking['id']],
                    [
                        'company_id' => $companyId,
                        'start_time' => Carbon::parse($booking['startTime']),
                        'end_time' => Carbon::parse($booking['endTime']),
                        'status' => $booking['status'] ?? 'scheduled',
                        'title' => $booking['title'] ?? 'Termin',
                        'attendee_name' => $booking['attendees'][0]['name'] ?? null,
                        'attendee_email' => $booking['attendees'][0]['email'] ?? null,
                    ]
                );
                
                if ($appointment->wasRecentlyCreated) {
                    $stats['new']++;
                } else {
                    $stats['updated']++;
                }
                
                if ($booking['status'] === 'CANCELLED') {
                    $stats['cancelled']++;
                }
                
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('MCP: Error processing appointment', [
                    'booking_id' => $booking['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('MCP: Appointment sync completed', $stats);
        
        return $stats;
    }
    
    /**
     * Vorschau für Synchronisation
     */
    public function previewSync(string $type, array $filters): array
    {
        if ($type === 'calls') {
            // Hole aktuelle Statistiken
            $companyId = auth()->user()->company_id;
            
            $existingCount = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [
                    $filters['date_from'] ?? now()->subDays(7),
                    $filters['date_to'] ?? now()
                ])
                ->count();
            
            return [
                'type' => 'calls',
                'existing_records' => $existingCount,
                'would_sync' => 'Unknown - depends on Retell API',
                'filters' => $filters
            ];
        }
        
        if ($type === 'appointments') {
            $companyId = auth()->user()->company_id;
            
            $existingCount = Appointment::where('company_id', $companyId)
                ->whereBetween('start_time', [
                    $filters['date_from'] ?? now()->subDays(30),
                    $filters['date_to'] ?? now()->addDays(90)
                ])
                ->count();
            
            return [
                'type' => 'appointments',
                'existing_records' => $existingCount,
                'would_sync' => 'Unknown - depends on Cal.com API',
                'filters' => $filters
            ];
        }
        
        return ['error' => 'Invalid type'];
    }
    
    /**
     * Empfehlungen für Synchronisation
     */
    public function getSyncRecommendations(): array
    {
        $companyId = auth()->user()->company_id;
        $recommendations = [];
        
        // Prüfe letzte Call-Sync
        $lastCallSync = Cache::get("last_call_sync_{$companyId}");
        $lastCallCount = Call::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        if (!$lastCallSync || $lastCallSync->diffInHours(now()) > 6) {
            $recommendations[] = [
                'type' => 'calls',
                'priority' => 'high',
                'reason' => 'Anrufe wurden seit über 6 Stunden nicht synchronisiert',
                'message' => 'Anrufe wurden seit über 6 Stunden nicht synchronisiert',
                'action' => 'sync_last_24h',
                'suggested_filters' => [
                    'date_from' => now()->subDay(),
                    'limit' => 100
                ]
            ];
        }
        
        // Prüfe letzte Appointment-Sync
        $lastAppointmentSync = Cache::get("last_appointment_sync_{$companyId}");
        
        if (!$lastAppointmentSync || $lastAppointmentSync->diffInDays(now()) > 1) {
            $recommendations[] = [
                'type' => 'appointments',
                'priority' => 'medium',
                'reason' => 'Termine wurden seit über einem Tag nicht synchronisiert',
                'message' => 'Termine wurden seit über einem Tag nicht synchronisiert',
                'action' => 'sync_upcoming',
                'suggested_filters' => [
                    'date_from' => now(),
                    'date_to' => now()->addDays(30),
                    'limit' => 200
                ]
            ];
        }
        
        // Prüfe auf fehlende Verknüpfungen
        $unlinkedCalls = Call::where('company_id', $companyId)
            ->whereNull('appointment_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
            
        if ($unlinkedCalls > 10) {
            $recommendations[] = [
                'type' => 'analysis',
                'priority' => 'low',
                'reason' => "{$unlinkedCalls} Anrufe ohne Terminverknüpfung in den letzten 7 Tagen",
                'message' => "{$unlinkedCalls} Anrufe ohne Terminverknüpfung in den letzten 7 Tagen",
                'action' => 'analyze_unlinked'
            ];
        }
        
        return $recommendations;
    }
}