<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentExtractionService
{
    /**
     * Extract appointment data from call transcript
     *
     * @param string $transcript
     * @return array|null
     */
    public function extractFromTranscript(string $transcript): ?array
    {
        if (empty($transcript)) {
            return null;
        }

        $result = [
            'found' => false,
            'date' => null,
            'time' => null,
            'service' => null,
            'customer_name' => null,
            'confidence' => 0
        ];

        // German date patterns
        $datePatterns = [
            'heute' => 'today',
            'morgen' => 'tomorrow',
            'übermorgen' => '+2 days',
            'montag' => 'next monday',
            'dienstag' => 'next tuesday',
            'mittwoch' => 'next wednesday',
            'donnerstag' => 'next thursday',
            'freitag' => 'next friday',
            'samstag' => 'next saturday',
            'sonntag' => 'next sunday',
            'nächste woche' => '+1 week',
            'nächsten montag' => 'next monday',
            'nächsten dienstag' => 'next tuesday',
            'nächsten mittwoch' => 'next wednesday',
            'nächsten donnerstag' => 'next thursday',
            'nächsten freitag' => 'next friday',
        ];

        // Extract date
        $foundDate = false;
        foreach ($datePatterns as $pattern => $carbonExpression) {
            if (stripos($transcript, $pattern) !== false) {
                $result['date'] = Carbon::parse($carbonExpression)->format('Y-m-d');
                $foundDate = true;
                $result['confidence'] += 30;
                break;
            }
        }

        // Extract time
        $timePatterns = [
            '/(\d{1,2})\s*uhr/i',
            '/(\d{1,2}):(\d{2})/i',
            '/um\s*(\d{1,2})/i'
        ];

        foreach ($timePatterns as $pattern) {
            if (preg_match($pattern, $transcript, $matches)) {
                $hour = intval($matches[1]);
                $minute = isset($matches[2]) ? intval($matches[2]) : 0;
                
                if ($hour >= 0 && $hour <= 23) {
                    $result['time'] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . 
                                     str_pad($minute, 2, '0', STR_PAD_LEFT);
                    $result['confidence'] += 30;
                    break;
                }
            }
        }

        // Extract customer name
        $namePatterns = [
            '/(?:ich bin|mein name ist|ich heiße)\s+([A-Za-zäöüÄÖÜß]+(?:\s+[A-Za-zäöüÄÖÜß]+)?)/i',
            '/(?:herr|frau)\s+([A-Za-zäöüÄÖÜß]+)/i'
        ];

        foreach ($namePatterns as $pattern) {
            if (preg_match($pattern, $transcript, $matches)) {
                $result['customer_name'] = trim($matches[1]);
                $result['confidence'] += 20;
                break;
            }
        }

        // Extract service type
        $serviceKeywords = [
            'beratung' => 'Beratung',
            'beratungstermin' => 'Beratung',
            'besprechung' => 'Besprechung',
            'konsultation' => 'Konsultation',
            'termin' => 'Allgemeiner Termin'
        ];

        foreach ($serviceKeywords as $keyword => $service) {
            if (stripos($transcript, $keyword) !== false) {
                $result['service'] = $service;
                $result['confidence'] += 20;
                break;
            }
        }

        // Check if appointment request is confirmed
        $confirmationPhrases = [
            'termin buchen',
            'termin vereinbaren',
            'termin machen',
            'möchte einen termin',
            'hätte gerne einen termin',
            'würde gern einen termin'
        ];

        foreach ($confirmationPhrases as $phrase) {
            if (stripos($transcript, $phrase) !== false) {
                $result['found'] = true;
                break;
            }
        }

        // Only return if we have minimum required data
        if ($result['found'] && $result['date'] && $result['time'] && $result['confidence'] >= 50) {
            $result['source'] = 'transcript_extraction';
            return $result;
        }

        return null;
    }

    /**
     * Create appointment from extracted data
     *
     * @param Call $call
     * @param array $appointmentData
     * @return Appointment|null
     */
    public function createAppointmentFromExtraction(Call $call, array $appointmentData): ?Appointment
    {
        try {
            Log::info('Starting appointment creation from extraction', [
                'call_id' => $call->id,
                'appointment_data' => $appointmentData
            ]);
            // Parse date and time
            $startTime = Carbon::parse($appointmentData['date'] . ' ' . $appointmentData['time']);
            
            // Adjust appointments in the past to next available time
            if ($startTime->isPast()) {
                Log::warning('Appointment time is in the past, adjusting to future', [
                    'call_id' => $call->id,
                    'original_time' => $startTime->format('Y-m-d H:i')
                ]);
                
                // If it's today but past time, move to tomorrow same time
                if ($startTime->isToday()) {
                    $startTime->addDay();
                }
                // If it's a past date, use the time but next occurrence
                else {
                    // Find next occurrence of the same day of week
                    $dayOfWeek = $startTime->dayOfWeek;
                    $startTime = now()->next($dayOfWeek)->setTimeFromTimeString($appointmentData['time']);
                }
                
                Log::info('Adjusted appointment time to future', [
                    'new_time' => $startTime->format('Y-m-d H:i')
                ]);
            }

            // Get or create customer
            $customer = $this->resolveCustomer($call, $appointmentData);
            if (!$customer) {
                Log::error('Failed to resolve customer', [
                    'call_id' => $call->id,
                    'phone' => $call->from_number
                ]);
                return null;
            }
            
            Log::info('Customer resolved', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name
            ]);

            // Create appointment (bypass tenant scope for webhook/extraction context)
            $appointment = new Appointment();
            $appointment->customer_id = $customer->id;
            $appointment->company_id = $call->company_id;
            $appointment->branch_id = $call->branch_id;
            $appointment->starts_at = $startTime;
            $appointment->ends_at = $startTime->copy()->addHour();
            $appointment->status = 'scheduled';
            $appointment->notes = $appointmentData['service'] ?? 'Telefonisch vereinbarter Termin';
            $appointment->source = 'phone';
            $appointment->metadata = [
                'extracted_from_call' => $call->id,
                'extraction_confidence' => $appointmentData['confidence'],
                'original_request' => $appointmentData,
                'created_via' => 'transcript_extraction'
            ];
            
            // Save without global scopes using DB facade
            \DB::table('appointments')->insert([
                'customer_id' => $appointment->customer_id,
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'source' => $appointment->source,
                'metadata' => json_encode($appointment->metadata),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Get the created appointment
            $appointment = Appointment::withoutGlobalScopes()
                ->where('company_id', $call->company_id)
                ->where('customer_id', $customer->id)
                ->where('starts_at', $startTime)
                ->orderBy('id', 'desc')
                ->first();

            // Link appointment to call
            $call->update(['appointment_id' => $appointment->id]);

            Log::info('Appointment created from transcript extraction', [
                'appointment_id' => $appointment->id,
                'call_id' => $call->id,
                'time' => $startTime->format('Y-m-d H:i')
            ]);

            return $appointment;

        } catch (\Exception $e) {
            Log::error('Failed to create appointment from extraction', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'data' => $appointmentData
            ]);
            return null;
        }
    }

    /**
     * Resolve customer from call and extracted data
     *
     * @param Call $call
     * @param array $appointmentData
     * @return Customer|null
     */
    protected function resolveCustomer(Call $call, array $appointmentData): ?Customer
    {
        // If call already has customer, use it
        if ($call->customer_id) {
            return Customer::withoutGlobalScopes()->find($call->customer_id);
        }

        // Try to find by phone number
        if ($call->from_number) {
            $customer = Customer::withoutGlobalScopes()
                ->where('company_id', $call->company_id)
                ->where('phone', $call->from_number)
                ->first();

            if ($customer) {
                // Update name if we extracted a better one
                if (!empty($appointmentData['customer_name']) && 
                    ($customer->name === 'Unknown' || $customer->name === 'Kunde')) {
                    $customer->update(['name' => $appointmentData['customer_name']]);
                }
                return $customer;
            }
        }

        // Create new customer
        $name = $appointmentData['customer_name'] ?? 'Kunde';
        $nameParts = explode(' ', $name);
        
        return Customer::create([
            'company_id' => $call->company_id,
            'name' => $name,
            'first_name' => $nameParts[0] ?? $name,
            'last_name' => $nameParts[1] ?? '',
            'phone' => $call->from_number,
            'source' => 'phone_call',
            'created_via' => 'transcript_extraction'
        ]);
    }

    /**
     * Process all calls without appointments
     *
     * @return array
     */
    public function processUnprocessedCalls(): array
    {
        $results = [
            'processed' => 0,
            'appointments_created' => 0,
            'failed' => 0
        ];

        // Get calls with transcripts but no appointments
        $calls = Call::withoutGlobalScopes()
            ->whereNotNull('transcript')
            ->whereNull('appointment_id')
            ->where('transcript', 'LIKE', '%termin%')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        foreach ($calls as $call) {
            $results['processed']++;

            $appointmentData = $this->extractFromTranscript($call->transcript);
            
            if ($appointmentData) {
                $appointment = $this->createAppointmentFromExtraction($call, $appointmentData);
                
                if ($appointment) {
                    $results['appointments_created']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }
}