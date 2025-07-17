<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\Call;
use App\Models\Customer;
use App\Models\CustomerJourneyStage;
use App\Services\Customer\CustomerMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerJourneyApiController extends BaseApiController
{
    protected $matchingService;
    
    public function __construct(CustomerMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }
    
    /**
     * Get customer journey data for a specific call
     */
    public function getCallJourney(Request $request, $callId)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        // Load call with customer
        $call = Call::where('company_id', $companyId)
            ->where('id', $callId)
            ->with(['customer', 'company', 'branch'])
            ->firstOrFail();
        
        // Get journey stages
        $journeyStages = CustomerJourneyStage::orderBy('order')->get();
        
        $response = [
            'call' => $call,
            'journey_stages' => $journeyStages,
            'customer' => null,
            'journey_data' => null,
            'potential_matches' => [],
            'touchpoints' => [],
            'journey_events' => [],
            'related_customers' => []
        ];
        
        if ($call->customer) {
            $customer = $call->customer;
            $currentStage = $journeyStages->firstWhere('code', $customer->journey_status);
            
            // Get touchpoints
            $touchpoints = DB::table('customer_touchpoints')
                ->where('customer_id', $customer->id)
                ->orderBy('occurred_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($touchpoint) {
                    $touchpoint->data = json_decode($touchpoint->data);
                    return $touchpoint;
                });
            
            // Get journey events
            $journeyEvents = DB::table('customer_journey_events')
                ->where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($event) {
                    $event->event_data = json_decode($event->event_data);
                    return $event;
                });
            
            // Get related interactions
            $interactions = $this->matchingService->getRelatedInteractions($customer);
            
            $response['customer'] = $customer;
            $response['journey_data'] = [
                'current_stage' => $currentStage,
                'journey_history' => json_decode($customer->journey_history ?? '[]'),
                'stats' => [
                    'total_calls' => $interactions['total_calls'],
                    'total_appointments' => $interactions['total_appointments'],
                    'call_count' => $customer->call_count ?? 0,
                    'appointment_count' => $customer->appointment_count ?? 0,
                    'completed_appointments' => $customer->completed_appointments ?? 0,
                    'cancelled_appointments' => $customer->cancelled_appointments ?? 0,
                    'no_show_appointments' => $customer->no_show_appointments ?? 0,
                    'total_revenue' => $customer->total_revenue ?? 0,
                    'last_call_at' => $customer->last_call_at,
                    'last_appointment_at' => $customer->last_appointment_at,
                ],
                'tags' => json_decode($customer->tags ?? '[]'),
                'internal_notes' => $customer->internal_notes,
            ];
            $response['touchpoints'] = $touchpoints;
            $response['journey_events'] = $journeyEvents;
            $response['related_customers'] = $interactions['related_customers'];
            
        } else {
            // Find potential matches
            $phoneNumber = $call->from_number;
            $companyName = $call->metadata['customer_data']['company'] ?? 
                          $call->extracted_company ?? 
                          null;
            $customerNumber = $call->metadata['customer_data']['customer_number'] ?? 
                             null;
            
            if ($phoneNumber) {
                $potentialMatches = $this->matchingService->findRelatedCustomers(
                    $call->company_id,
                    $call->to_number,
                    $phoneNumber,
                    $companyName,
                    $customerNumber
                );
                
                // Enhance matches with journey status
                $potentialMatches->each(function ($match) use ($journeyStages) {
                    $match->journey_stage = $journeyStages->firstWhere('code', $match->journey_status);
                });
                
                $response['potential_matches'] = $potentialMatches;
            }
        }
        
        return response()->json($response);
    }
    
    /**
     * Update customer journey status
     */
    public function updateJourneyStatus(Request $request, $customerId)
    {
        $validated = $request->validate([
            'status' => 'required|exists:customer_journey_stages,code',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);
        
        $user = $this->getCurrentUser();
        $company = $this->getCompany();
        
        if (!$user || !$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        $customer = Customer::where('company_id', $companyId)
            ->where('id', $customerId)
            ->firstOrFail();
        
        $oldStatus = $customer->journey_status;
        
        // Update customer
        $customer->journey_status = $validated['status'];
        $customer->journey_status_updated_at = now();
        
        // Update journey history
        $history = json_decode($customer->journey_history ?? '[]', true);
        $history[] = [
            'from' => $oldStatus,
            'to' => $validated['status'],
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'changed_by' => $user->name,
            'changed_at' => now()->toIso8601String()
        ];
        $customer->journey_history = json_encode($history);
        
        $customer->save();
        
        // Create journey event
        DB::table('customer_journey_events')->insert([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'event_type' => 'status_changed',
            'from_status' => $oldStatus,
            'to_status' => $validated['status'],
            'event_data' => json_encode([
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null
            ]),
            'triggered_by' => 'user',
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'customer' => $customer->fresh(),
            'message' => 'Journey Status erfolgreich aktualisiert'
        ]);
    }
    
    /**
     * Assign call to customer
     */
    public function assignCustomer(Request $request, $callId)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id'
        ]);
        
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        $call = Call::where('company_id', $companyId)
            ->where('id', $callId)
            ->firstOrFail();
        
        $customer = Customer::where('company_id', $companyId)
            ->where('id', $validated['customer_id'])
            ->firstOrFail();
        
        // Update call
        $call->customer_id = $customer->id;
        $call->save();
        
        // Update customer tracking data
        if (!empty($call->metadata['customer_data'])) {
            $data = $call->metadata['customer_data'];
            
            if (!empty($data['company']) && empty($customer->company_name)) {
                $customer->company_name = $data['company'];
            }
            
            if (!empty($data['customer_number']) && empty($customer->customer_number)) {
                $customer->customer_number = $data['customer_number'];
            }
            
            $customer->save();
        }
        
        // Create touchpoint
        DB::table('customer_touchpoints')->insert([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'type' => 'call',
            'channel' => 'phone',
            'direction' => 'inbound',
            'status' => 'completed',
            'data' => json_encode([
                'call_id' => $call->id,
                'duration' => $call->duration_sec,
                'summary' => $call->summary
            ]),
            'occurred_at' => $call->start_timestamp ?? $call->created_at,
            'touchpointable_type' => 'App\\Models\\Call',
            'touchpointable_id' => $call->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Fire call updated event to update stats
        event(new \App\Events\CallUpdated($call));
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich zugeordnet',
            'customer' => $customer
        ]);
    }
    
    /**
     * Add note to customer
     */
    public function addNote(Request $request, $customerId)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:5000',
            'type' => 'nullable|in:general,important,follow_up'
        ]);
        
        $user = $this->getCurrentUser();
        $company = $this->getCompany();
        
        if (!$user || !$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        $customer = Customer::where('company_id', $companyId)
            ->where('id', $customerId)
            ->firstOrFail();
        
        // Add note with timestamp
        $noteEntry = sprintf(
            "[%s - %s]%s\n%s",
            now()->format('d.m.Y H:i'),
            $user->name,
            $validated['type'] ? ' [' . strtoupper($validated['type']) . ']' : '',
            $validated['note']
        );
        
        $customer->internal_notes = trim($customer->internal_notes . "\n\n" . $noteEntry);
        $customer->save();
        
        // Create touchpoint
        DB::table('customer_touchpoints')->insert([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'type' => 'note',
            'channel' => 'internal',
            'direction' => null,
            'status' => 'completed',
            'data' => json_encode([
                'note' => $validated['note'],
                'type' => $validated['type'] ?? 'general',
                'added_by' => $user->name
            ]),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notiz erfolgreich hinzugefügt',
            'customer' => $customer
        ]);
    }
    
    /**
     * Get customer statistics
     */
    public function getCustomerStats(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        $stats = [
            'total_customers' => Customer::where('company_id', $companyId)->count(),
            'new_customers_this_month' => Customer::where('company_id', $companyId)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'active_customers' => Customer::where('company_id', $companyId)
                ->whereIn('journey_status', ['appointment_scheduled', 'appointment_completed', 'regular_customer'])
                ->count(),
            'at_risk_customers' => Customer::where('company_id', $companyId)
                ->where('journey_status', 'follow_up_needed')
                ->count(),
            'journey_distribution' => Customer::where('company_id', $companyId)
                ->select('journey_status', DB::raw('count(*) as count'))
                ->groupBy('journey_status')
                ->pluck('count', 'journey_status'),
            'revenue_this_month' => Customer::where('company_id', $companyId)
                ->sum('total_revenue'),
            'avg_appointments_per_customer' => Customer::where('company_id', $companyId)
                ->where('appointment_count', '>', 0)
                ->avg('appointment_count') ?? 0,
        ];
        
        return response()->json($stats);
    }
}