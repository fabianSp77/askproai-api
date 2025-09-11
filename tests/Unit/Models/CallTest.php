<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Agent;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CallTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        // Arrange & Act
        $call = new Call();
        $expectedFillable = [
            'tenant_id',
            'customer_id',
            'agent_id',
            'call_id',
            'conversation_id',
            'from_number',
            'to_number',
            'start_timestamp',
            'end_timestamp',
            'duration_sec',
            'call_successful',
            'disconnect_reason',
            'transcript',
            'analysis',
            'branch_id',
            'appointment_id'
        ];

        // Assert
        $this->assertEquals($expectedFillable, $call->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        // Arrange
        $call = Call::factory()->create([
            'analysis' => ['intent' => 'booking', 'sentiment' => 'positive'],
            'call_successful' => true,
            'start_timestamp' => '2025-09-05 10:00:00',
            'end_timestamp' => '2025-09-05 10:05:00'
        ]);

        // Act & Assert
        $this->assertIsArray($call->analysis);
        $this->assertEquals('booking', $call->analysis['intent']);
        $this->assertTrue($call->call_successful);
        $this->assertInstanceOf(Carbon::class, $call->start_timestamp);
        $this->assertInstanceOf(Carbon::class, $call->end_timestamp);
    }

    /** @test */
    public function it_belongs_to_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $call = Call::factory()->create(['tenant_id' => $tenant->id]);

        // Act
        $callTenant = $call->tenant;

        // Assert
        $this->assertInstanceOf(Tenant::class, $callTenant);
        $this->assertEquals($tenant->id, $callTenant->id);
    }

    /** @test */
    public function it_belongs_to_customer()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $call = Call::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id
        ]);

        // Act
        $callCustomer = $call->customer;

        // Assert
        $this->assertInstanceOf(Customer::class, $callCustomer);
        $this->assertEquals($customer->id, $callCustomer->id);
    }

    /** @test */
    public function it_can_have_appointment()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $call = Call::factory()->create(['tenant_id' => $tenant->id]);
        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'call_id' => $call->id
        ]);

        // Act
        $callAppointment = $call->appointment;

        // Assert
        $this->assertInstanceOf(Appointment::class, $callAppointment);
        $this->assertEquals($appointment->id, $callAppointment->id);
    }

    /** @test */
    public function it_can_calculate_duration_in_minutes()
    {
        // Arrange
        $call = Call::factory()->create(['duration_sec' => 300]); // 5 minutes

        // Act
        $durationMinutes = $call->getDurationInMinutes();

        // Assert
        $this->assertEquals(5, $durationMinutes);
    }

    /** @test */
    public function it_rounds_up_partial_minutes()
    {
        // Arrange
        $call = Call::factory()->create(['duration_sec' => 150]); // 2.5 minutes

        // Act
        $durationMinutes = $call->getDurationInMinutes();

        // Assert
        $this->assertEquals(3, $durationMinutes); // Rounds up to 3
    }

    /** @test */
    public function it_can_determine_if_call_was_successful()
    {
        // Arrange
        $successfulCall = Call::factory()->create(['call_successful' => true]);
        $failedCall = Call::factory()->create(['call_successful' => false]);

        // Act & Assert
        $this->assertTrue($successfulCall->wasSuccessful());
        $this->assertFalse($failedCall->wasSuccessful());
    }

    /** @test */
    public function it_can_determine_if_call_led_to_appointment()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        $callWithAppointment = Call::factory()->create(['tenant_id' => $tenant->id]);
        Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'call_id' => $callWithAppointment->id
        ]);
        
        $callWithoutAppointment = Call::factory()->create(['tenant_id' => $tenant->id]);

        // Act & Assert
        $this->assertTrue($callWithAppointment->ledToAppointment());
        $this->assertFalse($callWithoutAppointment->ledToAppointment());
    }

    /** @test */
    public function it_can_extract_caller_name_from_transcript()
    {
        // Arrange
        $call = Call::factory()->create([
            'transcript' => 'Hi, my name is John Doe and I would like to book an appointment'
        ]);

        // Act
        $callerName = $call->extractCallerName();

        // Assert
        $this->assertEquals('John Doe', $callerName);
    }

    /** @test */
    public function it_returns_null_when_no_name_in_transcript()
    {
        // Arrange
        $call = Call::factory()->create([
            'transcript' => 'Hello, I would like some information about your services'
        ]);

        // Act
        $callerName = $call->extractCallerName();

        // Assert
        $this->assertNull($callerName);
    }

    /** @test */
    public function it_can_get_call_intent_from_analysis()
    {
        // Arrange
        $call = Call::factory()->create([
            'analysis' => ['intent' => 'appointment_booking', 'confidence' => 0.95]
        ]);

        // Act
        $intent = $call->getIntent();

        // Assert
        $this->assertEquals('appointment_booking', $intent);
    }

    /** @test */
    public function it_returns_null_when_no_intent_in_analysis()
    {
        // Arrange
        $call = Call::factory()->create([
            'analysis' => ['sentiment' => 'positive']
        ]);

        // Act
        $intent = $call->getIntent();

        // Assert
        $this->assertNull($intent);
    }

    /** @test */
    public function it_can_get_sentiment_from_analysis()
    {
        // Arrange
        $call = Call::factory()->create([
            'analysis' => ['sentiment' => 'positive', 'confidence' => 0.89]
        ]);

        // Act
        $sentiment = $call->getSentiment();

        // Assert
        $this->assertEquals('positive', $sentiment);
    }

    /** @test */
    public function it_can_determine_if_call_is_recent()
    {
        // Arrange
        $recentCall = Call::factory()->create([
            'created_at' => now()->subMinutes(30)
        ]);
        $oldCall = Call::factory()->create([
            'created_at' => now()->subDays(2)
        ]);

        // Act & Assert
        $this->assertTrue($recentCall->isRecent());
        $this->assertFalse($oldCall->isRecent());
    }

    /** @test */
    public function it_can_scope_successful_calls()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Call::factory(3)->create([
            'tenant_id' => $tenant->id,
            'call_successful' => true
        ]);
        Call::factory(2)->create([
            'tenant_id' => $tenant->id,
            'call_successful' => false
        ]);

        // Act
        $successfulCalls = Call::where('tenant_id', $tenant->id)->successful()->get();

        // Assert
        $this->assertCount(3, $successfulCalls);
        foreach ($successfulCalls as $call) {
            $this->assertTrue($call->call_successful);
        }
    }

    /** @test */
    public function it_can_scope_calls_by_date_range()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        // Calls within range
        Call::factory(2)->create([
            'tenant_id' => $tenant->id,
            'created_at' => now()->subDays(2)
        ]);
        
        // Calls outside range
        Call::factory(1)->create([
            'tenant_id' => $tenant->id,
            'created_at' => now()->subDays(10)
        ]);

        // Act
        $recentCalls = Call::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [now()->subDays(5), now()])
            ->get();

        // Assert
        $this->assertCount(2, $recentCalls);
    }

    /** @test */
    public function it_can_scope_calls_by_intent()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Call::factory(2)->create([
            'tenant_id' => $tenant->id,
            'analysis' => ['intent' => 'appointment_booking']
        ]);
        Call::factory(1)->create([
            'tenant_id' => $tenant->id,
            'analysis' => ['intent' => 'information_request']
        ]);

        // Act
        $bookingCalls = Call::where('tenant_id', $tenant->id)
            ->whereJsonContains('analysis->intent', 'appointment_booking')
            ->get();

        // Assert
        $this->assertCount(2, $bookingCalls);
    }

    /** @test */
    public function it_can_get_transcript_word_count()
    {
        // Arrange
        $call = Call::factory()->create([
            'transcript' => 'Hello, I would like to book an appointment for tomorrow.'
        ]);

        // Act
        $wordCount = $call->getTranscriptWordCount();

        // Assert
        $this->assertEquals(10, $wordCount);
    }

    /** @test */
    public function it_handles_empty_transcript_for_word_count()
    {
        // Arrange
        $call = Call::factory()->create(['transcript' => '']);

        // Act
        $wordCount = $call->getTranscriptWordCount();

        // Assert
        $this->assertEquals(0, $wordCount);
    }

    /** @test */
    public function it_can_format_phone_number()
    {
        // Arrange
        $call = Call::factory()->create(['from_number' => '+491234567890']);

        // Act
        $formattedNumber = $call->getFormattedFromNumber();

        // Assert
        $this->assertEquals('+49 123 456 7890', $formattedNumber);
    }

    /** @test */
    public function it_can_determine_call_outcome()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        $bookingCall = Call::factory()->create([
            'tenant_id' => $tenant->id,
            'call_successful' => true,
            'analysis' => ['intent' => 'appointment_booking']
        ]);
        Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'call_id' => $bookingCall->id
        ]);
        
        $infoCall = Call::factory()->create([
            'tenant_id' => $tenant->id,
            'call_successful' => true,
            'analysis' => ['intent' => 'information_request']
        ]);
        
        $failedCall = Call::factory()->create([
            'tenant_id' => $tenant->id,
            'call_successful' => false
        ]);

        // Act & Assert
        $this->assertEquals('appointment_booked', $bookingCall->getOutcome());
        $this->assertEquals('information_provided', $infoCall->getOutcome());
        $this->assertEquals('call_failed', $failedCall->getOutcome());
    }

    /** @test */
    public function it_can_calculate_cost_based_on_duration()
    {
        // Arrange
        $call = Call::factory()->create(['duration_sec' => 300]); // 5 minutes
        $ratePerMinute = 0.05; // 5 cents per minute

        // Act
        $cost = $call->calculateCost($ratePerMinute);

        // Assert
        $this->assertEquals(0.25, $cost); // 5 minutes * 0.05 = 0.25 EUR
    }

    /** @test */
    public function it_stores_analysis_as_json()
    {
        // Arrange
        $analysisData = [
            'intent' => 'appointment_booking',
            'sentiment' => 'positive',
            'confidence' => 0.95,
            'entities' => [
                'name' => 'John Doe',
                'phone' => '+491234567890'
            ]
        ];

        // Act
        $call = Call::factory()->create(['analysis' => $analysisData]);

        // Assert
        $this->assertEquals($analysisData, $call->analysis);
        $this->assertEquals('appointment_booking', $call->analysis['intent']);
        $this->assertEquals('John Doe', $call->analysis['entities']['name']);
    }
}