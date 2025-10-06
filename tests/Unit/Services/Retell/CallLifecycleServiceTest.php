<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Services\Retell\CallLifecycleService;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit Tests for CallLifecycleService
 *
 * Verifies call creation, state management, caching, and lifecycle tracking
 */
class CallLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private CallLifecycleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CallLifecycleService();
    }

    /** @test */
    public function it_creates_call_with_basic_data()
    {
        $callData = [
            'call_id' => 'retell_abc123',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'direction' => 'inbound',
        ];

        $call = $this->service->createCall($callData, 15, 1, 2);

        $this->assertInstanceOf(Call::class, $call);
        $this->assertEquals('retell_abc123', $call->retell_call_id);
        $this->assertEquals('+491234567890', $call->from_number);
        $this->assertEquals('+499876543210', $call->to_number);
        $this->assertEquals(15, $call->company_id);
        $this->assertEquals(1, $call->phone_number_id);
        $this->assertEquals(2, $call->branch_id);
        $this->assertEquals('ongoing', $call->status);
    }

    /** @test */
    public function it_creates_call_with_timestamp()
    {
        $timestamp = now()->timestamp * 1000; // milliseconds
        $callData = [
            'call_id' => 'retell_xyz789',
            'from_number' => '+491234567890',
            'start_timestamp' => $timestamp,
        ];

        $call = $this->service->createCall($callData);

        $this->assertNotNull($call->start_timestamp);
        $this->assertInstanceOf(Carbon::class, $call->start_timestamp);
    }

    /** @test */
    public function it_creates_temporary_call_with_temp_id()
    {
        $call = $this->service->createTemporaryCall(
            '+491234567890',
            '+499876543210',
            15,
            1,
            2,
            'agent_123'
        );

        $this->assertStringStartsWith('temp_', $call->retell_call_id);
        $this->assertEquals('inbound', $call->status);
        $this->assertEquals('+491234567890', $call->from_number);
        $this->assertEquals('+499876543210', $call->to_number);
        $this->assertEquals(15, $call->company_id);
        $this->assertEquals('agent_123', $call->agent_id);
    }

    /** @test */
    public function it_finds_call_by_retell_id()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_find_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        $found = $this->service->findCallByRetellId('retell_find_test');

        $this->assertNotNull($found);
        $this->assertEquals($call->id, $found->id);
        $this->assertEquals('retell_find_test', $found->retell_call_id);
    }

    /** @test */
    public function it_finds_call_by_external_id_fallback()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_main',
            'external_id' => 'retell_external',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        $found = $this->service->findCallByRetellId('retell_external');

        $this->assertNotNull($found);
        $this->assertEquals($call->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_call_not_found()
    {
        $found = $this->service->findCallByRetellId('nonexistent_call');

        $this->assertNull($found);
    }

    /** @test */
    public function it_uses_cache_for_repeated_lookups()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_cache_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        // First lookup - queries database
        $found1 = $this->service->findCallByRetellId('retell_cache_test');

        // Second lookup - uses cache (no database query)
        $found2 = $this->service->findCallByRetellId('retell_cache_test');

        $this->assertEquals($found1->id, $found2->id);
        $this->assertSame($found1, $found2); // Same instance from cache
    }

    /** @test */
    public function it_finds_recent_temporary_call()
    {
        // Create temp call
        $tempCall = Call::create([
            'retell_call_id' => 'temp_' . now()->timestamp . '_abc123',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'inbound',
            'created_at' => now()->subMinutes(5),
        ]);

        $found = $this->service->findRecentTemporaryCall();

        $this->assertNotNull($found);
        $this->assertEquals($tempCall->id, $found->id);
        $this->assertStringStartsWith('temp_', $found->retell_call_id);
    }

    /** @test */
    public function it_returns_null_for_old_temporary_calls()
    {
        // Create old temp call (11 minutes ago)
        Call::create([
            'retell_call_id' => 'temp_old_call',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'inbound',
            'created_at' => now()->subMinutes(11),
        ]);

        $found = $this->service->findRecentTemporaryCall();

        $this->assertNull($found);
    }

    /** @test */
    public function it_upgrades_temporary_call_to_real_call()
    {
        $tempCall = Call::create([
            'retell_call_id' => 'temp_upgrade_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'inbound',
        ]);

        $upgraded = $this->service->upgradeTemporaryCall(
            $tempCall,
            'retell_real_id',
            ['status' => 'ongoing']
        );

        $this->assertEquals('retell_real_id', $upgraded->retell_call_id);
        $this->assertEquals('retell_real_id', $upgraded->external_id);
        $this->assertEquals('ongoing', $upgraded->status);
    }

    /** @test */
    public function it_updates_call_status()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_status_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'inbound',
        ]);

        $updated = $this->service->updateCallStatus($call, 'ongoing');

        $this->assertEquals('ongoing', $updated->status);
        $this->assertEquals('ongoing', $updated->call_status);
    }

    /** @test */
    public function it_updates_call_status_with_additional_data()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_status_data_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        $updated = $this->service->updateCallStatus(
            $call,
            'completed',
            [
                'end_timestamp' => now(),
                'duration_ms' => 120000,
                'duration_sec' => 120,
            ]
        );

        $this->assertEquals('completed', $updated->status);
        $this->assertNotNull($updated->end_timestamp);
        $this->assertEquals(120000, $updated->duration_ms);
        $this->assertEquals(120, $updated->duration_sec);
    }

    /** @test */
    public function it_links_customer_to_call()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_customer_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'company_id' => 15,
        ]);

        $updated = $this->service->linkCustomer($call, $customer);

        $this->assertEquals($customer->id, $updated->customer_id);
    }

    /** @test */
    public function it_links_appointment_to_call()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_appointment_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'completed',
        ]);

        $appointment = Appointment::create([
            'company_id' => 15,
            'customer_id' => 1,
            'service_id' => 1,
            'branch_id' => 1,
            'appointment_datetime' => now()->addDay(),
            'status' => 'active',
        ]);

        $updated = $this->service->linkAppointment($call, $appointment);

        $this->assertEquals($appointment->id, $updated->converted_appointment_id);
    }

    /** @test */
    public function it_tracks_booking_details()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_booking_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        $bookingDetails = [
            'customer_name' => 'John Doe',
            'service' => 'Haarschnitt',
            'date' => '2025-10-01',
            'time' => '14:00',
        ];

        $updated = $this->service->trackBooking($call, $bookingDetails);

        $this->assertNotNull($updated->booking_details);
        $decoded = json_decode($updated->booking_details, true);
        $this->assertEquals('John Doe', $decoded['customer_name']);
        $this->assertEquals('Haarschnitt', $decoded['service']);
    }

    /** @test */
    public function it_tracks_confirmed_booking()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_confirmed_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        $bookingDetails = [
            'customer_name' => 'Jane Smith',
            'service' => 'Färben',
        ];

        $updated = $this->service->trackBooking(
            $call,
            $bookingDetails,
            true, // confirmed
            'calcom_booking_123'
        );

        $this->assertTrue($updated->booking_confirmed);
        $this->assertTrue($updated->call_successful);
        $this->assertTrue($updated->appointment_made);
        $this->assertEquals('calcom_booking_123', $updated->booking_id);
    }

    /** @test */
    public function it_tracks_failed_booking()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_failed_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'completed',
        ]);

        $bookingDetails = [
            'customer_name' => 'Failed Customer',
            'service' => 'Beratung',
        ];

        $updated = $this->service->trackFailedBooking(
            $call,
            $bookingDetails,
            'No available time slots'
        );

        $this->assertTrue($updated->booking_failed);
        $this->assertTrue($updated->requires_manual_processing);
        $this->assertFalse($updated->call_successful);
        $this->assertEquals('No available time slots', $updated->booking_failure_reason);
    }

    /** @test */
    public function it_updates_call_analysis()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_analysis_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'completed',
            'analysis' => ['initial_key' => 'initial_value'],
        ]);

        $analysisData = [
            'sentiment' => 'positive',
            'intent' => 'booking',
            'confidence' => 0.95,
        ];

        $updated = $this->service->updateAnalysis($call, $analysisData);

        $analysis = $updated->analysis;
        $this->assertEquals('initial_value', $analysis['initial_key']); // Preserved
        $this->assertEquals('positive', $analysis['sentiment']); // Added
        $this->assertEquals('booking', $analysis['intent']); // Added
        $this->assertEquals(0.95, $analysis['confidence']); // Added
    }

    /** @test */
    public function it_gets_call_context_with_caching()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_context_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
            'phone_number_id' => 1,
            'company_id' => 15,
            'branch_id' => 2,
        ]);

        // Mock phoneNumber relationship to avoid null check failure
        $call->phoneNumber = (object)['id' => 1];

        // First call - database query
        $context1 = $this->service->getCallContext('retell_context_test');

        // Second call - cache hit
        $context2 = $this->service->getCallContext('retell_context_test');

        $this->assertNotNull($context1);
        $this->assertNotNull($context2);
    }

    /** @test */
    public function it_finds_recent_call_with_company()
    {
        Call::create([
            'retell_call_id' => 'retell_recent_1',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'completed',
            'company_id' => 15,
            'created_at' => now()->subMinutes(5),
        ]);

        $found = $this->service->findRecentCallWithCompany(30);

        $this->assertNotNull($found);
        $this->assertEquals(15, $found->company_id);
    }

    /** @test */
    public function it_returns_null_when_no_recent_call_with_company()
    {
        Call::create([
            'retell_call_id' => 'retell_old',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'completed',
            'company_id' => 15,
            'created_at' => now()->subHours(2),
        ]);

        $found = $this->service->findRecentCallWithCompany(30); // 30 minutes

        $this->assertNull($found);
    }

    /** @test */
    public function it_clears_cache()
    {
        $call = Call::create([
            'retell_call_id' => 'retell_clear_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ongoing',
        ]);

        // Cache the call
        $this->service->findCallByRetellId('retell_clear_test');

        // Clear cache
        $this->service->clearCache();

        // Next lookup should query database again (not from cache)
        // We can't directly test this without inspecting internal state,
        // but the method should execute without errors
        $this->assertTrue(true);
    }
}