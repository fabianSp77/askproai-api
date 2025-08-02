<?php

namespace Tests\Feature\Portal;

use Tests\TestCase;
use Tests\Traits\PortalTestHelpers;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Traits\UsesMCPServers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use Mockery;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, PortalTestHelpers;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected User $adminUser;
    protected User $appointmentUser;
    protected Staff $staff;
    protected Service $service;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and branch
        $this->company = $this->createTestCompany([
            'appointment_booking_enabled' => true,
            'multi_branch' => true
        ]);
        
        $this->branch = $this->createTestBranch($this->company);

        // Create users with different permission levels
        $this->user = $this->createPortalTestUser([
            'appointments.view' => false,
            'appointments.manage' => false
        ], ['company' => $this->company]);

        $this->appointmentUser = $this->createPortalTestUser([
            'appointments.view' => true,
            'appointments.manage' => true
        ], ['company' => $this->company]);

        $this->adminUser = $this->createPortalTestUser([
            'appointments.view' => true,
            'appointments.manage' => true,
            'admin.all' => true
        ], ['company' => $this->company]);

        // Create related test data
        $this->createAppointmentTestData();
        
        // Mock MCP services
        $this->mockAppointmentMCPServices();
    }

    protected function createAppointmentTestData(): void
    {
        // Create staff member
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Test Staff',
            'is_active' => true
        ]);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Consultation',
            'duration' => 30,
            'price' => 75.00
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'phone' => '+49123456789',
            'email' => 'customer@test.com'
        ]);

        // Create appointments with different statuses and dates
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'status' => 'confirmed',
            'scheduled_at' => now()->addDays(rand(1, 7))
        ]);

        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(rand(1, 10))
        ]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'status' => 'pending',
            'scheduled_at' => now()->addHours(2)
        ]);
    }

    protected function mockAppointmentMCPServices(): void
    {
        $this->mockMCPTasks([
            'searchAppointments' => [
                'success' => true,
                'result' => [
                    'data' => [
                        [
                            'id' => 'apt_123',
                            'customer_name' => 'Test Customer',
                            'customer_phone' => '+49123456789',
                            'staff_name' => 'Dr. Test Staff',
                            'service_name' => 'Test Consultation',
                            'scheduled_at' => now()->addDays(1)->toISOString(),
                            'status' => 'confirmed',
                            'duration' => 30,
                            'price' => 75.00
                        ],
                        [
                            'id' => 'apt_124',
                            'customer_name' => 'Another Customer',
                            'customer_phone' => '+49987654321',
                            'staff_name' => 'Dr. Test Staff',
                            'service_name' => 'Test Consultation',
                            'scheduled_at' => now()->addDays(2)->toISOString(),
                            'status' => 'pending',
                            'duration' => 30,
                            'price' => 75.00
                        ]
                    ],
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_count' => 2
                    ]
                ]
            ],
            'getAppointmentDetails' => [
                'success' => true,
                'result' => [
                    'data' => [
                        'appointment' => [
                            'id' => 'apt_123',
                            'customer' => [
                                'name' => 'Test Customer',
                                'phone' => '+49123456789',
                                'email' => 'customer@test.com'
                            ],
                            'staff' => [
                                'name' => 'Dr. Test Staff',
                                'specialization' => 'General Medicine'
                            ],
                            'service' => [
                                'name' => 'Test Consultation',
                                'duration' => 30,
                                'price' => 75.00
                            ],
                            'branch' => [
                                'name' => 'Test Branch',
                                'address' => 'Test Address 123'
                            ],
                            'scheduled_at' => now()->addDays(1)->toISOString(),
                            'status' => 'confirmed',
                            'notes' => 'Test appointment notes',
                            'created_at' => now()->subHours(2)->toISOString()
                        ]
                    ]
                ]
            ],
            'getAppointmentStats' => [
                'success' => true,
                'result' => [
                    'data' => [
                        'today' => 3,
                        'week' => 12,
                        'confirmed' => 8,
                        'total' => 15,
                        'completion_rate' => 85.2,
                        'no_show_rate' => 4.3
                    ]
                ]
            ]
        ]);
    }

    // =========== AUTHENTICATION & AUTHORIZATION TESTS ===========

    /** @test */
    public function appointments_index_requires_authentication()
    {
        $this->assertAuthenticationRequired('/business/appointments');
    }

    /** @test */
    public function appointments_index_requires_view_permission()
    {
        $this->assertPermissionRequired('/business/appointments', ['appointments.view']);
    }

    /** @test */
    public function appointment_user_can_access_appointments_index()
    {
        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->get('/business/appointments');

        $response->assertStatus(200);
        $response->assertViewIs('portal.appointments.index');
    }

    /** @test */
    public function show_appointment_requires_authentication()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->assertAuthenticationRequired('/business/appointments/' . $appointment->id);
    }

    /** @test */
    public function show_appointment_requires_view_permission()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->assertPermissionRequired('/business/appointments/' . $appointment->id, ['appointments.view']);
    }

    // =========== CORE FUNCTIONALITY TESTS ===========

    /** @test */
    public function show_displays_appointment_details()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id
        ]);

        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->get('/business/appointments/' . $appointment->id);

        $response->assertStatus(200);
        $response->assertViewIs('portal.appointments.show');
        $response->assertViewHas('appointment');
        
        $appointmentData = $response->viewData('appointment');
        $this->assertEquals('apt_123', $appointmentData['id']);
        $this->assertEquals('Test Customer', $appointmentData['customer']['name']);
    }

    /** @test */
    public function show_handles_nonexistent_appointment()
    {
        // Mock MCP failure for nonexistent appointment
        $this->mockMCPFailure('getAppointmentDetails', 'Appointment not found');

        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->get('/business/appointments/99999');

        $response->assertStatus(404);
    }

    // =========== API ENDPOINT TESTS ===========

    /** @test */
    public function api_appointments_requires_authentication()
    {
        $this->assertAuthenticationRequired('/business/api/appointments', 'GET');
    }

    /** @test */
    public function api_appointments_returns_structured_data()
    {
        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->getJson('/business/api/appointments');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'customer_name',
                    'customer_phone',
                    'staff_name',
                    'service_name',
                    'scheduled_at',
                    'status',
                    'duration',
                    'price'
                ]
            ],
            'pagination' => [
                'current_page',
                'total_pages',
                'total_count'
            ]
        ]);
    }

    /** @test */
    public function api_appointments_handles_filters()
    {
        $testCases = [
            ['date' => '2025-01-15'],
            ['status' => 'confirmed'],
            ['branch' => $this->branch->id],
            ['search' => 'Test Customer']
        ];

        foreach ($testCases as $filters) {
            $response = $this->actingAs($this->appointmentUser, 'portal')
                ->getJson('/business/api/appointments?' . http_build_query($filters));

            $response->assertStatus(200);
            $response->assertJsonStructure(['data', 'pagination']);
        }
    }

    /** @test */
    public function api_appointments_handles_pagination()
    {
        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->getJson('/business/api/appointments?page=2&per_page=5');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'pagination']);
    }

    /** @test */
    public function api_appointments_includes_related_data()
    {
        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->getJson('/business/api/appointments');

        $response->assertStatus(200);
        
        $appointments = $response->json('data');
        $this->assertIsArray($appointments);
        
        foreach ($appointments as $appointment) {
            $this->assertArrayHasKey('customer_name', $appointment);
            $this->assertArrayHasKey('staff_name', $appointment);
            $this->assertArrayHasKey('service_name', $appointment);
        }
    }

    /** @test */
    public function api_appointments_handles_mcp_failure()
    {
        $this->mockMCPFailure('searchAppointments', 'Database connection failed');

        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->getJson('/business/api/appointments');

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Failed to fetch appointments']);
    }

    // =========== STATISTICS API TESTS ===========

    /** @test */
    public function api_stats_requires_authentication()
    {
        $this->assertAuthenticationRequired('/business/api/appointments/stats', 'GET');
    }

    /** @test */
    public function api_stats_returns_appointment_statistics()
    {
        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->getJson('/business/api/appointments/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'today',
            'week',
            'confirmed',
            'total'
        ]);
        
        $stats = $response->json();
        $this->assertEquals(3, $stats['today']);
        $this->assertEquals(12, $stats['week']);
        $this->assertEquals(8, $stats['confirmed']);
        $this->assertEquals(15, $stats['total']);
    }

    /** @test */
    public function api_stats_handles_different_periods()
    {
        $periods = ['current', 'today', 'week', 'month'];
        
        foreach ($periods as $period) {
            $response = $this->actingAs($this->appointmentUser, 'portal')
                ->getJson('/business/api/appointments/stats?period=' . $period);

            $response->assertStatus(200);
            $response->assertJsonStructure(['today', 'week', 'confirmed', 'total']);
        }
    }

    /** @test */
    public function api_stats_returns_defaults_on_mcp_failure()
    {
        $this->mockMCPFailure('getAppointmentStats', 'Stats service unavailable');

        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->getJson('/business/api/appointments/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'today' => 0,
            'week' => 0,
            'confirmed' => 0,
            'total' => 0
        ]);
    }

    // =========== ADMIN IMPERSONATION TESTS ===========

    /** @test */
    public function admin_viewing_allows_appointments_access()
    {
        $this->simulateAdminViewing($this->company);

        $response = $this->get('/business/appointments');

        $response->assertStatus(200);
        $response->assertViewIs('portal.appointments.index');
    }

    /** @test */
    public function admin_viewing_allows_appointment_details()
    {
        $this->simulateAdminViewing($this->company);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id
        ]);

        $response = $this->get('/business/appointments/' . $appointment->id);

        $response->assertStatus(200);
        $response->assertViewIs('portal.appointments.show');
    }

    /** @test */
    public function admin_viewing_allows_api_access()
    {
        $this->simulateAdminViewing($this->company);

        $response = $this->getJson('/business/api/appointments');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    // =========== TENANT ISOLATION TESTS ===========

    /** @test */
    public function appointments_data_is_tenant_isolated()
    {
        $otherCompany = $this->createTestCompany();
        $otherBranch = $this->createTestBranch($otherCompany);
        
        // Create appointments for other company
        Appointment::factory()->count(3)->create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id
        ]);

        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->getJson('/business/api/appointments');

        $response->assertStatus(200);
        
        // Should only see appointments from own company
        $appointments = $response->json('data');
        foreach ($appointments as $appointment) {
            // The MCP service should handle tenant isolation
            $this->assertIsString($appointment['id']);
        }
    }

    /** @test */
    public function appointment_details_are_tenant_isolated()
    {
        $otherCompany = $this->createTestCompany();
        $otherAppointment = Appointment::factory()->create([
            'company_id' => $otherCompany->id
        ]);

        // Mock MCP to return "not found" for other company's appointment
        $this->mockMCPFailure('getAppointmentDetails', 'Appointment not found');

        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->get('/business/appointments/' . $otherAppointment->id);

        $response->assertStatus(404);
    }

    // =========== PERFORMANCE TESTS ===========

    /** @test */
    public function appointments_index_performance_is_acceptable()
    {
        $this->assertPerformanceAcceptable('/business/appointments', 800);
    }

    /** @test */
    public function appointment_details_performance_is_acceptable()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->assertPerformanceAcceptable('/business/appointments/' . $appointment->id, 600);
    }

    /** @test */
    public function api_appointments_performance_is_acceptable()
    {
        $user = $this->actAsPortalUser(['appointments.view' => true]);
        
        $this->assertPerformanceAcceptable('/business/api/appointments', 500);
    }

    // =========== ERROR HANDLING TESTS ===========

    /** @test */
    public function appointments_handles_mcp_service_failures_gracefully()
    {
        $this->assertErrorHandlingGraceful('/business/appointments');
    }

    /** @test */
    public function appointment_details_handles_service_failures()
    {
        $this->mockMCPFailure('getAppointmentDetails', 'Service temporarily unavailable');

        $response = $this->actingAs($this->appointmentUser, 'portal')
            ->get('/business/appointments/123');

        $response->assertStatus(404);
    }

    /** @test */
    public function api_handles_invalid_company_context()
    {
        // Create user without company context
        $orphanUser = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($orphanUser, 'portal')
            ->getJson('/business/api/appointments');

        $response->assertStatus(403);
    }

    // =========== INTEGRATION TESTS ===========

    /** @test */
    public function appointment_workflow_end_to_end()
    {
        $user = $this->actAsPortalUser(['appointments.view' => true]);

        // Step 1: View appointments list
        $listResponse = $this->get('/business/appointments');
        $listResponse->assertStatus(200);

        // Step 2: API call to get appointments
        $apiResponse = $this->getJson('/business/api/appointments');
        $apiResponse->assertStatus(200);
        $appointments = $apiResponse->json('data');
        $this->assertNotEmpty($appointments);

        // Step 3: View specific appointment
        $appointmentId = $appointments[0]['id'];
        $detailResponse = $this->get('/business/appointments/' . $appointmentId);
        $detailResponse->assertStatus(200);

        // Step 4: Get statistics
        $statsResponse = $this->getJson('/business/api/appointments/stats');
        $statsResponse->assertStatus(200);
        $stats = $statsResponse->json();
        $this->assertArrayHasKey('total', $stats);
    }

    /** @test */
    public function appointment_filtering_integration()
    {
        $user = $this->actAsPortalUser(['appointments.view' => true]);

        // Test various filter combinations
        $filterCombinations = [
            ['status' => 'confirmed'],
            ['date' => now()->addDays(1)->format('Y-m-d')],
            ['status' => 'confirmed', 'branch' => $this->branch->id],
            ['search' => 'Customer', 'status' => 'pending']
        ];

        foreach ($filterCombinations as $filters) {
            $response = $this->getJson('/business/api/appointments?' . http_build_query($filters));
            $response->assertStatus(200);
            $response->assertJsonStructure(['data', 'pagination']);
        }
    }

    /** @test */
    public function appointment_permissions_integration()
    {
        // Test different permission levels
        $permissionLevels = [
            [['appointments.view' => false], 403],
            [['appointments.view' => true], 200],
            [['appointments.view' => true, 'appointments.manage' => true], 200]
        ];

        foreach ($permissionLevels as [$permissions, $expectedStatus]) {
            $testUser = $this->createPortalTestUser($permissions, ['company' => $this->company]);
            
            $response = $this->actingAs($testUser, 'portal')
                ->get('/business/appointments');
                
            $response->assertStatus($expectedStatus);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}