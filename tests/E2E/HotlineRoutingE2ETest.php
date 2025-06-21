<?php

namespace Tests\E2E;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\PhoneNumberResolver;
use App\Services\Booking\HotlineRouter;
use App\Services\CalcomV2Service;
use App\Services\WebhookProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

/**
 * @group e2e
 * @group hotline
 */
class HotlineRoutingE2ETest extends TestCase
{
    use RefreshDatabase;
    
    private Company $company;
    private array $branches;
    private PhoneNumber $hotline;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create multi-branch company
        $this->setupMultiBranchCompany();
        
        // Mock external services
        $this->mockRetellWebhook();
        $this->mockCalcomApi();
    }
    
    /** @test */
    public function hotline_call_routes_to_selected_branch_and_creates_appointment()
    {
        // Simulate incoming call to hotline
        $webhookPayload = $this->createRetellWebhookPayload([
            'call_id' => 'call_123',
            'from_number' => '+49 170 1234567',
            'to_number' => $this->hotline->number,
            'transcript' => $this->getTranscriptWithBranchSelection(),
            'call_analysis' => $this->getCallAnalysisWithAppointment()
        ]);
        
        // Process webhook
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $this->generateRetellSignature($webhookPayload)
        ]);
        
        $response->assertOk();
        
        // Verify call record created
        $call = Call::where('retell_call_id', 'call_123')->first();
        $this->assertNotNull($call);
        $this->assertEquals($this->company->id, $call->company_id);
        $this->assertEquals($this->branches['berlin']->id, $call->branch_id);
        
        // Verify customer created/found
        $customer = Customer::where('phone', '+49 170 1234567')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Maria Schmidt', $customer->name);
        
        // Verify appointment created
        $appointment = Appointment::where('call_id', $call->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($this->branches['berlin']->id, $appointment->branch_id);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals('2025-06-25 14:00:00', $appointment->start_time);
        
        // Verify Cal.com booking was created
        $this->assertNotNull($appointment->calcom_booking_id);
    }
    
    /** @test */
    public function hotline_voice_menu_handles_multiple_languages()
    {
        // Test German selection
        $germanPayload = $this->createRetellWebhookPayload([
            'call_id' => 'call_de_123',
            'transcript' => 'Ich möchte einen Termin für die Filiale Hamburg buchen.',
            'detected_language' => 'de'
        ]);
        
        $response = $this->postJson('/api/retell/webhook', $germanPayload, [
            'x-retell-signature' => $this->generateRetellSignature($germanPayload)
        ]);
        
        $response->assertOk();
        
        $call = Call::where('retell_call_id', 'call_de_123')->first();
        $this->assertEquals($this->branches['hamburg']->id, $call->branch_id);
        
        // Test English selection
        $englishPayload = $this->createRetellWebhookPayload([
            'call_id' => 'call_en_123',
            'transcript' => 'I would like to book an appointment at the Munich branch.',
            'detected_language' => 'en'
        ]);
        
        $response = $this->postJson('/api/retell/webhook', $englishPayload, [
            'x-retell-signature' => $this->generateRetellSignature($englishPayload)
        ]);
        
        $response->assertOk();
        
        $call = Call::where('retell_call_id', 'call_en_123')->first();
        $this->assertEquals($this->branches['munich']->id, $call->branch_id);
    }
    
    /** @test */
    public function hotline_fallback_when_no_branch_selected()
    {
        $payload = $this->createRetellWebhookPayload([
            'call_id' => 'call_fallback_123',
            'transcript' => 'Ich brauche einen Termin.',
            'call_analysis' => [
                'branch_selection' => null,
                'appointment_requested' => true
            ]
        ]);
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $this->generateRetellSignature($payload)
        ]);
        
        $response->assertOk();
        
        // Should route to default branch (first/main branch)
        $call = Call::where('retell_call_id', 'call_fallback_123')->first();
        $this->assertEquals($this->branches['berlin']->id, $call->branch_id);
    }
    
    /** @test */
    public function hotline_handles_business_hours_routing()
    {
        // Set Hamburg as closed, Munich as open
        $this->branches['hamburg']->update([
            'business_hours' => [
                'monday' => ['closed' => true]
            ]
        ]);
        
        // Call requesting Hamburg on Monday
        $payload = $this->createRetellWebhookPayload([
            'call_id' => 'call_hours_123',
            'transcript' => 'Termin für Hamburg bitte.',
            'timestamp' => '2025-06-23 10:00:00', // Monday
            'call_analysis' => [
                'branch_requested' => 'Hamburg',
                'suggested_alternative' => 'Munich'
            ]
        ]);
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $this->generateRetellSignature($payload)
        ]);
        
        $response->assertOk();
        
        // Should suggest Munich as alternative
        $call = Call::where('retell_call_id', 'call_hours_123')->first();
        $this->assertStringContainsString('München', $call->ai_response);
    }
    
    /** @test */
    public function direct_line_bypasses_voice_menu()
    {
        // Create direct line for Munich branch
        $directLine = PhoneNumber::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branches['munich']->id,
            'number' => '+49 89 9876543',
            'type' => 'direct',
            'is_active' => true
        ]);
        
        $payload = $this->createRetellWebhookPayload([
            'call_id' => 'call_direct_123',
            'to_number' => $directLine->number,
            'transcript' => 'Ich möchte einen Termin buchen.'
        ]);
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $this->generateRetellSignature($payload)
        ]);
        
        $response->assertOk();
        
        // Should route directly to Munich without menu
        $call = Call::where('retell_call_id', 'call_direct_123')->first();
        $this->assertEquals($this->branches['munich']->id, $call->branch_id);
        $this->assertStringNotContainsString('Drücken Sie', $call->transcript);
    }
    
    /** @test */
    public function hotline_tracks_routing_metrics()
    {
        // Make multiple calls with different selections
        $branchSelections = [
            'berlin' => 5,
            'hamburg' => 3,
            'munich' => 7
        ];
        
        foreach ($branchSelections as $branch => $count) {
            for ($i = 0; $i < $count; $i++) {
                $payload = $this->createRetellWebhookPayload([
                    'call_id' => "call_{$branch}_{$i}",
                    'transcript' => "Termin für {$branch}",
                    'call_analysis' => [
                        'branch_selection' => $branch
                    ]
                ]);
                
                $this->postJson('/api/retell/webhook', $payload, [
                    'x-retell-signature' => $this->generateRetellSignature($payload)
                ]);
            }
        }
        
        // Verify routing metrics
        $metrics = HotlineRouter::getRoutingMetrics($this->company->id);
        
        $this->assertEquals(15, $metrics['total_calls']);
        $this->assertEquals(5, $metrics['branches']['berlin']['call_count']);
        $this->assertEquals(3, $metrics['branches']['hamburg']['call_count']);
        $this->assertEquals(7, $metrics['branches']['munich']['call_count']);
        $this->assertEquals(46.67, round($metrics['branches']['munich']['percentage'], 2));
    }
    
    /** @test */
    public function emergency_routing_when_all_branches_closed()
    {
        // Close all branches
        foreach ($this->branches as $branch) {
            $branch->update(['is_active' => false]);
        }
        
        $payload = $this->createRetellWebhookPayload([
            'call_id' => 'call_emergency_123',
            'transcript' => 'Dringender Termin benötigt!'
        ]);
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $this->generateRetellSignature($payload)
        ]);
        
        $response->assertOk();
        
        $call = Call::where('retell_call_id', 'call_emergency_123')->first();
        $this->assertNotNull($call);
        $this->assertStringContainsString('außerhalb der Geschäftszeiten', $call->ai_response);
        $this->assertEquals('after_hours_message', $call->tags['routing_result']);
    }
    
    /**
     * Setup multi-branch company for testing
     */
    private function setupMultiBranchCompany(): void
    {
        $this->company = Company::factory()->create([
            'name' => 'Test Chain GmbH',
            'industry' => 'salon'
        ]);
        
        // Create branches
        $this->branches = [
            'berlin' => Branch::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Berlin Hauptfiliale',
                'city' => 'Berlin',
                'is_default' => true,
                'features' => ['parking', 'wheelchair'],
                'calcom_event_type_id' => 2026361
            ]),
            'hamburg' => Branch::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Hamburg Filiale',
                'city' => 'Hamburg',
                'features' => ['parking'],
                'calcom_event_type_id' => 2026362
            ]),
            'munich' => Branch::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'München Filiale',
                'city' => 'München',
                'features' => ['wheelchair', 'kids_area'],
                'calcom_event_type_id' => 2026363
            ])
        ];
        
        // Create hotline
        $this->hotline = PhoneNumber::create([
            'company_id' => $this->company->id,
            'number' => '+49 30 1234567',
            'type' => 'hotline',
            'is_active' => true,
            'routing_config' => [
                'type' => 'voice_menu',
                'options' => [
                    ['key' => '1', 'branch_id' => $this->branches['berlin']->id, 'description' => 'Berlin'],
                    ['key' => '2', 'branch_id' => $this->branches['hamburg']->id, 'description' => 'Hamburg'],
                    ['key' => '3', 'branch_id' => $this->branches['munich']->id, 'description' => 'München']
                ],
                'default_branch_id' => $this->branches['berlin']->id,
                'greeting' => 'Willkommen bei Test Chain. Bitte wählen Sie Ihre Filiale.'
            ]
        ]);
    }
    
    /**
     * Create Retell webhook payload
     */
    private function createRetellWebhookPayload(array $data): array
    {
        return array_merge([
            'event' => 'call_ended',
            'call_id' => $data['call_id'] ?? 'call_' . uniqid(),
            'from_number' => $data['from_number'] ?? '+49 170 1234567',
            'to_number' => $data['to_number'] ?? $this->hotline->number,
            'direction' => 'inbound',
            'call_status' => 'ended',
            'duration' => 180,
            'transcript' => $data['transcript'] ?? '',
            'recording_url' => 'https://retell.ai/recordings/test.mp3',
            'call_analysis' => $data['call_analysis'] ?? [],
            'timestamp' => $data['timestamp'] ?? now()->toIso8601String()
        ], $data);
    }
    
    /**
     * Generate valid Retell signature
     */
    private function generateRetellSignature(array $payload): string
    {
        $secret = config('services.retell.webhook_secret', 'test_secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
    
    /**
     * Mock Retell webhook processing
     */
    private function mockRetellWebhook(): void
    {
        Event::fake([
            'App\Events\CallProcessed',
            'App\Events\AppointmentCreated'
        ]);
        
        Queue::fake();
    }
    
    /**
     * Mock Cal.com API
     */
    private function mockCalcomApi(): void
    {
        Http::fake([
            'api.cal.com/v2/bookings' => Http::response([
                'data' => [
                    'id' => 'cal_booking_123',
                    'uid' => 'uid_123',
                    'eventTypeId' => 2026361,
                    'startTime' => '2025-06-25T14:00:00Z',
                    'endTime' => '2025-06-25T14:30:00Z',
                    'attendees' => [
                        ['email' => 'maria.schmidt@example.com', 'name' => 'Maria Schmidt']
                    ]
                ]
            ], 201),
            
            'api.cal.com/v2/slots*' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-06-25T14:00:00Z',
                        '2025-06-25T14:30:00Z',
                        '2025-06-25T15:00:00Z'
                    ]
                ]
            ])
        ]);
    }
    
    /**
     * Get sample transcript with branch selection
     */
    private function getTranscriptWithBranchSelection(): string
    {
        return "AI: Willkommen bei Test Chain. Für Berlin drücken Sie die 1, für Hamburg die 2, für München die 3.\n" .
               "Kunde: Ich möchte die 1.\n" .
               "AI: Sie haben Berlin gewählt. Wie kann ich Ihnen helfen?\n" .
               "Kunde: Ich bin Maria Schmidt und möchte einen Termin für einen Haarschnitt buchen.\n" .
               "AI: Gerne, Frau Schmidt. Wann hätten Sie Zeit?\n" .
               "Kunde: Am Dienstag nachmittag wäre gut.\n" .
               "AI: Ich habe am Dienstag, den 25. Juni um 14 Uhr einen Termin frei. Passt das?\n" .
               "Kunde: Ja, perfekt.\n" .
               "AI: Wunderbar. Der Termin ist gebucht.";
    }
    
    /**
     * Get call analysis with appointment data
     */
    private function getCallAnalysisWithAppointment(): array
    {
        return [
            'branch_selection' => '1',
            'branch_name' => 'Berlin',
            'appointment_requested' => true,
            'customer_name' => 'Maria Schmidt',
            'service_requested' => 'Haarschnitt',
            'preferred_date' => '2025-06-25',
            'preferred_time' => '14:00',
            'appointment_confirmed' => true
        ];
    }
}