<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Call;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * SmartBookingService - Der zentrale Service für alle Terminbuchungen
 * 
 * Konsolidiert die Funktionalität von:
 * - AppointmentService
 * - BookingService  
 * - Teile von CallService
 */
class SmartBookingService
{
    private CalcomV2Service $calcom;
    private RetellV2Service $retell;
    private PhoneNumberResolver $phoneResolver;
    
    public function __construct(
        CalcomV2Service $calcom,
        RetellV2Service $retell,
        PhoneNumberResolver $phoneResolver
    ) {
        $this->calcom = $calcom;
        $this->retell = $retell;
        $this->phoneResolver = $phoneResolver;
    }
    
    /**
     * Hauptmethode: Verarbeitet einen eingehenden Anruf und bucht einen Termin
     */
    public function handleIncomingCall(array $webhookData): ?Appointment
    {
        Log::info('SmartBookingService: Processing incoming call', [
            'call_id' => $webhookData['call_id'] ?? 'unknown',
            'to_number' => $webhookData['to_number'] ?? 'unknown'
        ]);
        
        DB::beginTransaction();
        
        try {
            // 1. Branch über Telefonnummer finden
            $branch = $this->resolveBranch($webhookData);
            if (!$branch) {
                throw new Exception('Keine Filiale für Telefonnummer gefunden');
            }
            
            // 2. Call-Record erstellen/updaten
            $call = $this->processCallData($webhookData, $branch);
            
            // 3. Kunde finden oder erstellen
            $customer = $this->findOrCreateCustomer($webhookData, $branch);
            
            // 4. Terminwunsch extrahieren
            $bookingIntent = $this->extractBookingIntent($webhookData);
            if (!$bookingIntent) {
                Log::info('Kein Terminwunsch erkannt');
                DB::commit();
                return null;
            }
            
            // 5. Verfügbaren Slot finden
            $slot = $this->findAvailableSlot($branch, $bookingIntent);
            if (!$slot) {
                Log::warning('Kein verfügbarer Termin gefunden');
                DB::commit();
                return null;
            }
            
            // 6. Termin buchen
            $appointment = $this->createAppointment($branch, $customer, $slot, $call);
            
            // 7. Bei Cal.com buchen
            $calcomBooking = $this->bookInCalcom($appointment);
            if ($calcomBooking) {
                $appointment->update([
                    'external_id' => $calcomBooking['id'],
                    'status' => 'confirmed'
                ]);
            }
            
            DB::commit();
            
            Log::info('SmartBookingService: Termin erfolgreich gebucht', [
                'appointment_id' => $appointment->id,
                'calcom_id' => $calcomBooking['id'] ?? null
            ]);
            
            return $appointment;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SmartBookingService: Fehler bei Terminbuchung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Branch über Telefonnummer auflösen
     */
    private function resolveBranch(array $webhookData): ?Branch
    {
        $toNumber = $webhookData['to_number'] ?? null;
        $agentId = $webhookData['agent_id'] ?? null;
        
        // Zuerst über Telefonnummer
        if ($toNumber) {
            $branch = DB::table('phone_numbers')
                ->where('number', $toNumber)
                ->join('branches', 'phone_numbers.branch_id', '=', 'branches.id')
                ->where('branches.active', true)
                ->select('branches.*')
                ->first();
                
            if ($branch) {
                return Branch::hydrate([$branch])->first();
            }
            
            // Fallback: Direkt bei Branch
            $branch = Branch::where('phone_number', $toNumber)
                ->where('active', true)
                ->first();
                
            if ($branch) {
                return $branch;
            }
        }
        
        // Über Agent ID
        if ($agentId) {
            return Branch::where('retell_agent_id', $agentId)
                ->where('active', true)
                ->first();
        }
        
        return null;
    }
    
    /**
     * Call-Daten verarbeiten
     */
    private function processCallData(array $webhookData, Branch $branch): Call
    {
        $callId = $webhookData['call_id'];
        
        $call = Call::firstOrNew(['call_id' => $callId]);
        
        $call->fill([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'from_number' => $webhookData['from_number'] ?? null,
            'to_number' => $webhookData['to_number'] ?? null,
            'agent_id' => $webhookData['agent_id'] ?? null,
            'status' => $webhookData['status'] ?? 'completed',
            'start_time' => Carbon::parse($webhookData['start_timestamp'] ?? now()),
            'end_time' => Carbon::parse($webhookData['end_timestamp'] ?? now()),
            'duration' => $webhookData['call_length'] ?? 0,
            'recording_url' => $webhookData['recording_url'] ?? null,
            'transcript' => $webhookData['transcript'] ?? null,
            'summary' => $webhookData['call_summary'] ?? null,
            'metadata' => $webhookData,
        ]);
        
        // Custom fields (mit _ prefix)
        $customFields = [];
        foreach ($webhookData as $key => $value) {
            if (str_starts_with($key, '_')) {
                $customFields[substr($key, 1)] = $value;
            }
        }
        
        if (!empty($customFields)) {
            $call->custom_fields = $customFields;
        }
        
        $call->save();
        
        return $call;
    }
    
    /**
     * Kunde finden oder erstellen
     */
    private function findOrCreateCustomer(array $webhookData, Branch $branch): Customer
    {
        $phoneNumber = $webhookData['from_number'] ?? null;
        $name = $webhookData['_customer_name'] ?? 'Unbekannt';
        $email = $webhookData['_customer_email'] ?? null;
        
        // Existierenden Kunden suchen
        if ($phoneNumber) {
            $customer = Customer::where('company_id', $branch->company_id)
                ->where('phone', $phoneNumber)
                ->first();
                
            if ($customer) {
                // Update mit neuen Infos
                if ($email && !$customer->email) {
                    $customer->email = $email;
                }
                if ($name !== 'Unbekannt' && $customer->name === 'Unbekannt') {
                    $customer->name = $name;
                }
                $customer->save();
                return $customer;
            }
        }
        
        // Neuen Kunden erstellen
        return Customer::create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phoneNumber,
            'source' => 'phone_call',
            'status' => 'active'
        ]);
    }
    
