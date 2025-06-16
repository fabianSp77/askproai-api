<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Services\CalcomSyncService;
use App\Services\AvailabilityChecker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;
    
    protected $company;
    protected $staff;
    protected $eventType;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Erstelle Test-Company
        $this->company = Company::factory()->create([
            'calcom_api_key' => 'test_api_key',
            'retell_api_key' => 'test_retell_key'
        ]);
        
        // Erstelle Test-Staff
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'active' => true,
            'is_bookable' => true,
            'calcom_user_id' => '12345',
            'calcom_username' => 'testuser'
        ]);
        
        // Erstelle Test Event-Type
        $this->eventType = CalcomEventType::create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 'evt_test123',
            'calcom_numeric_event_type_id' => 123456,
            'name' => 'Test Service',
            'slug' => 'test-service',
            'duration_minutes' => 30,
            'price' => 50.00,
            'is_active' => true
        ]);
    }
    
    /** @test */
    public function it_can_sync_event_types_from_calcom()
    {
        // Mock Cal.com API Response
        Http::fake([
            'api.cal.com/v1/event-types*' => Http::response([
                'event_types' => [
                    [
                        'id' => 123456,
                        'title' => 'Consultation',
                        'slug' => 'consultation',
                        'length' => 30,
                        'description' => 'A consultation meeting',
                        'schedulingType' => 'INDIVIDUAL',
                        'hidden' => false
                    ]
                ]
            ], 200)
        ]);
        
        $syncService = new CalcomSyncService();
        $result = $syncService->syncEventTypesForCompany($this->company->id);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['synced_count']);
        
        // Prüfe ob Event-Type erstellt wurde
        $this->assertDatabaseHas('calcom_event_types', [
            'company_id' => $this->company->id,
            'calcom_event_type_id' => '123456',
            'name' => 'Consultation',
            'duration_minutes' => 30
        ]);
    }
    
    /** @test */
    public function it_can_check_availability_with_specific_staff()
    {
        // Erstelle Staff-Event-Type Zuordnung
        \DB::table('staff_event_types')->insert([
            'staff_id' => $this->staff->id,
            'event_type_id' => $this->eventType->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Mock Cal.com Availability Response
        Http::fake([
            'api.cal.com/v1/availability*' => Http::response([
                'slots' => [
                    ['time' => '2024-01-10T10:00:00Z'],
                    ['time' => '2024-01-10T10:30:00Z'],
                    ['time' => '2024-01-10T11:00:00Z']
                ]
            ], 200)
        ]);
        
        $syncService = new CalcomSyncService();
        $availability = $syncService->checkAvailability(
            $this->eventType->id,
            now()->toIso8601String(),
            now()->addDays(7)->toIso8601String(),
            $this->staff->id
        );
        
        $this->assertTrue($availability['available']);
        $this->assertCount(3, $availability['slots']);
        $this->assertEquals($this->staff->id, $availability['staff_id']);
    }
    
    /** @test */
    public function it_returns_error_when_staff_not_assigned_to_event_type()
    {
        // Kein Staff-Event-Type Assignment
        
        $syncService = new CalcomSyncService();
        $availability = $syncService->checkAvailability(
            $this->eventType->id,
            now()->toIso8601String(),
            now()->addDays(7)->toIso8601String(),
            $this->staff->id
        );
        
        $this->assertFalse($availability['available']);
        $this->assertEquals('Staff member is not assigned to this service', $availability['message']);
    }
    
    /** @test */
    public function it_can_manage_staff_event_assignments()
    {
        $response = $this->postJson('/api/event-management/staff-event-assignments', [
            'assignments' => [
                [
                    'staff_id' => $this->staff->id,
                    'event_type_id' => $this->eventType->id,
                    'action' => 'add',
                    'custom_duration' => 45,
                    'custom_price' => 60.00
                ]
            ]
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // Prüfe ob Zuordnung erstellt wurde
        $this->assertDatabaseHas('staff_event_types', [
            'staff_id' => $this->staff->id,
            'event_type_id' => $this->eventType->id,
            'custom_duration' => 45,
            'custom_price' => 60.00
        ]);
    }
    
    /** @test */
    public function it_can_find_event_type_by_name()
    {
        $availabilityChecker = new AvailabilityChecker(new CalcomSyncService());
        
        $result = $availabilityChecker->checkAvailabilityFromRequest([
            'company_id' => $this->company->id,
            'service_name' => 'Test Service',
            'date_from' => now()->toIso8601String(),
            'date_to' => now()->addDays(7)->toIso8601String()
        ]);
        
        $this->assertArrayHasKey('event_type', $result);
        $this->assertEquals($this->eventType->id, $result['event_type']['id']);
    }
    
    /** @test */
    public function it_uses_default_event_type_when_none_specified()
    {
        // Setze Default Event-Type
        $this->company->update(['default_event_type_id' => $this->eventType->id]);
        
        Http::fake([
            'api.cal.com/v1/availability*' => Http::response([
                'slots' => [['time' => '2024-01-10T10:00:00Z']]
            ], 200)
        ]);
        
        $availabilityChecker = new AvailabilityChecker(new CalcomSyncService());
        
        $result = $availabilityChecker->checkAvailabilityFromRequest([
            'company_id' => $this->company->id,
            'date_from' => now()->toIso8601String(),
            'date_to' => now()->addDays(7)->toIso8601String()
        ]);
        
        $this->assertTrue($result['available']);
        $this->assertEquals($this->eventType->id, $result['event_type']['id']);
    }
    
    /** @test */
    public function retell_webhook_can_extract_service_and_staff_preferences()
    {
        $webhookData = [
            'call_id' => 'test_call_123',
            'company_id' => $this->company->id,
            '_dienstleistung' => 'Test Service',
            '_mitarbeiter' => $this->staff->name,
            '_datum__termin' => '2024-01-15',
            '_uhrzeit__termin' => '14:30',
            '_name' => 'Test Kunde',
            '_telefonnummer' => '+491234567890'
        ];
        
        $response = $this->postJson('/api/retell/webhook', $webhookData);
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // Prüfe ob Termin mit korrekten Daten erstellt wurde
        $this->assertDatabaseHas('appointments', [
            'customer_id' => \App\Models\Customer::where('phone', '+491234567890')->first()->id,
            'status' => 'booked'
        ]);
    }
    
    /** @test */
    public function it_can_handle_multi_branch_queries()
    {
        $branch = \App\Models\Branch::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $this->eventType->update(['branch_id' => $branch->id]);
        
        Http::fake([
            'api.cal.com/v1/availability*' => Http::response([
                'slots' => []
            ], 200)
        ]);
        
        $availabilityChecker = new AvailabilityChecker(new CalcomSyncService());
        
        // Prüfe mit falscher Branch
        $result = $availabilityChecker->checkAvailability(
            $this->eventType->id,
            now()->toIso8601String(),
            now()->addDays(7)->toIso8601String(),
            null,
            Str::uuid() // Andere Branch ID
        );
        
        $this->assertFalse($result['available']);
        $this->assertEquals('Service not available at this branch', $result['message']);
        
        // Prüfe mit korrekter Branch
        $result = $availabilityChecker->checkAvailability(
            $this->eventType->id,
            now()->toIso8601String(),
            now()->addDays(7)->toIso8601String(),
            null,
            $branch->id
        );
        
        // Sollte jetzt durchgehen (auch wenn keine Slots verfügbar)
        $this->assertArrayNotHasKey('message', $result);
    }
}