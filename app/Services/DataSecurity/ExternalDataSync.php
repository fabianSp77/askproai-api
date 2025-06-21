<?php

namespace App\Services\DataSecurity;

use App\Services\CalcomV2Service;
use App\Services\RetellService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExternalDataSync
{
    private $calcomService;
    private $retellService;
    
    public function __construct(CalcomV2Service $calcomService, RetellService $retellService)
    {
        $this->calcomService = $calcomService;
        $this->retellService = $retellService;
    }
    
    /**
     * Sync and backup all external data
     */
    public function syncAllExternalData()
    {
        $report = [
            'started_at' => now(),
            'calcom' => $this->syncCalcomData(),
            'retell' => $this->syncRetellData(),
            'finished_at' => now(),
        ];
        
        // Store sync report
        DB::table('external_sync_logs')->insert([
            'sync_type' => 'full',
            'report' => json_encode($report),
            'status' => 'completed',
            'created_at' => now(),
        ]);
        
        return $report;
    }
    
    /**
     * Sync Cal.com appointments and store locally
     */
    private function syncCalcomData()
    {
        $results = [
            'appointments' => 0,
            'event_types' => 0,
            'availability' => 0,
            'errors' => [],
        ];
        
        try {
            // Get all companies
            $companies = DB::table('companies')->where('is_active', true)->get();
            
            foreach ($companies as $company) {
                // Sync appointments
                $bookings = $this->calcomService->getBookings([
                    'from' => Carbon::now()->subMonths(6)->toIso8601String(),
                    'to' => Carbon::now()->addMonths(6)->toIso8601String(),
                ]);
                
                foreach ($bookings as $booking) {
                    // Store in local backup table
                    DB::table('calcom_bookings_backup')->updateOrInsert(
                        ['calcom_booking_id' => $booking['id']],
                        [
                            'company_id' => $company->id,
                            'booking_data' => json_encode($booking),
                            'starts_at' => $booking['startTime'],
                            'ends_at' => $booking['endTime'],
                            'status' => $booking['status'],
                            'attendee_email' => $booking['attendees'][0]['email'] ?? null,
                            'synced_at' => now(),
                        ]
                    );
                    $results['appointments']++;
                }
                
                // Sync event types
                $eventTypes = $this->calcomService->getEventTypes();
                foreach ($eventTypes as $eventType) {
                    DB::table('calcom_event_types_backup')->updateOrInsert(
                        ['calcom_event_type_id' => $eventType['id']],
                        [
                            'company_id' => $company->id,
                            'event_type_data' => json_encode($eventType),
                            'synced_at' => now(),
                        ]
                    );
                    $results['event_types']++;
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Cal.com sync failed', ['error' => $e->getMessage()]);
        }
        
        return $results;
    }
    
    /**
     * Sync Retell.ai call data and store locally
     */
    private function syncRetellData()
    {
        $results = [
            'calls' => 0,
            'agents' => 0,
            'transcripts' => 0,
            'errors' => [],
        ];
        
        try {
            // Get all companies
            $companies = DB::table('companies')->where('is_active', true)->get();
            
            foreach ($companies as $company) {
                if (!$company->retell_api_key) continue;
                
                // Initialize service with company API key
                $retellService = new RetellService($company->retell_api_key);
                
                // Sync recent calls
                $calls = $retellService->listCalls([
                    'limit' => 1000,
                    'created_after' => Carbon::now()->subDays(30)->toIso8601String(),
                ]);
                
                foreach ($calls as $call) {
                    // Get full call details with transcript
                    $callDetails = $retellService->getCall($call['call_id']);
                    
                    // Store in backup table
                    DB::table('retell_calls_backup')->updateOrInsert(
                        ['retell_call_id' => $call['call_id']],
                        [
                            'company_id' => $company->id,
                            'call_data' => json_encode($callDetails),
                            'transcript' => $callDetails['transcript'] ?? null,
                            'recording_url' => $callDetails['recording_url'] ?? null,
                            'duration_seconds' => $callDetails['duration'] ?? 0,
                            'from_number' => $call['from_number'],
                            'to_number' => $call['to_number'],
                            'synced_at' => now(),
                        ]
                    );
                    $results['calls']++;
                }
                
                // Sync agents
                $agents = $retellService->listAgents();
                foreach ($agents as $agent) {
                    DB::table('retell_agents_backup')->updateOrInsert(
                        ['retell_agent_id' => $agent['agent_id']],
                        [
                            'company_id' => $company->id,
                            'agent_data' => json_encode($agent),
                            'synced_at' => now(),
                        ]
                    );
                    $results['agents']++;
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Retell sync failed', ['error' => $e->getMessage()]);
        }
        
        return $results;
    }
    
    /**
     * Verify data integrity between local and external sources
     */
    public function verifyDataIntegrity()
    {
        $issues = [];
        
        // Check appointments
        $localAppointments = DB::table('appointments')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
            
        $calcomAppointments = DB::table('calcom_bookings_backup')
            ->where('synced_at', '>=', Carbon::now()->subDays(7))
            ->count();
            
        if (abs($localAppointments - $calcomAppointments) > 5) {
            $issues[] = "Appointment count mismatch: Local={$localAppointments}, Cal.com={$calcomAppointments}";
        }
        
        // Check calls
        $localCalls = DB::table('calls')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
            
        $retellCalls = DB::table('retell_calls_backup')
            ->where('synced_at', '>=', Carbon::now()->subDays(7))
            ->count();
            
        if (abs($localCalls - $retellCalls) > 5) {
            $issues[] = "Call count mismatch: Local={$localCalls}, Retell={$retellCalls}";
        }
        
        return $issues;
    }
}