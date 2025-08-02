<?php

namespace Tests\Feature\Portal;

use Tests\TestCase;
use Tests\Traits\PortalTestHelpers;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Staff;
use App\Traits\UsesMCPServers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use Mockery;

class CallControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, PortalTestHelpers;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected User $callUser;
    protected User $adminUser;
    protected Staff $staff;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and branch
        $this->company = $this->createTestCompany([
            'call_management_enabled' => true,
            'call_assignment_enabled' => true
        ]);
        
        $this->branch = $this->createTestBranch($this->company);

        // Create users with different permission levels
        $this->user = $this->createPortalTestUser([
            'calls.view' => false,
            'calls.manage' => false
        ], ['company' => $this->company]);

        $this->callUser = $this->createPortalTestUser([
            'calls.view' => true,
            'calls.view_own' => true,
            'calls.manage' => true,
            'calls.assign' => true
        ], ['company' => $this->company]);

        $this->adminUser = $this->createPortalTestUser([
            'calls.view' => true,
            'calls.view_all' => true,
            'calls.manage' => true,
            'calls.assign' => true,
            'calls.export' => true,
            'admin.all' => true
        ], ['company' => $this->company]);

        // Create related test data
        $this->createCallTestData();
        
        // Mock MCP services
        $this->mockCallMCPServices();
    }

    protected function createCallTestData(): void
    {
        // Create staff member
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Test Call Handler',
            'is_active' => true
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Call Customer',
            'phone' => '+49123456789',
            'email' => 'callcustomer@test.com'
        ]);

        // Create calls with different statuses
        Call::factory()->count(6)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->staff->id,
            'status' => 'completed',
            'call_ended_at' => now()->subHours(rand(1, 24)),
            'duration' => rand(60, 300)
        ]);

        Call::factory()->count(4)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'in_progress',
            'call_started_at' => now()->subMinutes(rand(5, 30))
        ]);

        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'requires_action',
            'call_ended_at' => now()->subHours(rand(2, 8))
        ]);

        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'assigned_to' => $this->callUser->id,
            'status' => 'callback_scheduled',
            'callback_scheduled_at' => now()->addHours(rand(1, 6))
        ]);
    }

    protected function mockCallMCPServices(): void
    {
        $this->mockMCPTasks([
            'getCall' => [
                'success' => true,
                'result' => [
                    'data' => [
                        'id' => 'call_123',
                        'customer_phone' => '+49123456789',
                        'customer_name' => 'Test Call Customer',
                        'status' => 'completed',
                        'duration' => 185,
                        'call_started_at' => now()->subHours(2)->toISOString(),
                        'call_ended_at' => now()->subHours(2)->addMinutes(3)->toISOString(),
                        'transcript' => 'Hello, this is a test call transcript...',
                        'summary' => 'Customer called regarding appointment booking.',
                        'sentiment' => 'positive',
                        'assigned_to' => [
                            'id' => $this->staff->id,
                            'name' => 'Test Call Handler'
                        ],
                        'branch' => [
                            'id' => $this->branch->id,
                            'name' => $this->branch->name
                        ],
                        'notes' => [
                            [
                                'id' => 'note_1',
                                'content' => 'Customer was very satisfied with service',
                                'created_by' => 'Test User',
                                'created_at' => now()->subHours(1)->toISOString(),
                                'is_internal' => true
                            ]
                        ]
                    ]
                ]
            ],
            'listCalls' => [
                'success' => true,
                'result' => [
                    'data' => [
                        'data' => [
                            [
                                'id' => 'call_123',
                                'customer_phone' => '+49123456789',
                                'customer_name' => 'Test Customer 1',
                                'status' => 'completed',
                                'duration' => 185,
                                'call_started_at' => now()->subHours(2)->toISOString(),
                                'assigned_to' => 'Test Handler'
                            ],
                            [
                                'id' => 'call_124',
                                'customer_phone' => '+49987654321',
                                'customer_name' => 'Test Customer 2',
                                'status' => 'in_progress',
                                'duration' => 0,
                                'call_started_at' => now()->subMinutes(15)->toISOString(),
                                'assigned_to' => null
                            ]
                        ],
                        'pagination' => [
                            'current_page' => 1,
                            'total_pages' => 3,
                            'total_count' => 25,
                            'per_page' => 20
                        ]
                    ]
                ]
            ],
            'getCallStats' => [
                'success' => true,
                'result' => [
                    'data' => [
                        'total_calls' => 25,
                        'completed_calls' => 18,
                        'in_progress_calls' => 4,
                        'requires_action' => 3,
                        'average_duration' => 165,
                        'conversion_rate' => 72.5,
                        'callback_scheduled' => 2
                    ]
                ]
            ],
            'updateCallStatus' => [
                'success' => true,
                'result' => [
                    'success' => true,
                    'message' => 'Call status updated successfully',
                    'call' => [
                        'id' => 'call_123',
                        'status' => 'completed',
                        'updated_at' => now()->toISOString()
                    ]
                ]
            ],
            'assignCall' => [
                'success' => true,
                'result' => [
                    'success' => true,
                    'message' => 'Call assigned successfully',
                    'assignment' => [
                        'call_id' => 'call_123',
                        'assigned_to' => $this->staff->id,
                        'assigned_at' => now()->toISOString()
                    ]
                ]
            ],
            'addCallNote' => [
                'success' => true,
                'result' => [
                    'success' => true,
                    'message' => 'Note added successfully',
                    'note' => [
                        'id' => 'note_123',
                        'content' => 'Test note content',
                        'created_at' => now()->toISOString()
                    ]
                ]
            ],
            'scheduleCallback' => [
                'success' => true,
                'result' => [
                    'success' => true,
                    'message' => 'Callback scheduled successfully',
                    'callback' => [
                        'call_id' => 'call_123',
                        'scheduled_at' => now()->addHours(2)->toISOString()
                    ]
                ]
            ],
            'exportCalls' => [
                'success' => true,
                'result' => [
                    'success' => true,
                    'export' => [
                        'path' => '/tmp/calls_export_test.csv',
                        'filename' => 'calls_export_2025-01-01.csv',
                        'headers' => [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename=calls_export.csv'
                        ]
                    ]
                ]
            ]
        ]);
    }

    // =========== AUTHENTICATION & AUTHORIZATION TESTS ===========

    /** @test */
    public function calls_index_requires_authentication()
    {
        $this->assertAuthenticationRequired('/business/calls');
    }

    /** @test */
    public function calls_index_redirects_unauthenticated_users()
    {
        $response = $this->get('/business/calls');
        
        $response->assertRedirect('/business/login');
    }

    /** @test */
    public function authenticated_user_with_admin_viewing_can_access_calls()
    {
        $this->simulateAdminViewing($this->company);

        $response = $this->get('/business/calls');

        $response->assertStatus(200);
        $response->assertViewIs('portal.calls.index');
    }

    /** @test */
    public function call_user_can_access_calls_index()
    {
        $response = $this->actingAs($this->callUser, 'portal')
            ->get('/business/calls');

        $response->assertStatus(200);
        $response->assertViewIs('portal.calls.index');
    }

    /** @test */
    public function show_call_requires_authentication()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $this->assertAuthenticationRequired('/business/calls/' . $call->id);
    }

    // =========== CORE FUNCTIONALITY TESTS ===========

    /** @test */
    public function show_displays_call_details()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id
        ]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->get('/business/calls/' . $call->id);

        $response->assertStatus(200);
        $response->assertViewIs('portal.calls.show');
        $response->assertViewHas('call');
        
        $callData = $response->viewData('call');
        $this->assertEquals('call_123', $callData['id']);
        $this->assertEquals('+49123456789', $callData['customer_phone']);
        $this->assertEquals('completed', $callData['status']);
    }

    /** @test */
    public function show_handles_nonexistent_call()
    {
        $this->mockMCPFailure('getCall', 'Call not found');

        $response = $this->actingAs($this->callUser, 'portal')
            ->get('/business/calls/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function show_handles_no_company_context()
    {
        $orphanUser = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($orphanUser, 'portal')
            ->get('/business/calls/123');

        $response->assertStatus(403);
    }

    // =========== API ENDPOINT TESTS ===========

    /** @test */
    public function api_calls_requires_authentication()
    {
        $this->assertAuthenticationRequired('/business/api/calls', 'GET');
    }

    /** @test */
    public function api_calls_requires_company_context()
    {
        $orphanUser = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($orphanUser, 'portal')
            ->getJson('/business/api/calls');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'No company context']);
    }

    /** @test */
    public function api_calls_returns_structured_data()
    {
        $response = $this->actingAs($this->callUser, 'portal')
            ->getJson('/business/api/calls');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'customer_phone',
                    'customer_name',
                    'status',
                    'duration',
                    'call_started_at'
                ]
            ],
            'pagination' => [
                'current_page',
                'total_pages',
                'total_count',
                'per_page'
            ]
        ]);
    }

    /** @test */
    public function api_calls_handles_filters()
    {
        $testFilters = [
            ['status' => 'completed'],
            ['date_from' => '2025-01-01', 'date_to' => '2025-01-31'],
            ['search' => 'Test Customer'],
            ['branch_id' => $this->branch->id],
            ['assigned_to' => $this->staff->id]
        ];

        foreach ($testFilters as $filters) {
            $response = $this->actingAs($this->callUser, 'portal')
                ->getJson('/business/api/calls?' . http_build_query($filters));

            $response->assertStatus(200);
            $response->assertJsonStructure(['data', 'pagination']);
        }
    }

    /** @test */
    public function api_calls_handles_pagination()
    {
        $response = $this->actingAs($this->callUser, 'portal')
            ->getJson('/business/api/calls?page=2&per_page=10');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'pagination']);
        
        $data = $response->json();
        $this->assertEquals(1, $data['pagination']['current_page']);
    }

    /** @test */
    public function api_calls_handles_mcp_failure()
    {
        $this->mockMCPFailure('listCalls', 'Database connection failed');

        $response = $this->actingAs($this->callUser, 'portal')
            ->getJson('/business/api/calls');

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Failed to fetch calls']);
    }

    // =========== CALL MANAGEMENT TESTS ===========

    /** @test */
    public function update_call_status_validates_input()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->putJson('/business/calls/' . $call->id . '/status', [
                'status' => 'invalid_status'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function update_call_status_accepts_valid_statuses()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);
        $validStatuses = ['new', 'in_progress', 'completed', 'requires_action', 'callback_scheduled'];

        foreach ($validStatuses as $status) {
            $response = $this->actingAs($this->callUser, 'portal')
                ->putJson('/business/calls/' . $call->id . '/status', [
                    'status' => $status,
                    'notes' => 'Status updated to ' . $status
                ]);

            $response->assertStatus(200);
            $response->assertJsonStructure(['success', 'message', 'call']);
        }
    }

    /** @test */
    public function update_call_status_handles_mcp_failure()
    {
        $this->mockMCPFailure('updateCallStatus', 'Failed to update status');
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->putJson('/business/calls/' . $call->id . '/status', [
                'status' => 'completed'
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to update status'
        ]);
    }

    /** @test */
    public function assign_call_validates_input()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->postJson('/business/calls/' . $call->id . '/assign', [
                'user_id' => 'invalid'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    /** @test */
    public function assign_call_succeeds_with_valid_data()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->postJson('/business/calls/' . $call->id . '/assign', [
                'user_id' => $this->staff->id,
                'notes' => 'Assigned to specialist'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'assignment']);
    }

    /** @test */
    public function add_call_note_validates_content()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->postJson('/business/calls/' . $call->id . '/notes', [
                'note' => ''
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['note']);
    }

    /** @test */
    public function add_call_note_succeeds_with_valid_content()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->postJson('/business/calls/' . $call->id . '/notes', [
                'note' => 'Customer follow-up required',
                'is_internal' => true
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'note']);
    }

    /** @test */
    public function schedule_callback_validates_datetime()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->postJson('/business/calls/' . $call->id . '/callback', [
                'callback_at' => 'invalid-date'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['callback_at']);
    }

    /** @test */
    public function schedule_callback_rejects_past_dates()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->postJson('/business/calls/' . $call->id . '/callback', [
                'callback_at' => now()->subHour()->toDateTimeString()
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['callback_at']);
    }

    /** @test */
    public function schedule_callback_succeeds_with_future_date()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->callUser, 'portal')
            ->postJson('/business/calls/' . $call->id . '/callback', [
                'callback_at' => now()->addHours(2)->toDateTimeString(),
                'notes' => 'Follow up on appointment availability'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'callback']);
    }

    // =========== STATISTICS API TESTS ===========

    /** @test */
    public function api_stats_requires_authentication()
    {
        $this->assertAuthenticationRequired('/business/api/calls/stats', 'GET');
    }

    /** @test */
    public function api_stats_requires_company_context()
    {
        $orphanUser = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($orphanUser, 'portal')
            ->getJson('/business/api/calls/stats');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'No company context']);
    }

    /** @test */
    public function api_stats_returns_call_statistics()
    {
        $response = $this->actingAs($this->callUser, 'portal')
            ->getJson('/business/api/calls/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_calls',
            'completed_calls', 
            'in_progress_calls',
            'requires_action',
            'average_duration',
            'conversion_rate'
        ]);
    }

    /** @test */
    public function api_stats_handles_different_periods()
    {
        $periods = ['today', 'week', 'month'];
        
        foreach ($periods as $period) {
            $response = $this->actingAs($this->callUser, 'portal')
                ->getJson('/business/api/calls/stats?period=' . $period);

            $response->assertStatus(200);
            $response->assertJsonStructure(['total_calls', 'completed_calls']);
        }
    }

    /** @test */
    public function api_stats_handles_branch_filtering()
    {
        $response = $this->actingAs($this->callUser, 'portal')
            ->getJson('/business/api/calls/stats?branch_id=' . $this->branch->id);

        $response->assertStatus(200);
        $response->assertJsonStructure(['total_calls', 'completed_calls']);
    }

    // =========== EXPORT FUNCTIONALITY TESTS ===========

    /** @test */
    public function export_calls_validates_format()
    {
        $response = $this->actingAs($this->adminUser, 'portal')
            ->postJson('/business/calls/export', [
                'format' => 'invalid_format'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function export_calls_supports_valid_formats()
    {
        $validFormats = ['csv', 'excel', 'pdf'];
        
        foreach ($validFormats as $format) {
            $response = $this->actingAs($this->adminUser, 'portal')
                ->postJson('/business/calls/export', [
                    'format' => $format
                ]);

            $response->assertStatus(200);
            $response->assertHeader('Content-Type');
        }
    }

    /** @test */
    public function export_calls_handles_call_ids_filter()
    {
        $callIds = ['call_123', 'call_456', 'call_789'];

        $response = $this->actingAs($this->adminUser, 'portal')
            ->postJson('/business/calls/export', [
                'format' => 'csv',
                'call_ids' => $callIds
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function export_calls_requires_company_context()
    {
        $orphanUser = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($orphanUser, 'portal')
            ->postJson('/business/calls/export', [
                'format' => 'csv'
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function export_calls_handles_mcp_failure()
    {
        $this->mockMCPFailure('exportCalls', 'Export service unavailable');

        $response = $this->actingAs($this->adminUser, 'portal')
            ->postJson('/business/calls/export', [
                'format' => 'csv'
            ]);

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Export failed']);
    }

    // =========== TENANT ISOLATION TESTS ===========

    /** @test */
    public function calls_data_is_tenant_isolated()
    {
        $this->assertTenantIsolation('/business/api/calls');
    }

    /** @test */
    public function call_details_are_tenant_isolated()
    {
        $otherCompany = $this->createTestCompany();
        $otherCall = Call::factory()->create(['company_id' => $otherCompany->id]);

        $this->mockMCPFailure('getCall', 'Call not found');

        $response = $this->actingAs($this->callUser, 'portal')
            ->get('/business/calls/' . $otherCall->id);

        $response->assertStatus(404);
    }

    // =========== PERFORMANCE TESTS ===========

    /** @test */
    public function calls_index_performance_is_acceptable()
    {
        $this->assertPerformanceAcceptable('/business/calls', 800);
    }

    /** @test */
    public function call_details_performance_is_acceptable()
    {
        $call = Call::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertPerformanceAcceptable('/business/calls/' . $call->id, 600);
    }

    /** @test */
    public function api_calls_performance_is_acceptable()
    {
        $user = $this->actAsPortalUser(['calls.view' => true]);
        
        $this->assertPerformanceAcceptable('/business/api/calls', 500);
    }

    // =========== ERROR HANDLING TESTS ===========

    /** @test */
    public function calls_handles_mcp_service_failures_gracefully()
    {
        $this->assertErrorHandlingGraceful('/business/calls');
    }

    // =========== INTEGRATION TESTS ===========

    /** @test */
    public function call_management_workflow_end_to_end()
    {
        $user = $this->actAsPortalUser([
            'calls.view' => true,
            'calls.manage' => true,
            'calls.assign' => true
        ]);

        // Step 1: View calls list
        $listResponse = $this->get('/business/calls');
        $listResponse->assertStatus(200);

        // Step 2: Get calls via API
        $apiResponse = $this->getJson('/business/api/calls');
        $apiResponse->assertStatus(200);
        $calls = $apiResponse->json('data');
        $this->assertNotEmpty($calls);

        // Step 3: View specific call
        $callId = $calls[0]['id'];
        $detailResponse = $this->get('/business/calls/' . $callId);
        $detailResponse->assertStatus(200);

        // Step 4: Update call status
        $statusResponse = $this->putJson('/business/calls/' . $callId . '/status', [
            'status' => 'in_progress',
            'notes' => 'Starting call review'
        ]);
        $statusResponse->assertStatus(200);

        // Step 5: Add note
        $noteResponse = $this->postJson('/business/calls/' . $callId . '/notes', [
            'note' => 'Customer expressed satisfaction',
            'is_internal' => false
        ]);
        $noteResponse->assertStatus(200);

        // Step 6: Schedule callback
        $callbackResponse = $this->postJson('/business/calls/' . $callId . '/callback', [
            'callback_at' => now()->addHours(2)->toDateTimeString(),
            'notes' => 'Follow up on additional questions'
        ]);
        $callbackResponse->assertStatus(200);

        // Step 7: Get updated statistics
        $statsResponse = $this->getJson('/business/api/calls/stats');
        $statsResponse->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}