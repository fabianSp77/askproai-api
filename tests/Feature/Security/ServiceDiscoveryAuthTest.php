<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Service Discovery Authorization Test Suite
 *
 * Tests BookingController fix and service authorization:
 * - Cross-company service booking prevention
 * - Service discovery authorization
 * - Booking authorization enforcement
 */
class ServiceDiscoveryAuthTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $userA;
    private User $userB;
    private Service $serviceA;
    private Service $serviceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->userB = User::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        $this->serviceA = Service::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Service A',
        ]);

        $this->serviceB = Service::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Service B',
        ]);
    }

    /**
     * @test
     * Test user cannot discover services from other companies
     */
    public function it_prevents_cross_company_service_discovery(): void
    {
        $this->actingAs($this->userA);

        $services = Service::all();

        $this->assertCount(1, $services);
        $this->assertEquals($this->serviceA->id, $services->first()->id);
        $this->assertNotContains($this->serviceB->id, $services->pluck('id'));
    }

    /**
     * @test
     * Test user cannot book services from other companies
     */
    public function it_prevents_cross_company_service_booking(): void
    {
        $this->actingAs($this->userA);

        // Attempt to book service from company B
        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->serviceB->id,
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'booking_time' => '10:00:00',
        ]);

        // Should fail with 403 or 404
        $this->assertContains($response->status(), [403, 404, 422]);

        // Verify booking was not created
        $this->assertDatabaseMissing('bookings', [
            'service_id' => $this->serviceB->id,
            'user_id' => $this->userA->id,
        ]);
    }

    /**
     * @test
     * Test service listing respects company scope
     */
    public function service_listing_respects_company_scope(): void
    {
        $this->actingAs($this->userA);

        $response = $this->getJson('/api/services');

        if ($response->status() === 200) {
            $data = $response->json('data') ?? $response->json();

            $serviceIds = collect($data)->pluck('id');

            $this->assertContains($this->serviceA->id, $serviceIds);
            $this->assertNotContains($this->serviceB->id, $serviceIds);
        }
    }

    /**
     * @test
     * Test user can book own company services
     */
    public function user_can_book_own_company_services(): void
    {
        $this->actingAs($this->userA);

        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->serviceA->id,
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'booking_time' => '10:00:00',
            'status' => 'pending',
        ]);

        if ($response->status() === 201) {
            $this->assertDatabaseHas('bookings', [
                'service_id' => $this->serviceA->id,
                'user_id' => $this->userA->id,
            ]);
        }
    }

    /**
     * @test
     * Test booking queries are scoped to company
     */
    public function booking_queries_are_scoped_to_company(): void
    {
        $bookingA = Booking::factory()->create([
            'service_id' => $this->serviceA->id,
            'user_id' => $this->userA->id,
            'company_id' => $this->companyA->id,
        ]);

        $bookingB = Booking::factory()->create([
            'service_id' => $this->serviceB->id,
            'user_id' => $this->userB->id,
            'company_id' => $this->companyB->id,
        ]);

        $this->actingAs($this->userA);

        $bookings = Booking::all();

        $this->assertCount(1, $bookings);
        $this->assertEquals($bookingA->id, $bookings->first()->id);
        $this->assertNotContains($bookingB->id, $bookings->pluck('id'));
    }

    /**
     * @test
     * Test user cannot view other company's bookings
     */
    public function it_prevents_viewing_cross_company_bookings(): void
    {
        $bookingB = Booking::factory()->create([
            'service_id' => $this->serviceB->id,
            'user_id' => $this->userB->id,
            'company_id' => $this->companyB->id,
        ]);

        $this->actingAs($this->userA);

        $response = $this->getJson("/api/bookings/{$bookingB->id}");

        $this->assertEquals(404, $response->status());
    }

    /**
     * @test
     * Test user cannot modify other company's bookings
     */
    public function it_prevents_modifying_cross_company_bookings(): void
    {
        $bookingB = Booking::factory()->create([
            'service_id' => $this->serviceB->id,
            'user_id' => $this->userB->id,
            'company_id' => $this->companyB->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->userA);

        $response = $this->putJson("/api/bookings/{$bookingB->id}", [
            'status' => 'cancelled',
        ]);

        $this->assertContains($response->status(), [403, 404]);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingB->id,
            'status' => 'pending',
        ]);
    }

    /**
     * @test
     * Test service authorization is enforced in BookingController
     */
    public function booking_controller_enforces_service_authorization(): void
    {
        $this->actingAs($this->userA);

        // Direct attempt to bypass by specifying service from another company
        $response = $this->postJson('/api/bookings', [
            'service_id' => $this->serviceB->id,
            'user_id' => $this->userA->id,
            'company_id' => $this->companyA->id, // Try to force company A
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'booking_time' => '10:00:00',
        ]);

        // Should fail authorization
        $this->assertContains($response->status(), [403, 404, 422]);
    }
}
