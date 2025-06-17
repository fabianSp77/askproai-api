<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Customer;
use Livewire\Livewire;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Actions\DeleteAction;

class CalcomResourcesTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'calcom_api_key' => 'cal_test_key_123'
        ]);
        
        $this->user = User::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $this->actingAs($this->user);
    }
    
    /** @test */
    public function it_can_access_appointments_list_page()
    {
        $response = $this->get('/admin/appointments');
        
        $response->assertSuccessful();
        $response->assertSee('Termine');
    }
    
    /** @test */
    public function it_shows_sync_calcom_button_on_appointments_page()
    {
        Livewire::test(\App\Filament\Admin\Resources\AppointmentResource\Pages\ListAppointments::class)
            ->assertSee('Termine abrufen')
            ->assertActionExists('sync_calcom');
    }
    
    /** @test */
    public function it_can_trigger_calcom_sync()
    {
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'status' => 'success',
                'data' => []
            ], 200)
        ]);
        
        Livewire::test(\App\Filament\Admin\Resources\AppointmentResource\Pages\ListAppointments::class)
            ->callAction('sync_calcom')
            ->assertNotified();
    }
    
    /** @test */
    public function it_shows_appointment_tabs_with_counts()
    {
        // Create test appointments
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'confirmed'
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'pending'
        ]);
        
        Appointment::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'status' => 'cancelled'
        ]);
        
        $response = $this->get('/admin/appointments');
        
        $response->assertSee('Alle (6)');
        $response->assertSee('Bestätigt (3)');
        $response->assertSee('Ausstehend (2)');
        $response->assertSee('Abgesagt (1)');
    }
    
    /** @test */
    public function it_can_view_appointment_details()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'calcom_v2_booking_id' => '12345',
            'status' => 'confirmed',
            'notes' => 'Test appointment notes'
        ]);
        
        $response = $this->get("/admin/appointments/{$appointment->id}");
        
        $response->assertSuccessful();
        $response->assertSee($customer->name);
        $response->assertSee('12345');
        $response->assertSee('Test appointment notes');
        $response->assertSee('Cal.com Integration');
    }
    
    /** @test */
    public function it_displays_appointment_timeline()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'meta' => [
                'timeline' => [
                    [
                        'event' => 'created',
                        'timestamp' => now()->subHours(2)->toIso8601String(),
                        'description' => 'Termin erstellt'
                    ],
                    [
                        'event' => 'confirmed',
                        'timestamp' => now()->subHour()->toIso8601String(),
                        'description' => 'Termin bestätigt'
                    ]
                ]
            ]
        ]);
        
        $response = $this->get("/admin/appointments/{$appointment->id}");
        
        $response->assertSuccessful();
        $response->assertSee('Timeline');
        $response->assertSee('Termin erstellt');
        $response->assertSee('Termin bestätigt');
    }
    
    /** @test */
    public function it_can_access_calcom_sync_status_page()
    {
        $response = $this->get('/admin/calcom-sync-status');
        
        $response->assertSuccessful();
        $response->assertSee('Cal.com Sync Status');
        $response->assertSee('Gesamt synchronisiert');
        $response->assertSee('Letzte 24 Stunden');
        $response->assertSee('Ausstehend');
        $response->assertSee('Fehlgeschlagen');
    }
    
    /** @test */
    public function it_can_trigger_manual_sync_from_status_page()
    {
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'status' => 'success',
                'data' => []
            ], 200)
        ]);
        
        Livewire::test(\App\Filament\Admin\Pages\CalcomSyncStatus::class)
            ->assertActionExists('manual_sync')
            ->callAction('manual_sync')
            ->assertNotified();
    }
    
    /** @test */
    public function it_shows_appointment_widgets_on_list_page()
    {
        $response = $this->get('/admin/appointments');
        
        $response->assertSuccessful();
        
        // Check for widget presence
        $response->assertSee('appointment-stats-widget');
        $response->assertSee('appointment-trends-widget');
        $response->assertSee('cost-dashboard-widget');
        $response->assertSee('staff-performance-widget');
    }
    
    /** @test */
    public function it_can_access_calcom_api_test_page()
    {
        $response = $this->get('/admin/calcom-api-test');
        
        $response->assertSuccessful();
        $response->assertSee('Cal.com API Test');
        $response->assertSee('Test API Connection');
    }
    
    /** @test */
    public function it_filters_appointments_by_calcom_sync_status()
    {
        // Create synced appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => '123'
        ]);
        
        // Create non-synced appointment
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => null
        ]);
        
        Livewire::test(\App\Filament\Admin\Resources\AppointmentResource\Pages\ListAppointments::class)
            ->assertCanSeeTableRecords(Appointment::all())
            ->filterTable('calcom_synced', true)
            ->assertCanSeeTableRecords(Appointment::whereNotNull('calcom_v2_booking_id')->get())
            ->assertCanNotSeeTableRecords(Appointment::whereNull('calcom_v2_booking_id')->get());
    }
    
    /** @test */
    public function it_shows_calcom_booking_id_in_appointment_table()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => 'CAL-12345'
        ]);
        
        Livewire::test(\App\Filament\Admin\Resources\AppointmentResource\Pages\ListAppointments::class)
            ->assertCanSeeTableRecords([$appointment])
            ->assertTableColumnStateSet('calcom_booking_id', 'CAL-12345', $appointment);
    }
    
    /** @test */
    public function it_validates_webhook_signature_middleware()
    {
        $payload = ['test' => 'data'];
        
        // Without signature
        $response = $this->postJson('/api/calcom/webhook', $payload);
        $response->assertStatus(401);
        
        // With invalid signature
        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => 'invalid'
        ]);
        $response->assertStatus(401);
        
        // With valid signature
        $secret = config('services.calcom.webhook_secret', 'test-secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);
        
        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature
        ]);
        
        // Should not be 401 (might be 422 or 200 depending on payload)
        $this->assertNotEquals(401, $response->status());
    }
}