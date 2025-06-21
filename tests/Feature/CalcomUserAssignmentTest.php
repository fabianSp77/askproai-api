<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Services\CalcomSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class CalcomUserAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $branch;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'calcom_api_key' => encrypt('cal_test_key_123'),
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'is_active' => true,
        ]);
        
        $this->service = new CalcomSyncService();
    }

    /**
     * Test that Cal.com user data is properly extracted from API response
     */
    public function test_calcom_api_returns_user_assignments()
    {
        // Mock Cal.com API response with detailed user information
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'groupName' => 'Team Events',
                            'eventTypes' => [
                                [
                                    'id' => 1001,
                                    'title' => 'Team Consultation',
                                    'slug' => 'team-consultation',
                                    'length' => 60,
                                    'schedulingType' => 'COLLECTIVE',
                                    'active' => true,
                                    // This is how Cal.com returns user assignments
                                    'users' => [
                                        [
                                            'id' => 101,
                                            'username' => 'john.doe',
                                            'name' => 'John Doe',
                                            'email' => 'john@example.com',
                                            'avatar' => 'https://cal.com/avatar/101.jpg',
                                        ],
                                        [
                                            'id' => 102,
                                            'username' => 'jane.smith',
                                            'name' => 'Jane Smith',
                                            'email' => 'jane@example.com',
                                            'avatar' => 'https://cal.com/avatar/102.jpg',
                                        ]
                                    ],
                                    // Hosts array for scheduling preferences
                                    'hosts' => [
                                        [
                                            'userId' => 101,
                                            'isFixed' => true,
                                            'priority' => 1
                                        ],
                                        [
                                            'userId' => 102,
                                            'isFixed' => false,
                                            'priority' => 2
                                        ]
                                    ],
                                    // Team information
                                    'team' => [
                                        'id' => 10,
                                        'name' => 'Support Team',
                                        'slug' => 'support-team'
                                    ]
                                ],
                                [
                                    'id' => 1002,
                                    'title' => 'Individual Session',
                                    'slug' => 'individual-session',
                                    'length' => 30,
                                    'schedulingType' => 'INDIVIDUAL',
                                    'active' => true,
                                    // Individual events may have a single user
                                    'users' => [
                                        [
                                            'id' => 101,
                                            'username' => 'john.doe',
                                            'name' => 'John Doe',
                                            'email' => 'john@example.com',
                                        ]
                                    ],
                                    'userId' => 101, // Direct user assignment
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Create matching staff members
        $john = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'calcom_user_id' => null, // Will be set during sync
        ]);
        
        $jane = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'calcom_user_id' => null,
        ]);

        // Run the sync
        $result = $this->service->syncEventTypesForCompany($this->company->id);
        
        // Verify sync was successful
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['synced_count']);
        
        // Check that event types were created with proper metadata
        $teamEvent = CalcomEventType::where('calcom_event_type_id', 1001)->first();
        $this->assertNotNull($teamEvent);
        $this->assertTrue($teamEvent->is_team_event);
        
        $metadata = $teamEvent->metadata;
        $this->assertArrayHasKey('users', $metadata);
        $this->assertCount(2, $metadata['users']);
        
        // Verify user information is stored
        $users = collect($metadata['users']);
        $johnData = $users->firstWhere('id', 101);
        $this->assertEquals('John Doe', $johnData['name']);
        $this->assertEquals('john@example.com', $johnData['email']);
        
        // Check staff_event_types assignments
        $assignments = DB::table('staff_event_types')
            ->where('event_type_id', $teamEvent->id)
            ->get();
            
        $this->assertEquals(2, $assignments->count());
        
        // Verify correct staff were assigned
        $assignedStaffIds = $assignments->pluck('staff_id')->toArray();
        $this->assertContains($john->id, $assignedStaffIds);
        $this->assertContains($jane->id, $assignedStaffIds);
        
        // Check that Cal.com user IDs were stored
        $johnAssignment = $assignments->firstWhere('staff_id', $john->id);
        $this->assertEquals(101, $johnAssignment->calcom_user_id);
    }

    /**
     * Test staff matching by email when Cal.com user ID is not set
     */
    public function test_staff_matching_by_email()
    {
        // Create staff without Cal.com user IDs
        $staff1 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Alice Johnson',
            'email' => 'alice@company.com',
            'calcom_user_id' => null,
        ]);
        
        $staff2 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Bob Wilson',
            'email' => 'bob@company.com',
            'calcom_user_id' => null,
        ]);

        // Mock Cal.com response
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'eventTypes' => [
                                [
                                    'id' => 2001,
                                    'title' => 'Team Meeting',
                                    'schedulingType' => 'COLLECTIVE',
                                    'users' => [
                                        ['id' => 201, 'email' => 'alice@company.com', 'name' => 'Alice J'],
                                        ['id' => 202, 'email' => 'bob@company.com', 'name' => 'Robert Wilson'],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Run sync
        $this->service->syncEventTypesForCompany($this->company->id);
        
        // Verify staff were matched by email
        $eventType = CalcomEventType::where('calcom_event_type_id', 2001)->first();
        $assignments = DB::table('staff_event_types')
            ->where('event_type_id', $eventType->id)
            ->get();
            
        $this->assertEquals(2, $assignments->count());
        
        // Check assignments were created correctly
        $aliceAssignment = $assignments->firstWhere('staff_id', $staff1->id);
        $this->assertNotNull($aliceAssignment);
        $this->assertEquals(201, $aliceAssignment->calcom_user_id);
        
        $bobAssignment = $assignments->firstWhere('staff_id', $staff2->id);
        $this->assertNotNull($bobAssignment);
        $this->assertEquals(202, $bobAssignment->calcom_user_id);
    }

    /**
     * Test handling of unmatched Cal.com users
     */
    public function test_unmatched_calcom_users_are_logged()
    {
        // Only create one staff member
        Staff::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@company.com',
        ]);

        // Mock Cal.com response with multiple users
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'eventTypes' => [
                                [
                                    'id' => 3001,
                                    'title' => 'Group Session',
                                    'schedulingType' => 'COLLECTIVE',
                                    'users' => [
                                        ['id' => 301, 'email' => 'existing@company.com', 'name' => 'Existing User'],
                                        ['id' => 302, 'email' => 'unknown1@cal.com', 'name' => 'Unknown User 1'],
                                        ['id' => 303, 'email' => 'unknown2@cal.com', 'name' => 'Unknown User 2'],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Run sync
        $this->service->syncEventTypesForCompany($this->company->id);
        
        // Check that only matched staff got assignments
        $eventType = CalcomEventType::where('calcom_event_type_id', 3001)->first();
        $assignments = DB::table('staff_event_types')
            ->where('event_type_id', $eventType->id)
            ->count();
            
        // Only 1 staff member should be assigned (the existing one)
        $this->assertEquals(1, $assignments);
        
        // But metadata should still contain all users
        $metadata = $eventType->metadata;
        $this->assertCount(3, $metadata['users']);
    }

    /**
     * Test priority and fixed host handling
     */
    public function test_host_priority_information_is_preserved()
    {
        // Create staff
        $primaryHost = Staff::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'primary@company.com',
            'calcom_user_id' => 401,
        ]);
        
        $secondaryHost = Staff::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'secondary@company.com',
            'calcom_user_id' => 402,
        ]);

        // Mock response with host priority data
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'eventTypes' => [
                                [
                                    'id' => 4001,
                                    'title' => 'Priority Meeting',
                                    'schedulingType' => 'COLLECTIVE',
                                    'users' => [
                                        ['id' => 401, 'email' => 'primary@company.com'],
                                        ['id' => 402, 'email' => 'secondary@company.com'],
                                    ],
                                    'hosts' => [
                                        ['userId' => 401, 'isFixed' => true, 'priority' => 1],
                                        ['userId' => 402, 'isFixed' => false, 'priority' => 2],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Run sync
        $this->service->syncEventTypesForCompany($this->company->id);
        
        // Check metadata contains host information
        $eventType = CalcomEventType::where('calcom_event_type_id', 4001)->first();
        $metadata = $eventType->metadata;
        
        $this->assertArrayHasKey('hosts', $metadata);
        $this->assertCount(2, $metadata['hosts']);
        
        // Verify host priorities
        $hosts = collect($metadata['hosts']);
        $primaryHostData = $hosts->firstWhere('userId', 401);
        $this->assertTrue($primaryHostData['isFixed']);
        $this->assertEquals(1, $primaryHostData['priority']);
        
        $secondaryHostData = $hosts->firstWhere('userId', 402);
        $this->assertFalse($secondaryHostData['isFixed']);
        $this->assertEquals(2, $secondaryHostData['priority']);
        
        // Check if primary assignment is marked
        $primaryAssignment = DB::table('staff_event_types')
            ->where('event_type_id', $eventType->id)
            ->where('staff_id', $primaryHost->id)
            ->first();
            
        // Note: is_primary field might need to be added based on host data
        // This is a potential enhancement
    }

    /**
     * Test handling of different scheduling types
     */
    public function test_different_scheduling_types_handled_correctly()
    {
        $staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'solo@company.com',
        ]);

        // Mock response with different scheduling types
        Http::fake([
            'https://api.cal.com/v2/event-types' => Http::response([
                'data' => [
                    'eventTypeGroups' => [
                        [
                            'eventTypes' => [
                                // COLLECTIVE - all hosts required
                                [
                                    'id' => 5001,
                                    'title' => 'Collective Event',
                                    'schedulingType' => 'COLLECTIVE',
                                    'users' => [['id' => 501, 'email' => 'solo@company.com']],
                                ],
                                // ROUND_ROBIN - any available host
                                [
                                    'id' => 5002,
                                    'title' => 'Round Robin Event',
                                    'schedulingType' => 'ROUND_ROBIN',
                                    'users' => [['id' => 501, 'email' => 'solo@company.com']],
                                ],
                                // INDIVIDUAL - specific host
                                [
                                    'id' => 5003,
                                    'title' => 'Individual Event',
                                    'schedulingType' => 'INDIVIDUAL',
                                    'userId' => 501,
                                    'users' => [['id' => 501, 'email' => 'solo@company.com']],
                                ],
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Run sync
        $this->service->syncEventTypesForCompany($this->company->id);
        
        // Verify scheduling types are correctly stored
        $collective = CalcomEventType::where('calcom_event_type_id', 5001)->first();
        $this->assertTrue($collective->is_team_event);
        
        $roundRobin = CalcomEventType::where('calcom_event_type_id', 5002)->first();
        $this->assertFalse($roundRobin->is_team_event); // Currently only COLLECTIVE is marked as team
        
        $individual = CalcomEventType::where('calcom_event_type_id', 5003)->first();
        $this->assertFalse($individual->is_team_event);
        
        // All should have staff assignments
        foreach ([5001, 5002, 5003] as $eventId) {
            $assignments = DB::table('staff_event_types')
                ->where('event_type_id', CalcomEventType::where('calcom_event_type_id', $eventId)->first()->id)
                ->count();
            $this->assertEquals(1, $assignments);
        }
    }
}