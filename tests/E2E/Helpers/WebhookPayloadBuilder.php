<?php

namespace Tests\E2E\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class WebhookPayloadBuilder
{
    protected array $payload = [];
    protected string $provider = 'retell';

    public function __construct(string $provider = 'retell')
    {
        $this->provider = $provider;
        $this->initializeDefaults();
    }

    protected function initializeDefaults(): void
    {
        if ($this->provider === 'retell') {
            $this->payload = [
                'event' => 'call_ended',
                'data' => [
                    'call_id' => 'call_' . Str::random(10),
                    'agent_id' => 'agent_test_001',
                    'from_number' => '+49' . rand(1000000000, 9999999999),
                    'to_number' => '+493012345678',
                    'direction' => 'inbound',
                    'status' => 'ended',
                    'duration_ms' => rand(60000, 300000),
                    'recording_url' => null,
                    'transcript' => '',
                    'transcript_object' => [],
                    'summary' => '',
                    'call_analysis' => [
                        'appointment_scheduled' => false,
                        'customer_name' => null,
                        'customer_phone' => null,
                        'customer_email' => null,
                        'service_requested' => null,
                        'preferred_date' => null,
                        'preferred_time' => null,
                    ],
                    'metadata' => [],
                ],
            ];
        } elseif ($this->provider === 'calcom') {
            $this->payload = [
                'triggerEvent' => 'BOOKING_CREATED',
                'createdAt' => Carbon::now()->toIso8601String(),
                'payload' => [
                    'bookingId' => rand(10000, 99999),
                    'uid' => 'book_' . Str::random(10),
                    'type' => 'booking',
                    'title' => 'Test Booking',
                    'startTime' => Carbon::now()->addDay()->toIso8601String(),
                    'endTime' => Carbon::now()->addDay()->addMinutes(30)->toIso8601String(),
                    'organizer' => [
                        'id' => 1,
                        'name' => 'Test Organizer',
                        'email' => 'organizer@test.com',
                        'timeZone' => 'Europe/Berlin',
                    ],
                    'attendees' => [],
                    'location' => 'In Person',
                    'destinationCalendar' => null,
                    'hideCalendarNotes' => false,
                    'requiresConfirmation' => false,
                    'eventTypeId' => 1,
                    'metadata' => [],
                ],
            ];
        }
    }

    public static function retell(): self
    {
        return new self('retell');
    }

    public static function calcom(): self
    {
        return new self('calcom');
    }

