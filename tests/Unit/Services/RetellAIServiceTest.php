<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RetellAIService;
use App\Models\Call;
use App\Models\Tenant;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetellAIServiceTest extends TestCase
{
    use RefreshDatabase;

    private RetellAIService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set([
            'services.retell.api_key' => 'test-retell-key',
            'services.retell.webhook_secret' => 'test-webhook-secret'
        ]);

        $this->service = new RetellAIService();
    }

    /** @test */
    public function it_can_process_call_ended_webhook()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        $webhookData = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_call_123',
                'conversation_id' => 'conv_456',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'transcript' => 'Hello, I would like to book an appointment',
                'call_analysis' => [
                    'intent' => 'appointment_booking',
                    'sentiment' => 'positive',
                    'customer_info' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com'
                    ]
                ]
            ]
        ];

        // Act
        $result = $this->service->processCallEndedWebhook($webhookData, $tenant);

        // Assert
        $this->assertInstanceOf(Call::class, $result);
        $this->assertEquals('retell_call_123', $result->call_id);
        $this->assertEquals('conv_456', $result->conversation_id);
        $this->assertEquals($tenant->id, $result->tenant_id);
        $this->assertDatabaseHas('calls', [
            'call_id' => 'retell_call_123',
            'tenant_id' => $tenant->id
        ]);
    }

    /** @test */
    public function it_can_verify_webhook_signature()
    {
        // Arrange
        $payload = '{"event":"call_ended","data":{}}';
        $secret = 'test-webhook-secret';
        $validSignature = hash_hmac('sha256', $payload, $secret);
        $invalidSignature = 'invalid-signature';

        // Act & Assert
        $this->assertTrue($this->service->verifyWebhookSignature($payload, $validSignature, $secret));
        $this->assertFalse($this->service->verifyWebhookSignature($payload, $invalidSignature, $secret));
    }

    /** @test */
    public function it_extracts_customer_info_from_transcript()
    {
        // Arrange
        $transcript = 'Hi, my name is Jane Smith and my email is jane@example.com. I want to book for tomorrow at 3 PM.';
        
        // Act
        $result = $this->service->extractCustomerInfo($transcript);

        // Assert
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('Jane Smith', $result['name']);
        $this->assertEquals('jane@example.com', $result['email']);
    }

    /** @test */
    public function it_detects_booking_intent_from_transcript()
    {
        // Arrange
        $bookingTranscript = 'I would like to schedule an appointment for next week';
        $infoTranscript = 'What are your opening hours?';
        $complaintTranscript = 'I need to cancel my appointment and I am not happy';

        // Act & Assert
        $this->assertEquals('appointment_booking', $this->service->detectIntent($bookingTranscript));
        $this->assertEquals('information_request', $this->service->detectIntent($infoTranscript));
        $this->assertEquals('complaint', $this->service->detectIntent($complaintTranscript));
    }

    /** @test */
    public function it_analyzes_sentiment_from_transcript()
    {
        // Arrange
        $positiveTranscript = 'Thank you so much! This is wonderful service!';
        $negativeTranscript = 'This is terrible service. I am very disappointed.';
        $neutralTranscript = 'What time do you close?';

        // Act & Assert
        $this->assertEquals('positive', $this->service->analyzeSentiment($positiveTranscript));
        $this->assertEquals('negative', $this->service->analyzeSentiment($negativeTranscript));
        $this->assertEquals('neutral', $this->service->analyzeSentiment($neutralTranscript));
    }

    /** @test */
    public function it_handles_invalid_webhook_data()
    {
        // Arrange
        $invalidData = [
            'event' => 'unknown_event',
            'data' => []
        ];
        $tenant = Tenant::factory()->create();

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid webhook event type');
        
        $this->service->processCallEndedWebhook($invalidData, $tenant);
    }

    /** @test */
    public function it_handles_missing_required_webhook_fields()
    {
        // Arrange
        $incompleteData = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'test_call_123'
                // Missing required fields
            ]
        ];
        $tenant = Tenant::factory()->create();

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required webhook data fields');
        
        $this->service->processCallEndedWebhook($incompleteData, $tenant);
    }

    /** @test */
    public function it_can_create_or_update_customer_from_call_data()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $callData = [
            'from_number' => '+491234567890',
            'call_analysis' => [
                'customer_info' => [
                    'name' => 'New Customer',
                    'email' => 'new@example.com'
                ]
            ]
        ];

        // Act
        $customer = $this->service->createOrUpdateCustomer($callData, $tenant);

        // Assert
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('New Customer', $customer->name);
        $this->assertEquals('new@example.com', $customer->email);
        $this->assertEquals('+491234567890', $customer->phone);
        $this->assertEquals($tenant->id, $customer->tenant_id);
    }

    /** @test */
    public function it_updates_existing_customer_with_new_info()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $existingCustomer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => '+491234567890',
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);

        $callData = [
            'from_number' => '+491234567890',
            'call_analysis' => [
                'customer_info' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com'
                ]
            ]
        ];

        // Act
        $customer = $this->service->createOrUpdateCustomer($callData, $tenant);

        // Assert
        $this->assertEquals($existingCustomer->id, $customer->id);
        $this->assertEquals('Updated Name', $customer->name);
        $this->assertEquals('updated@example.com', $customer->email);
    }

    /** @test */
    public function it_processes_call_with_appointment_booking_intent()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        $webhookData = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'booking_call_123',
                'conversation_id' => 'conv_789',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'transcript' => 'Hi, I would like to book an appointment for tomorrow at 2 PM',
                'call_analysis' => [
                    'intent' => 'appointment_booking',
                    'sentiment' => 'positive',
                    'booking_details' => [
                        'preferred_time' => '2025-09-06T14:00:00Z',
                        'service' => 'consultation'
                    ]
                ]
            ]
        ];

        // Mock Cal.com service
        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response([
                'booking' => [
                    'id' => 456,
                    'status' => 'ACCEPTED'
                ]
            ], 201)
        ]);

        // Act
        $call = $this->service->processCallEndedWebhook($webhookData, $tenant);

        // Assert
        $this->assertEquals('appointment_booking', $call->analysis['intent']);
        $this->assertArrayHasKey('booking_details', $call->analysis);
    }
}