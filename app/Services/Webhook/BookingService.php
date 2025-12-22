<?php

namespace App\Services\Webhook;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingService
{
    private CalcomService $calcomService;

    public function __construct()
    {
        $this->calcomService = new CalcomService();
    }

    /**
     * Extract booking details from call data
     */
    public function extractBookingDetails(Call $call): ?array
    {
        // First check if we have booking details in custom data
        if ($call->raw && isset($call->raw['custom_data'])) {
            $details = $this->extractFromCustomData($call->raw['custom_data']);
            if ($details) {
                return $details;
            }
        }

        // Then try to extract from transcript
        if ($call->transcript) {
            $details = $this->extractFromTranscript($call->transcript);
            if ($details) {
                return $details;
            }
        }

        // Try analysis data
        if ($call->analysis && isset($call->analysis['booking_details'])) {
            return $call->analysis['booking_details'];
        }

        return null;
    }

    /**
     * Extract booking details from custom data
     */
    private function extractFromCustomData(array $customData): ?array
    {
        if (!isset($customData['slots']) || !is_array($customData['slots'])) {
            return null;
        }

        $slots = $customData['slots'];

        // Check for required fields
        if (empty($slots['name']) && empty($slots['phone']) && empty($slots['email'])) {
            return null;
        }

        // Parse appointment time
        $appointmentTime = $this->parseAppointmentTime($slots);
        if (!$appointmentTime) {
            return null;
        }

        return [
            'customer_name' => $slots['name'] ?? 'Unbekannt',
            'customer_email' => $slots['email'] ?? 'termin@askproai.de',
            'customer_phone' => $slots['phone'] ?? $slots['to_number'] ?? null,
            'appointment_date' => $appointmentTime->format('Y-m-d'),
            'appointment_time' => $appointmentTime->format('H:i:s'),
            'appointment_datetime' => $appointmentTime->toIso8601String(),
            'service_type' => $slots['service'] ?? $slots['service_type'] ?? 'Allgemeine Beratung',
            'notes' => $slots['notes'] ?? '',
            'confidence' => 0.95, // High confidence for structured data
        ];
    }

    /**
     * Extract booking details from transcript
     */
    private function extractFromTranscript($transcript): ?array
    {
        $transcriptText = is_array($transcript) ? json_encode($transcript) : $transcript;

        // Pattern matching for German appointment keywords
        $patterns = [
            'name' => [
                '/(?:mein name ist|ich heiße|ich bin)\s+([A-Za-zÄÖÜäöüß\s]+)/i',
                '/(?:name lautet|heiße)\s+([A-Za-zÄÖÜäöüß\s]+)/i',
            ],
            'email' => [
                '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
            ],
            'phone' => [
                '/(?:telefon|nummer|handy|mobil)[\s:]*([+0-9\s\-\(\)]{10,20})/i',
                '/(\+49[0-9\s\-\(\)]{10,15})/i',
            ],
            'date' => [
                '/(?:termin am|für den|am)\s+(\d{1,2}[\.\s]\d{1,2}[\.\s]\d{2,4})/i',
                '/(morgen|übermorgen|montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)/i',
            ],
            'time' => [
                '/(?:um|uhrzeit)\s+(\d{1,2}:\d{2}|\d{1,2}\s+uhr)/i',
                '/(\d{1,2}:\d{2})\s*uhr/i',
            ],
        ];

        $extracted = [];
        $confidence = 0.5; // Base confidence for transcript extraction

        // Extract each field
        foreach ($patterns as $field => $fieldPatterns) {
            foreach ($fieldPatterns as $pattern) {
                if (preg_match($pattern, $transcriptText, $matches)) {
                    $extracted[$field] = trim($matches[1]);
                    $confidence += 0.1;
                    break;
                }
            }
        }

        // Check if we have minimum required fields
        if (empty($extracted['name']) && empty($extracted['phone'])) {
            return null;
        }

        // Parse date and time
        $appointmentTime = $this->parseGermanDateTime(
            $extracted['date'] ?? 'morgen',
            $extracted['time'] ?? '10:00'
        );

        return [
            'customer_name' => $extracted['name'] ?? 'Unbekannt',
            'customer_email' => $extracted['email'] ?? 'termin@askproai.de',
            'customer_phone' => $extracted['phone'] ?? null,
            'appointment_date' => $appointmentTime->format('Y-m-d'),
            'appointment_time' => $appointmentTime->format('H:i:s'),
            'appointment_datetime' => $appointmentTime->toIso8601String(),
            'service_type' => 'Allgemeine Beratung',
            'notes' => 'Automatisch aus Transkript extrahiert',
            'confidence' => min($confidence, 0.9),
        ];
    }