    public function withCallId(string $callId): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['call_id'] = $callId;
        }
        return $this;
    }

    public function withCompany(int $companyId, ?int $branchId = null): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['metadata']['company_id'] = $companyId;
            if ($branchId) {
                $this->payload['data']['metadata']['branch_id'] = $branchId;
            }
        } elseif ($this->provider === 'calcom') {
            $this->payload['payload']['metadata']['company_id'] = $companyId;
            if ($branchId) {
                $this->payload['payload']['metadata']['branch_id'] = $branchId;
            }
        }
        return $this;
    }

    public function withCustomer(string $name, string $phone, ?string $email = null): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['from_number'] = $phone;
            $this->payload['data']['call_analysis']['customer_name'] = $name;
            $this->payload['data']['call_analysis']['customer_phone'] = $phone;
            if ($email) {
                $this->payload['data']['call_analysis']['customer_email'] = $email;
            }
        } elseif ($this->provider === 'calcom') {
            $this->payload['payload']['attendees'][] = [
                'id' => rand(1000, 9999),
                'email' => $email ?? 'customer@example.com',
                'name' => $name,
                'timeZone' => 'Europe/Berlin',
                'locale' => 'de',
            ];
            $this->payload['payload']['responses']['phone'] = $phone;
        }
        return $this;
    }

    public function withAppointment(
        string $service,
        Carbon $date,
        ?string $staffName = null,
        ?int $duration = 30
    ): self {
        if ($this->provider === 'retell') {
            $this->payload['data']['call_analysis']['appointment_scheduled'] = true;
            $this->payload['data']['call_analysis']['service_requested'] = $service;
            $this->payload['data']['call_analysis']['preferred_date'] = $date->toDateString();
            $this->payload['data']['call_analysis']['preferred_time'] = $date->format('H:i');
            if ($staffName) {
                $this->payload['data']['call_analysis']['staff_name'] = $staffName;
            }
        } elseif ($this->provider === 'calcom') {
            $this->payload['payload']['title'] = $service;
            $this->payload['payload']['startTime'] = $date->toIso8601String();
            $this->payload['payload']['endTime'] = $date->addMinutes($duration)->toIso8601String();
            if ($staffName) {
                $this->payload['payload']['organizer']['name'] = $staffName;
            }
        }
        return $this;
    }

    public function withTranscript(string $transcript, ?string $summary = null): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['transcript'] = $transcript;
            if ($summary) {
                $this->payload['data']['summary'] = $summary;
            }
        }
        return $this;
    }

    public function withTranscriptObject(array $transcriptObject): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['transcript_object'] = $transcriptObject;
        }
        return $this;
    }

    public function withDuration(int $seconds): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['duration_ms'] = $seconds * 1000;
        }
        return $this;
    }

    public function withStatus(string $status): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['status'] = $status;
        } elseif ($this->provider === 'calcom') {
            $this->payload['payload']['status'] = $status;
        }
        return $this;
    }

    public function withEvent(string $event): self
    {
        if ($this->provider === 'retell') {
            $this->payload['event'] = $event;
        } elseif ($this->provider === 'calcom') {
            $this->payload['triggerEvent'] = $event;
        }
        return $this;
    }

    public function withCalcomBookingId(int $bookingId, string $uid): self
    {
        if ($this->provider === 'calcom') {
            $this->payload['payload']['bookingId'] = $bookingId;
            $this->payload['payload']['uid'] = $uid;
        }
        return $this;
    }

    public function withNoAppointment(): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['call_analysis']['appointment_scheduled'] = false;
            $this->payload['data']['call_analysis']['service_requested'] = null;
            $this->payload['data']['call_analysis']['preferred_date'] = null;
            $this->payload['data']['call_analysis']['preferred_time'] = null;
        }
        return $this;
    }

    public function withEmergency(bool $isEmergency = true): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['call_analysis']['is_emergency'] = $isEmergency;
        }
        return $this;
    }

    public function withNotes(string $notes): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['call_analysis']['notes'] = $notes;
        } elseif ($this->provider === 'calcom') {
            $this->payload['payload']['description'] = $notes;
        }
        return $this;
    }

    public function withRecordingUrl(string $url): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['recording_url'] = $url;
        }
        return $this;
    }

    public function withRawAnalysis(array $analysis): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['call_analysis'] = array_merge(
                $this->payload['data']['call_analysis'],
                $analysis
            );
        }
        return $this;
    }

    public function withMetadata(array $metadata): self
    {
        if ($this->provider === 'retell') {
            $this->payload['data']['metadata'] = array_merge(
                $this->payload['data']['metadata'],
                $metadata
            );
        } elseif ($this->provider === 'calcom') {
            $this->payload['payload']['metadata'] = array_merge(
                $this->payload['payload']['metadata'] ?? [],
                $metadata
            );
        }
        return $this;
    }

    public function build(): array
    {
        return $this->payload;
    }

    public function buildWithSignature(string $secret = null): array
    {
        $payload = $this->build();
        $signature = $this->generateSignature($payload, $secret);
        
        return [
            'payload' => $payload,
            'signature' => $signature,
        ];
    }

    protected function generateSignature(array $payload, ?string $secret = null): string
    {
        if (!$secret) {
            $secret = $this->provider === 'retell' 
                ? config('services.retell.webhook_secret', 'test_secret')
                : config('services.calcom.webhook_secret', 'test_secret');
        }

        $timestamp = time();
        $body = json_encode($payload);
        
        if ($this->provider === 'retell') {
            $signatureBase = "{$timestamp}.{$body}";
            $signature = hash_hmac('sha256', $signatureBase, $secret);
            return "t={$timestamp},v1={$signature}";
        } else {
            // Cal.com uses a different signature format
            $signature = hash_hmac('sha256', $body, $secret);
            return $signature;
        }
    }

    /**
     * Create a realistic appointment booking payload
     */
    public static function createAppointmentBooking(
        int $companyId,
        int $branchId,
        string $customerName = 'Test Customer',
        string $service = 'Kontrolluntersuchung',
        ?Carbon $appointmentDate = null
    ): array {
        $appointmentDate = $appointmentDate ?? Carbon::now()->next('Monday')->setTime(10, 0);
        $phone = '+49' . rand(1000000000, 9999999999);
        $email = Str::slug($customerName) . '@example.com';

        return self::retell()
            ->withCompany($companyId, $branchId)
            ->withCustomer($customerName, $phone, $email)
            ->withAppointment($service, $appointmentDate)
            ->withTranscript(
                "AI: Guten Tag, wie kann ich Ihnen helfen?\n" .
                "Customer: Ich möchte einen Termin für eine {$service} vereinbaren.\n" .
                "AI: Gerne, wann würde es Ihnen passen?\n" .
                "Customer: {$appointmentDate->format('l')} um {$appointmentDate->format('H:i')} Uhr wäre gut.\n" .
                "AI: Perfekt, ich habe Ihnen den Termin reserviert."
            )
            ->withDuration(120)
            ->build();
    }

    /**
     * Create an informational call payload (no appointment)
     */
    public static function createInfoCall(
        int $companyId,
        int $branchId,
        string $question = 'opening hours'
    ): array {
        return self::retell()
            ->withCompany($companyId, $branchId)
            ->withNoAppointment()
            ->withTranscript(
                "AI: Guten Tag, wie kann ich Ihnen helfen?\n" .
                "Customer: Ich hätte eine Frage zu {$question}.\n" .
                "AI: Gerne beantworte ich Ihre Frage...\n" .
                "Customer: Vielen Dank!\n" .
                "AI: Gern geschehen, einen schönen Tag noch!"
            )
            ->withDuration(60)
            ->build();
    }

    /**
     * Create a failed booking payload
     */
    public static function createFailedBooking(
        int $companyId,
        int $branchId,
        string $reason = 'no_availability'
    ): array {
        return self::retell()
            ->withCompany($companyId, $branchId)
            ->withCustomer('Failed Customer', '+491234567890')
            ->withAppointment('Kontrolluntersuchung', Carbon::now()->next('Monday'))
            ->withRawAnalysis([
                'appointment_scheduled' => false,
                'booking_failed' => true,
                'failure_reason' => $reason,
            ])
            ->withTranscript(
                "AI: Leider haben wir zu diesem Zeitpunkt keinen freien Termin.\n" .
                "Customer: Oh, das ist schade.\n" .
                "AI: Möchten Sie auf die Warteliste?"
            )
            ->build();
    }
}