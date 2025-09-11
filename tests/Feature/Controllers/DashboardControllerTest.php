<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_displays_dashboard_with_basic_stats()
    {
        // Arrange
        Call::factory(5)->create(['tenant_id' => $this->tenant->id]);
        Appointment::factory(3)->create(['tenant_id' => $this->tenant->id]);
        Customer::factory(10)->create(['tenant_id' => $this->tenant->id]);

        // Act
        $response = $this->get(route('filament.admin.pages.dashboard'));

        // Assert
        $response->assertSuccessful();
        $response->assertSee('Dashboard');
    }

    /** @test */
    public function it_shows_correct_call_statistics()
    {
        // Arrange
        // Today's calls
        Call::factory(3)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
            'call_successful' => true
        ]);
        
        // Yesterday's calls
        Call::factory(2)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDay(),
            'call_successful' => true
        ]);
        
        // Failed calls
        Call::factory(1)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
            'call_successful' => false
        ]);

        // Act
        $response = $this->get('/api/dashboard/stats');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonStructure([
            'calls_today',
            'calls_this_week',
            'calls_this_month',
            'successful_calls_rate'
        ]);
        
        $data = $response->json();
        $this->assertEquals(4, $data['calls_today']); // 3 successful + 1 failed
        $this->assertGreaterThanOrEqual(4, $data['calls_this_week']);
    }

    /** @test */
    public function it_shows_appointment_statistics()
    {
        // Arrange
        // Today's appointments
        Appointment::factory(2)->create([
            'tenant_id' => $this->tenant->id,
            'start_time' => now(),
            'status' => 'scheduled'
        ]);
        
        // Completed appointments
        Appointment::factory(3)->create([
            'tenant_id' => $this->tenant->id,
            'start_time' => now()->subHour(),
            'status' => 'completed'
        ]);

        // Act
        $response = $this->get('/api/dashboard/appointments-stats');

        // Assert
        $response->assertSuccessful();
        $data = $response->json();
        
        $this->assertArrayHasKey('scheduled_today', $data);
        $this->assertArrayHasKey('completed_this_week', $data);
        $this->assertEquals(2, $data['scheduled_today']);
        $this->assertEquals(3, $data['completed_this_week']);
    }

    /** @test */
    public function it_filters_data_by_tenant_correctly()
    {
        // Arrange
        $otherTenant = Tenant::factory()->create();
        
        // Create calls for current tenant
        Call::factory(3)->create(['tenant_id' => $this->tenant->id]);
        
        // Create calls for other tenant (should not appear in stats)
        Call::factory(5)->create(['tenant_id' => $otherTenant->id]);

        // Act
        $response = $this->get('/api/dashboard/stats');

        // Assert
        $response->assertSuccessful();
        $data = $response->json();
        
        $this->assertEquals(3, $data['calls_today']);
        // Verify we don't see the other tenant's calls
    }

    /** @test */
    public function it_shows_recent_calls_activity()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $recentCalls = Call::factory(5)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'created_at' => now()->subMinutes(rand(1, 60))
        ]);

        // Act
        $response = $this->get('/api/dashboard/recent-calls');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'call_id',
                    'from_number',
                    'customer',
                    'duration_sec',
                    'created_at'
                ]
            ]
        ]);
    }

    /** @test */
    public function it_shows_upcoming_appointments()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Future appointments
        Appointment::factory(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->addHours(rand(1, 24)),
            'status' => 'scheduled'
        ]);

        // Past appointments (should not appear)
        Appointment::factory(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->subHours(2),
            'status' => 'completed'
        ]);

        // Act
        $response = $this->get('/api/dashboard/upcoming-appointments');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'customer',
                    'start_time',
                    'end_time',
                    'status'
                ]
            ]
        ]);
    }

    /** @test */
    public function it_handles_empty_dashboard_gracefully()
    {
        // Act - No data created, should handle empty state
        $response = $this->get('/api/dashboard/stats');

        // Assert
        $response->assertSuccessful();
        $data = $response->json();
        
        $this->assertEquals(0, $data['calls_today']);
        $this->assertEquals(0, $data['calls_this_week']);
        $this->assertEquals(0, $data['calls_this_month']);
        $this->assertEquals(0, $data['successful_calls_rate']);
    }

    /** @test */
    public function it_caches_dashboard_stats_for_performance()
    {
        // Arrange
        Call::factory(10)->create(['tenant_id' => $this->tenant->id]);
        $cacheKey = "dashboard_stats_{$this->tenant->id}";

        // Act - First request should cache the data
        $response1 = $this->get('/api/dashboard/stats');
        
        // Verify cache was set
        $this->assertTrue(Cache::has($cacheKey));
        
        // Second request should use cached data
        $response2 = $this->get('/api/dashboard/stats');

        // Assert
        $response1->assertSuccessful();
        $response2->assertSuccessful();
        $this->assertEquals($response1->json(), $response2->json());
    }

    /** @test */
    public function it_requires_authentication_for_dashboard_access()
    {
        // Arrange - Log out user
        $this->app['auth']->logout();

        // Act
        $response = $this->get('/api/dashboard/stats');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_shows_conversion_rate_metrics()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Calls that led to appointments
        $callsWithAppointments = Call::factory(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);
        
        foreach ($callsWithAppointments as $call) {
            Appointment::factory()->create([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customer->id,
                'call_id' => $call->id
            ]);
        }
        
        // Calls without appointments
        Call::factory(7)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);

        // Act
        $response = $this->get('/api/dashboard/conversion-stats');

        // Assert
        $response->assertSuccessful();
        $data = $response->json();
        
        $this->assertArrayHasKey('conversion_rate', $data);
        $this->assertEquals(30, $data['conversion_rate']); // 3 out of 10 calls = 30%
        $this->assertEquals(10, $data['total_calls']);
        $this->assertEquals(3, $data['appointments_created']);
    }

    /** @test */
    public function it_shows_revenue_projections()
    {
        // Arrange
        Appointment::factory(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'start_time' => now()->subDays(7)
        ]);

        // Act
        $response = $this->get('/api/dashboard/revenue-stats');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonStructure([
            'revenue_this_month',
            'projected_revenue',
            'average_appointment_value',
            'completed_appointments_count'
        ]);
    }
}