    /**
     * Parse appointment time from slots
     */
    private function parseAppointmentTime(array $slots): ?Carbon
    {
        // Check for ISO format first
        if (isset($slots['start']) && !empty($slots['start'])) {
            try {
                return Carbon::parse($slots['start']);
            } catch (\Exception $e) {
                // Continue to other formats
            }
        }

        // Check for separate date and time
        if (isset($slots['date']) && isset($slots['time'])) {
            try {
                return Carbon::parse($slots['date'] . ' ' . $slots['time']);
            } catch (\Exception $e) {
                // Continue
            }
        }

        // Check for German date descriptions
        if (isset($slots['appointment_date'])) {
            return $this->parseGermanDateTime($slots['appointment_date'], $slots['appointment_time'] ?? '10:00');
        }

        return null;
    }

    /**
     * Parse German date and time descriptions
     */
    private function parseGermanDateTime(string $dateStr, string $timeStr): Carbon
    {
        $date = match(strtolower($dateStr)) {
            'heute' => Carbon::today(),
            'morgen' => Carbon::tomorrow(),
            'übermorgen' => Carbon::today()->addDays(2),
            'montag' => Carbon::parse('next monday'),
            'dienstag' => Carbon::parse('next tuesday'),
            'mittwoch' => Carbon::parse('next wednesday'),
            'donnerstag' => Carbon::parse('next thursday'),
            'freitag' => Carbon::parse('next friday'),
            'samstag' => Carbon::parse('next saturday'),
            'sonntag' => Carbon::parse('next sunday'),
            default => $this->parseGermanDate($dateStr),
        };

        // Parse time
        $time = preg_replace('/[^0-9:]/', '', $timeStr);
        if (!str_contains($time, ':')) {
            $time = $time . ':00';
        }

        [$hour, $minute] = explode(':', $time);
        $date->setTime((int)$hour, (int)$minute);

        return $date;
    }

    /**
     * Parse German date format (DD.MM.YYYY)
     */
    private function parseGermanDate(string $dateStr): Carbon
    {
        // Try German format first (DD.MM.YYYY)
        if (preg_match('/(\d{1,2})[\.\s](\d{1,2})[\.\s](\d{2,4})/', $dateStr, $matches)) {
            $year = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
            return Carbon::createFromFormat('d.m.Y', $matches[1] . '.' . $matches[2] . '.' . $year);
        }

        // Fallback to Carbon's parser
        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            // Default to tomorrow if parsing fails
            return Carbon::tomorrow();
        }
    }

    /**
     * Create appointment from booking details
     */
    public function createAppointment(Call $call, array $bookingDetails): ?Appointment
    {
        try {
            // Find or create customer
            // Multi-Tenancy Fix: Pass company_id from call to ensure proper tenant isolation
            $customer = $this->findOrCreateCustomer($bookingDetails, $call->company_id);

            // Create appointment
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'call_id' => $call->id,
                'company_id' => $call->company_id,
                'service_id' => null, // Could be determined from service_type
                'staff_id' => null, // Could be assigned based on availability
                'appointment_date' => $bookingDetails['appointment_date'],
                'appointment_time' => $bookingDetails['appointment_time'],
                'status' => 'scheduled',
                'notes' => $bookingDetails['notes'] ?? '',
                'source' => 'retell_webhook',
                'confidence_score' => $bookingDetails['confidence'] ?? 0.5,
            ]);

            // Update call
            $call->update([
                'appointment_made' => true,
                'converted_appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
            ]);

            Log::info('✅ Appointment created from call', [
                'appointment_id' => $appointment->id,
                'call_id' => $call->id,
                'customer_id' => $customer->id,
            ]);

            return $appointment;

        } catch (\Exception $e) {
            Log::error('Failed to create appointment', [
                'error' => $e->getMessage(),
                'call_id' => $call->id,
                'booking_details' => $bookingDetails,
            ]);
            return null;
        }
    }

    /**
     * Find or create customer from booking details
     * Multi-Tenancy Fix: Now requires companyId to prevent cross-tenant matching
     */
    private function findOrCreateCustomer(array $bookingDetails, ?int $companyId = null): Customer
    {
        // Try to find existing customer by phone or email
        // Multi-Tenancy Fix: Filter by company_id
        $customer = null;
        $companyId = $companyId ?? ($bookingDetails['company_id'] ?? 1);

        if (!empty($bookingDetails['customer_phone'])) {
            $customer = Customer::where('company_id', $companyId)
                ->where('phone', $bookingDetails['customer_phone'])
                ->first();
        }

        if (!$customer && !empty($bookingDetails['customer_email'])) {
            $customer = Customer::where('company_id', $companyId)
                ->where('email', $bookingDetails['customer_email'])
                ->first();
        }

        // Create new customer if not found
        if (!$customer) {
            // 🔧 FIX: Create customer without guarded fields first
            $customer = Customer::create([
                'name' => $bookingDetails['customer_name'] ?? 'Unbekannt',
                'email' => $bookingDetails['customer_email'] ?? 'termin@askproai.de',
                'phone' => $bookingDetails['customer_phone'] ?? null,
                'source' => 'retell_webhook',
            ]);

            // Then set guarded fields directly (bypass mass assignment protection)
            $customer->company_id = $companyId;
            $customer->save();

            Log::info('Created new customer from booking', [
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id
            ]);
        }

        return $customer;
    }
}