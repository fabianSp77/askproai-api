<?php

namespace App\Http\Controllers\MCP;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetellWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $callData = $request->input('call', []);
        
        Log::info('MCP Retell Webhook received', [
            'event' => $event,
            'call_id' => $callData['call_id'] ?? null,
            'test' => $request->input('test', false)
        ]);
        
        // Handle different event types
        switch ($event) {
            case 'call_started':
                return $this->handleCallStarted($callData);
                
            case 'call_ended':
                return $this->handleCallEnded($callData);
                
            case 'call_analyzed':
                return $this->handleCallAnalyzed($callData);
                
            default:
                Log::warning('Unknown Retell event type', ['event' => $event]);
                return response()->json(['status' => 'ignored'], 200);
        }
    }
    
    protected function handleCallStarted($callData)
    {
        Log::info('Call started', ['call_id' => $callData['call_id']]);
        
        // Create initial call record
        $call = Call::updateOrCreate(
            ['retell_call_id' => $callData['call_id']],
            [
                'phone_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['direction'] ?? 'inbound',
                'status' => 'in_progress',
                'start_time' => now(),
                'retell_agent_id' => $callData['agent_id'] ?? null,
                'metadata' => $callData
            ]
        );
        
        return response()->json(['status' => 'success', 'call_id' => $call->id], 200);
    }
    
    protected function handleCallEnded($callData)
    {
        Log::info('Call ended', ['call_id' => $callData['call_id']]);
        
        // Find phone number and branch
        $phoneNumber = PhoneNumber::withoutGlobalScopes()
            ->where('number', $callData['to_number'])
            ->first();
            
        if (!$phoneNumber || !$phoneNumber->branch_id) {
            Log::error('No branch found for phone number', ['phone' => $callData['to_number']]);
            return response()->json(['error' => 'No branch found'], 400);
        }
        
        $branch = Branch::withoutGlobalScopes()->find($phoneNumber->branch_id);
        if (!$branch) {
            Log::error('Branch not found', ['branch_id' => $phoneNumber->branch_id]);
            return response()->json(['error' => 'Branch not found'], 400);
        }
        
        // Get or create customer
        $customer = $this->findOrCreateCustomer($callData, $branch->company_id);
        
        // Update call record
        $call = Call::updateOrCreate(
            ['retell_call_id' => $callData['call_id']],
            [
                'phone_number' => $callData['from_number'],
                'to_number' => $callData['to_number'],
                'direction' => $callData['direction'] ?? 'inbound',
                'status' => 'completed',
                'start_time' => isset($callData['start_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']) 
                    : now(),
                'end_time' => isset($callData['end_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']) 
                    : now(),
                'duration_minutes' => isset($callData['duration_ms']) 
                    ? round($callData['duration_ms'] / 60000, 2) 
                    : 0,
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'transcript' => $callData['transcript'] ?? null,
                'summary' => $callData['summary'] ?? null,
                'retell_agent_id' => $callData['agent_id'] ?? null,
                'metadata' => $callData
            ]
        );
        
        // Check if appointment was requested
        if ($this->shouldCreateAppointment($callData)) {
            $appointment = $this->createAppointmentFromCall($call, $callData, $branch, $customer);
            if ($appointment) {
                $call->update(['appointment_id' => $appointment->id]);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'call_id' => $call->id,
            'customer_id' => $customer->id,
            'appointment_id' => $call->appointment_id
        ], 200);
    }
    
    protected function handleCallAnalyzed($callData)
    {
        Log::info('Call analyzed', ['call_id' => $callData['call_id']]);
        
        // Update call with analysis data
        $call = Call::where('retell_call_id', $callData['call_id'])->first();
        if ($call) {
            $call->update([
                'analysis' => $callData['call_analysis'] ?? null,
                'sentiment' => $callData['call_analysis']['sentiment'] ?? null
            ]);
        }
        
        return response()->json(['status' => 'success'], 200);
    }
    
    protected function findOrCreateCustomer($callData, $companyId)
    {
        $phoneNumber = $callData['from_number'] ?? null;
        $analysis = $callData['call_analysis'] ?? [];
        $name = $analysis['customer_name'] ?? 'Unknown Customer';
        
        if ($phoneNumber) {
            // Try to find existing customer by phone
            $customer = Customer::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('phone', $phoneNumber)
                ->first();
                
            if ($customer) {
                // Update name if we got a better one
                if ($name !== 'Unknown Customer' && $customer->name === 'Unknown Customer') {
                    $customer->update(['name' => $name]);
                }
                return $customer;
            }
        }
        
        // Create new customer
        return Customer::create([
            'id' => Str::uuid()->toString(),
            'company_id' => $companyId,
            'name' => $name,
            'phone' => $phoneNumber,
            'email' => null, // Would need to extract from conversation
            'source' => 'phone_call',
            'notes' => 'Created from Retell AI call'
        ]);
    }
    
    protected function shouldCreateAppointment($callData)
    {
        $analysis = $callData['call_analysis'] ?? [];
        
        // Check if appointment was requested
        if (isset($analysis['appointment_requested']) && $analysis['appointment_requested']) {
            return true;
        }
        
        // Check summary for appointment keywords
        $summary = strtolower($callData['summary'] ?? '');
        $appointmentKeywords = ['termin', 'appointment', 'booking', 'reservation'];
        
        foreach ($appointmentKeywords as $keyword) {
            if (str_contains($summary, $keyword)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function createAppointmentFromCall($call, $callData, $branch, $customer)
    {
        $analysis = $callData['call_analysis'] ?? [];
        
        // Parse appointment details
        $preferredDate = $this->parseDate($analysis['preferred_date'] ?? 'next week');
        $preferredTime = $this->parseTime($analysis['preferred_time'] ?? '10:00');
        
        // Get default service
        $service = Service::withoutGlobalScopes()
            ->where('company_id', $branch->company_id)
            ->where('is_active', true)
            ->first();
            
        if (!$service) {
            Log::warning('No active service found for appointment');
            return null;
        }
        
        // Get first available staff
        $staff = Staff::withoutGlobalScopes()
            ->where('company_id', $branch->company_id)
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where('is_bookable', true)
            ->first();
            
        if (!$staff) {
            Log::warning('No bookable staff found for appointment');
            return null;
        }
        
        // Create appointment
        $appointment = Appointment::create([
            'id' => Str::uuid()->toString(),
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'appointment_date' => $preferredDate,
            'start_time' => $preferredTime,
            'end_time' => \Carbon\Carbon::parse($preferredTime)->addMinutes($service->duration)->format('H:i:s'),
            'duration' => $service->duration,
            'status' => 'scheduled',
            'notes' => 'Booked via AI phone call. ' . ($callData['summary'] ?? ''),
            'source' => 'phone_ai',
            'metadata' => [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'analysis' => $analysis
            ]
        ]);
        
        Log::info('Appointment created from call', [
            'appointment_id' => $appointment->id,
            'call_id' => $call->id,
            'date' => $preferredDate,
            'time' => $preferredTime
        ]);
        
        return $appointment;
    }
    
    protected function parseDate($dateString)
    {
        $dateString = strtolower($dateString);
        
        // Simple date parsing - in production, use more sophisticated NLP
        if (str_contains($dateString, 'morgen') || str_contains($dateString, 'tomorrow')) {
            return now()->addDay()->format('Y-m-d');
        }
        
        if (str_contains($dateString, 'übermorgen') || str_contains($dateString, 'day after tomorrow')) {
            return now()->addDays(2)->format('Y-m-d');
        }
        
        if (str_contains($dateString, 'montag') || str_contains($dateString, 'monday')) {
            return now()->next('Monday')->format('Y-m-d');
        }
        
        if (str_contains($dateString, 'dienstag') || str_contains($dateString, 'tuesday')) {
            return now()->next('Tuesday')->format('Y-m-d');
        }
        
        if (str_contains($dateString, 'mittwoch') || str_contains($dateString, 'wednesday')) {
            return now()->next('Wednesday')->format('Y-m-d');
        }
        
        if (str_contains($dateString, 'donnerstag') || str_contains($dateString, 'thursday')) {
            return now()->next('Thursday')->format('Y-m-d');
        }
        
        if (str_contains($dateString, 'freitag') || str_contains($dateString, 'friday')) {
            return now()->next('Friday')->format('Y-m-d');
        }
        
        // Default to next week
        return now()->addWeek()->format('Y-m-d');
    }
    
    protected function parseTime($timeString)
    {
        // Extract time in HH:MM format
        if (preg_match('/(\d{1,2}):?(\d{2})/', $timeString, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minute = $matches[2];
            return "$hour:$minute:00";
        }
        
        // Extract hour only (e.g., "14 Uhr", "2pm")
        if (preg_match('/(\d{1,2})\s*(uhr|pm|am)/i', $timeString, $matches)) {
            $hour = (int)$matches[1];
            $period = strtolower($matches[2]);
            
            if ($period === 'pm' && $hour < 12) {
                $hour += 12;
            }
            
            return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
        }
        
        // Default to 10:00
        return '10:00:00';
    }
}