<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\CalcomEventMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class CompositeBookingTest extends TestCase
{
    use RefreshDatabase;

    private Service $compositeService;
    private Branch $branch;
    private Staff $staffA;
    private Staff $staffB;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'calcom_v2_api_key' => 'test_api_key'
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Branch'
        ]);

        $this->compositeService = Service::factory()->create([
            'company_id' => $company->id,
            'name' => 'Composite Service',
            'composite' => true,
            'duration_minutes' => 150,
            'segments' => [
                [
                    'key' => 'A',
                    'name' => 'First Treatment',
                    'durationMin' => 60,
                    'gapAfterMin' => 30
                ],
                [
                    'key' => 'B',
                    'name' => 'Second Treatment',
                    'durationMin' => 60,
                    'gapAfterMin' => 0
                ]
            ]
        ]);

        $this->staffA = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Staff A'
        ]);

        $this->staffB = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Staff B'
        ]);

        // Link staff to service
        $this->compositeService->staff()->attach($this->staffA, [
            'segment_key' => 'A',
            'can_book' => true,
            'weight' => 100
        ]);

        $this->compositeService->staff()->attach($this->staffB, [
            'segment_key' => 'B',
            'can_book' => true,
            'weight' => 90
        ]);

        // Create event mappings
        CalcomEventMap::create([
            'company_id' => $company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->compositeService->id,
            'segment_key' => 'A',
            'staff_id' => $this->staffA->id,
            'event_type_id' => 1001,
            'sync_status' => 'synced'
        ]);

        CalcomEventMap::create([
            'company_id' => $company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->compositeService->id,
            'segment_key' => 'B',
            'staff_id' => $this->staffB->id,
            'event_type_id' => 1002,
            'sync_status' => 'synced'
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com'
        ]);
    }

    /**
     * Test composite booking creation
     */
    public function test_can_create_composite_booking(): void
    {
        $start = Carbon::now()->addDays(2)->setTime(10, 0);

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+491234567890'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => $start->toIso8601String()
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'appointment_id',
                    'composite_uid',
                    'status',
                    'starts_at',
                    'ends_at',
                    'segments',
                    'confirmation_code'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('appointments', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'is_composite' => true,
            'status' => 'booked'
        ]);
    }

    /**
     * Test simple booking for non-composite service
     */
    public function test_can_create_simple_booking(): void
    {
        $simpleService = Service::factory()->create([
            'company_id' => $this->branch->company_id,
            'name' => 'Simple Service',
            'composite' => false,
            'duration_minutes' => 60
        ]);

        CalcomEventMap::create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'service_id' => $simpleService->id,
            'staff_id' => $this->staffA->id,
            'event_type_id' => 2001,
            'sync_status' => 'synced'
        ]);

        $start = Carbon::now()->addDays(1)->setTime(14, 0);

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $simpleService->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staffA->id,
            'customer' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => $start->toIso8601String()
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'appointment_id',
                    'status',
                    'starts_at',
                    'ends_at',
                    'confirmation_code'
                ],
                'message'
            ]);
    }

    /**
     * Test appointment cancellation
     */
    public function test_can_cancel_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->compositeService->id,
            'customer_id' => $this->customer->id,
            'is_composite' => true,
            'status' => 'booked',
            'composite_group_uid' => uniqid('comp_'),
            'calcom_v2_booking_id' => 'cal_123'
        ]);

        $response = $this->deleteJson("/api/v2/bookings/{$appointment->id}", [
            'reason' => 'Customer requested cancellation'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'appointment_id' => $appointment->id,
                    'status' => 'cancelled'
                ],
                'message' => 'Appointment cancelled successfully'
            ]);
    }

    /**
     * Test appointment rescheduling
     */
    public function test_can_reschedule_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->branch->company_id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->compositeService->id,
            'customer_id' => $this->customer->id,
            'is_composite' => true,
            'status' => 'booked',
            'starts_at' => Carbon::now()->addDays(3)->setTime(10, 0),
            'ends_at' => Carbon::now()->addDays(3)->setTime(12, 30)
        ]);

        $newStart = Carbon::now()->addDays(5)->setTime(14, 0);

        $response = $this->patchJson("/api/v2/bookings/{$appointment->id}/reschedule", [
            'start' => $newStart->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'reason' => 'Schedule conflict'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'appointment_id',
                    'starts_at',
                    'ends_at'
                ],
                'message'
            ]);
    }

    /**
     * Test lock acquisition for concurrent bookings
     */
    public function test_prevents_double_booking_with_locks(): void
    {
        Redis::shouldReceive('set')
            ->twice()
            ->andReturn(true, false);

        $start = Carbon::now()->addDays(2)->setTime(10, 0);

        // First booking should succeed
        $response1 = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'Customer 1',
                'email' => 'customer1@test.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => $start->toIso8601String()
        ]);

        // Second booking at same time should fail
        $response2 = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'Customer 2',
                'email' => 'customer2@test.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => $start->toIso8601String()
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(500);
    }

    /**
     * Test availability check for composite service
     */
    public function test_can_get_composite_availability(): void
    {
        $response = $this->postJson('/api/v2/availability/composite', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'timeZone' => 'Europe/Berlin'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'service' => [
                        'id',
                        'name',
                        'is_composite',
                        'segments'
                    ],
                    'slots',
                    'total'
                ]
            ]);
    }

    /**
     * Test segment building from service definition
     */
    public function test_builds_segments_correctly(): void
    {
        $start = Carbon::now()->addDays(2)->setTime(10, 0);

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $this->compositeService->id,
            'branch_id' => $this->branch->id,
            'customer' => [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ],
            'timeZone' => 'Europe/Berlin',
            'start' => $start->toIso8601String()
        ]);

        $response->assertStatus(201);

        $data = $response->json('data');
        $this->assertCount(2, $data['segments']);

        // Check segment A: 10:00 - 11:00
        $this->assertEquals('A', $data['segments'][0]['key']);
        $this->assertEquals($start->toIso8601String(), $data['segments'][0]['starts_at']);
        $this->assertEquals($start->copy()->addMinutes(60)->toIso8601String(), $data['segments'][0]['ends_at']);

        // Check segment B: 11:30 - 12:30 (after 30min gap)
        $this->assertEquals('B', $data['segments'][1]['key']);
        $this->assertEquals($start->copy()->addMinutes(90)->toIso8601String(), $data['segments'][1]['starts_at']);
        $this->assertEquals($start->copy()->addMinutes(150)->toIso8601String(), $data['segments'][1]['ends_at']);
    }
}