    /**
     * Terminwunsch aus Webhook-Daten extrahieren
     */
    private function extractBookingIntent(array $webhookData): ?array
    {
        // Check verschiedene Felder
        $hasAppointmentRequest = 
            ($webhookData['appointment_requested'] ?? false) ||
            ($webhookData['_appointment_requested'] ?? false) ||
            ($webhookData['user_requested_appointment'] ?? false);
            
        if (!$hasAppointmentRequest) {
            return null;
        }
        
        // Datum und Zeit extrahieren
        $requestedDate = $webhookData['_requested_date'] ?? 
                        $webhookData['requested_date'] ?? 
                        null;
                        
        $requestedTime = $webhookData['_requested_time'] ?? 
                        $webhookData['requested_time'] ?? 
                        null;
        
        // Service/Grund
        $service = $webhookData['_service_type'] ?? 
                  $webhookData['_appointment_reason'] ?? 
                  $webhookData['service_requested'] ?? 
                  'Allgemein';
        
        return [
            'requested_date' => $requestedDate,
            'requested_time' => $requestedTime,
            'service' => $service,
            'duration' => $webhookData['_duration'] ?? 30,
            'notes' => $webhookData['_notes'] ?? ''
        ];
    }
    
    /**
     * Verfügbaren Termin-Slot finden
     */
    private function findAvailableSlot(Branch $branch, array $bookingIntent): ?array
    {
        $eventTypeId = $branch->calcom_event_type_id;
        if (!$eventTypeId) {
            Log::warning('Branch hat keine Cal.com Event Type ID');
            return null;
        }
        
        // Parse requested date/time
        $startDate = $bookingIntent['requested_date'] ? 
            Carbon::parse($bookingIntent['requested_date']) : 
            Carbon::tomorrow();
            
        $endDate = $startDate->copy()->addDays(7);
        
        try {
            // Verfügbarkeit bei Cal.com prüfen
            $slots = $this->calcom->getAvailability(
                $eventTypeId,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );
            
            if (empty($slots)) {
                return null;
            }
            
            // Wenn spezifische Zeit gewünscht, versuche nächsten Slot zu finden
            if ($bookingIntent['requested_time']) {
                $requestedTime = Carbon::parse($bookingIntent['requested_time']);
                
                // Finde nächsten passenden Slot
                foreach ($slots as $slot) {
                    $slotTime = Carbon::parse($slot['time']);
                    if ($slotTime->format('H:i') === $requestedTime->format('H:i')) {
                        return $slot;
                    }
                }
            }
            
            // Sonst ersten verfügbaren Slot
            return $slots[0] ?? null;
            
        } catch (Exception $e) {
            Log::error('Fehler beim Abrufen der Verfügbarkeit', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Termin in Datenbank erstellen
     */
    private function createAppointment(
        Branch $branch,
        Customer $customer,
        array $slot,
        Call $call
    ): Appointment {
        $startTime = Carbon::parse($slot['time']);
        $duration = $slot['duration'] ?? 30;
        $endTime = $startTime->copy()->addMinutes($duration);
        
        return Appointment::create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'call_id' => $call->id,
            'title' => "Termin mit {$customer->name}",
            'description' => $call->custom_fields['notes'] ?? '',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration,
            'status' => 'pending',
            'type' => 'phone_booking',
            'metadata' => [
                'slot_data' => $slot,
                'booked_via' => 'smart_booking_service'
            ]
        ]);
    }
    
    /**
     * Termin bei Cal.com buchen
     */
    private function bookInCalcom(Appointment $appointment): ?array
    {
        try {
            $customer = $appointment->customer;
            $branch = $appointment->branch;
            
            $bookingData = [
                'eventTypeId' => $branch->calcom_event_type_id,
                'start' => $appointment->start_time->toIso8601String(),
                'responses' => [
                    'name' => $customer->name,
                    'email' => $customer->email ?: 'noemail@askproai.de',
                    'phone' => $customer->phone,
                    'notes' => $appointment->description
                ],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'branch_id' => $branch->id,
                    'call_id' => $appointment->call_id
                ]
            ];
            
            return $this->calcom->createBooking($bookingData);
            
        } catch (Exception $e) {
            Log::error('Fehler beim Buchen in Cal.com', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}