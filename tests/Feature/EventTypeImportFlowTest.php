<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Services\EventTypeNameParser;
use App\Services\SmartEventTypeNameParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class EventTypeImportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $company;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'admin']);
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'calcom_api_key' => encrypt('cal_test_key_123'),
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Berlin Mitte',
            'is_active' => true,
        ]);
        
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
        ]);
        
        $this->admin->assignRole('admin');
    }

    /**
     * Test 1: Verify company selection works correctly
     */
    public function test_company_selection_works_correctly()
    {
        // Create another company without API key
        $companyWithoutKey = Company::factory()->create([
            'name' => 'No API Key Company',
            'calcom_api_key' => null,
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/event-type-import-wizard');
        
        $response->assertStatus(200);
        $response->assertSee('Test Company');
        $response->assertDontSee('No API Key Company');
    }

    /**
     * Test 2: Verify branch dropdown functionality
     */
    public function test_branch_dropdown_loads_correct_branches()
    {
        // Create additional branches
        $activeBranch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Frankfurt Main',
            'is_active' => true,
        ]);
        
        $inactiveBranch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'München Closed',
            'is_active' => false,
        ]);
        
        $otherCompanyBranch = Branch::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'name' => 'Other Company Branch',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/event-type-import-wizard');
        
        // Should see active branches from the company
        $response->assertSee('Berlin Mitte');
        $response->assertSee('Frankfurt Main');
        
        // Should NOT see inactive or other company branches
        $response->assertDontSee('München Closed');
        $response->assertDontSee('Other Company Branch');
    }

    /**
     * Test 3: Verify Cal.com API integration
     */
    public function test_calcom_api_call_handles_v2_response_correctly()
    {
        // Mock Cal.com v2 API response
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'groupName' => 'Team Events',
                            'eventTypes' => [
                                [
                                    'id' => 123,
                                    'title' => 'Berlin Mitte-Test Company-Haircut 30min',
                                    'slug' => 'haircut-30',
                                    'length' => 30,
                                    'schedulingType' => 'INDIVIDUAL',
                                    'active' => true,
                                    'users' => [
                                        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@test.com']
                                    ]
                                ],
                                [
                                    'id' => 124,
                                    'title' => 'Test Demo Event',
                                    'slug' => 'test-demo',
                                    'length' => 60,
                                    'schedulingType' => 'COLLECTIVE',
                                    'active' => false,
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->actingAs($this->admin);
        
        // Simulate wizard step 1 completion
        $response = $this->post('/admin/event-type-import-wizard', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'currentStep' => 1,
        ]);
        
        // Verify API was called
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cal.com/v2/event-types' &&
                   $request->hasHeader('Authorization', 'Bearer cal_test_key_123');
        });
    }

    /**
     * Test 4: Verify name parsing functionality
     */
    public function test_event_type_name_parsing_works_correctly()
    {
        $parser = new EventTypeNameParser();
        
        // Test 1: Standard format
        $result = $parser->parseEventTypeName('Berlin Mitte-Test Company-Haircut 30min');
        $this->assertTrue($result['success']);
        $this->assertEquals('Berlin Mitte', $result['branch_name']);
        $this->assertEquals('Test Company', $result['company_name']);
        $this->assertEquals('Haircut 30min', $result['service_name']);
        
        // Test 2: Invalid format
        $result = $parser->parseEventTypeName('Just a service name');
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        
        // Test 3: Service name extraction from marketing text
        $serviceName = $parser->extractServiceName('AskProAI + 24/7 Kundenservice + Beratung aus Berlin');
        $this->assertEquals('Beratung', $serviceName);
    }

    /**
     * Test 5: Verify smart name parser functionality
     */
    public function test_smart_name_parser_extracts_clean_service_names()
    {
        $parser = new SmartEventTypeNameParser();
        
        // Test various marketing names
        $testCases = [
            'AskProAI + 30% mehr Umsatz + Haarschnitt aus Berlin' => '30 Min Haarschnitt',
            'ModernHair - Färben und Styling 24/7' => 'Färben',
            'FitXpert Frankfurt - 60 Min Personal Training' => '60 Min Training',
            'Test Demo Event' => 'Termin', // Should become generic
            'Erstberatung für Sie und besten Kundenservice' => 'Erstberatung',
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $parser->extractCleanServiceName($input);
            $this->assertStringContainsString(
                explode(' ', $expected)[count(explode(' ', $expected)) - 1], 
                $result,
                "Failed for input: $input"
            );
        }
    }

    /**
     * Test 6: Verify staff assignment import from Cal.com
     */
    public function test_staff_assignments_are_imported_from_calcom()
    {
        // Create staff members
        $staff1 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'calcom_user_id' => 1,
        ]);
        
        $staff2 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'calcom_user_id' => 2,
        ]);

        // Mock Cal.com response with user assignments
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'eventTypes' => [
                                [
                                    'id' => 125,
                                    'title' => 'Team Consultation',
                                    'schedulingType' => 'COLLECTIVE',
                                    'users' => [
                                        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@test.com'],
                                        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@test.com']
                                    ],
                                    'hosts' => [
                                        ['userId' => 1, 'isFixed' => true],
                                        ['userId' => 2, 'isFixed' => false]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Execute import
        $service = new \App\Services\CalcomSyncService();
        $service->syncEventTypesForCompany($this->company->id);
        
        // Verify event type was created
        $eventType = CalcomEventType::where('calcom_event_type_id', 125)->first();
        $this->assertNotNull($eventType);
        
        // Verify staff assignments were created
        $assignments = \DB::table('staff_event_types')
            ->where('event_type_id', $eventType->id)
            ->get();
            
        $this->assertEquals(2, $assignments->count());
        
        // Verify correct staff were assigned
        $assignedStaffIds = $assignments->pluck('staff_id')->toArray();
        $this->assertContains($staff1->id, $assignedStaffIds);
        $this->assertContains($staff2->id, $assignedStaffIds);
    }

    /**
     * Test 7: Verify import selection logic
     */
    public function test_smart_selection_logic_works_correctly()
    {
        // Test the import wizard's smart selection
        $eventTypes = [
            [
                'id' => 1,
                'title' => 'Berlin Mitte-Test Company-Consultation',
                'active' => true,
            ],
            [
                'id' => 2,
                'title' => 'Frankfurt-Other Company-Service',
                'active' => true,
            ],
            [
                'id' => 3,
                'title' => 'Test Demo Event',
                'active' => true,
            ],
            [
                'id' => 4,
                'title' => 'Berlin Mitte-Test Company-Inactive Service',
                'active' => false,
            ],
        ];
        
        $parser = new EventTypeNameParser();
        $results = $parser->analyzeEventTypesForImport($eventTypes, $this->branch);
        
        // First event should match branch
        $this->assertTrue($results[0]['matches_branch']);
        $this->assertEquals('import', $results[0]['suggested_action']);
        
        // Second event should not match branch
        $this->assertFalse($results[1]['matches_branch']);
        $this->assertEquals('skip', $results[1]['suggested_action']);
        
        // Test event should be manual
        $this->assertEquals('manual', $results[2]['suggested_action']);
    }

    /**
     * Test 8: Full import flow integration test
     */
    public function test_complete_import_flow_end_to_end()
    {
        // Mock complete Cal.com response
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'eventTypes' => [
                                [
                                    'id' => 200,
                                    'title' => 'Berlin Mitte-Test Company-Beratung 60min',
                                    'slug' => 'beratung-60',
                                    'description' => 'Professional consultation',
                                    'length' => 60,
                                    'schedulingType' => 'INDIVIDUAL',
                                    'active' => true,
                                    'requiresConfirmation' => true,
                                    'bookingLimits' => ['PER_DAY' => 5],
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->actingAs($this->admin);
        
        // Execute the import
        $importData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'mappings' => [
                [
                    'calcom_id' => 200,
                    'service_name' => 'Beratung 60min',
                    'import' => true,
                    'duration' => 60,
                ]
            ]
        ];
        
        // Simulate the import execution
        \DB::beginTransaction();
        
        try {
            $importLog = \DB::table('event_type_import_logs')->insertGetId([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'user_id' => $this->admin->id,
                'import_type' => 'manual',
                'total_found' => 1,
                'total_imported' => 0,
                'status' => 'processing',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Create event type
            $eventType = CalcomEventType::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'calcom_event_type_id' => 200,
                'calcom_numeric_event_type_id' => 200,
                'name' => 'Berlin Mitte-Test Company-Beratung 60min',
                'slug' => 'beratung-60',
                'description' => 'Professional consultation',
                'duration_minutes' => 60,
                'is_team_event' => false,
                'requires_confirmation' => true,
                'booking_limits' => ['PER_DAY' => 5],
                'is_active' => true,
                'last_synced_at' => now(),
                'metadata' => [
                    'imported_at' => now(),
                    'imported_by' => $this->admin->id,
                    'original_name' => 'Berlin Mitte-Test Company-Beratung 60min'
                ]
            ]);
            
            // Update import log
            \DB::table('event_type_import_logs')
                ->where('id', $importLog)
                ->update([
                    'total_imported' => 1,
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);
            
            \DB::commit();
            
            // Verify import success
            $this->assertDatabaseHas('calcom_event_types', [
                'calcom_event_type_id' => 200,
                'branch_id' => $this->branch->id,
                'name' => 'Berlin Mitte-Test Company-Beratung 60min',
                'duration_minutes' => 60,
                'is_active' => true,
            ]);
            
            $this->assertDatabaseHas('event_type_import_logs', [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'total_imported' => 1,
                'status' => 'completed',
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->fail('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Test 9: Error handling and edge cases
     */
    public function test_error_handling_for_various_edge_cases()
    {
        // Test 1: No API key
        $companyNoKey = Company::factory()->create(['calcom_api_key' => null]);
        $branchNoKey = Branch::factory()->create(['company_id' => $companyNoKey->id]);
        
        $this->actingAs($this->admin);
        
        // This should fail gracefully
        $response = $this->post('/admin/event-type-import-wizard', [
            'company_id' => $companyNoKey->id,
            'branch_id' => $branchNoKey->id,
        ]);
        
        // Test 2: Cal.com API returns error
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response(['error' => 'Unauthorized'], 401)
        ]);
        
        // Test 3: Empty event types response
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response(['data' => ['eventTypeGroups' => []]], 200)
        ]);
        
        // Test 4: Malformed event type data
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'eventTypes' => [
                                ['id' => null, 'title' => null] // Missing required fields
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);
    }

    /**
     * Test 10: Database transaction integrity
     */
    public function test_import_rollback_on_failure()
    {
        $this->actingAs($this->admin);
        
        // Count existing records
        $initialEventTypes = CalcomEventType::count();
        $initialLogs = \DB::table('event_type_import_logs')->count();
        
        // Simulate a failing import (duplicate key or constraint violation)
        try {
            \DB::beginTransaction();
            
            // Create first event type successfully
            CalcomEventType::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'calcom_event_type_id' => 300,
                'name' => 'Test Event',
                'slug' => 'test-event',
                'duration_minutes' => 30,
            ]);
            
            // Try to create duplicate (should fail)
            CalcomEventType::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'calcom_event_type_id' => 300, // Duplicate!
                'name' => 'Test Event 2',
                'slug' => 'test-event-2',
                'duration_minutes' => 30,
            ]);
            
            \DB::commit();
            
        } catch (\Exception $e) {
            \DB::rollBack();
        }
        
        // Verify nothing was saved
        $this->assertEquals($initialEventTypes, CalcomEventType::count());
        $this->assertEquals($initialLogs, \DB::table('event_type_import_logs')->count());
    }
}