<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\Company;
use App\Models\PortalUser;
use App\Models\Call;
use App\Models\Customer;
use Laravel\Sanctum\Sanctum;

class CallApiTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private Company $company;
    private PortalUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function can_list_calls()
    {
        Call::factory()->count(20)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/calls');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'phone_number',
                        'customer_name',
                        'duration',
                        'status',
                        'created_at',
                        'transcript',
                        'recording_url',
                        'ai_summary',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(20, 'data');
    }

    /** @test */
    public function can_filter_calls_by_status()
    {
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
        ]);
        
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'missed',
        ]);

        $response = $this->getJson('/api/calls?status=completed');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function can_search_calls_by_phone_number()
    {
        Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number' => '+49123456789',
        ]);
        
        Call::factory()->create([
            'company_id' => $this->company->id,
            'phone_number' => '+49987654321',
        ]);

        $response = $this->getJson('/api/calls?search=123456');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone_number', '+49123456789');
    }

    /** @test */
    public function can_get_call_details()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => 'Hello, I would like to book an appointment.',
            'ai_summary' => 'Customer wants to book appointment',
            'metadata' => ['detected_intent' => 'booking'],
        ]);

        $response = $this->getJson("/api/calls/{$call->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'phone_number',
                    'customer_name',
                    'duration',
                    'status',
                    'transcript',
                    'recording_url',
                    'ai_summary',
                    'metadata',
                    'customer',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /** @test */
    public function can_get_call_transcript()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => 'Full transcript of the call...',
        ]);

        $response = $this->getJson("/api/calls/{$call->id}/transcript");

        $response->assertOk()
            ->assertJson([
                'transcript' => 'Full transcript of the call...',
                'formatted' => true,
            ]);
    }

    /** @test */
    public function can_download_call_recording()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'recording_url' => 'https://example.com/recording.mp3',
        ]);

        $response = $this->getJson("/api/calls/{$call->id}/recording");

        $response->assertOk()
            ->assertJson([
                'recording_url' => 'https://example.com/recording.mp3',
                'expires_at' => $response->json('expires_at'),
            ]);
    }

    /** @test */
    public function can_update_call_notes()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->patchJson("/api/calls/{$call->id}/notes", [
            'notes' => 'Customer seemed interested in premium package',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Customer seemed interested in premium package');

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'notes' => 'Customer seemed interested in premium package',
        ]);
    }

    /** @test */
    public function can_assign_call_to_customer()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => null,
        ]);
        
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson("/api/calls/{$call->id}/assign-customer", [
            'customer_id' => $customer->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'customer_id' => $customer->id,
        ]);
    }

    /** @test */
    public function can_get_call_statistics()
    {
        // Create calls with different statuses
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'duration' => 180,
        ]);
        
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'missed',
        ]);

        $response = $this->getJson('/api/calls/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'total_calls',
                'completed_calls',
                'missed_calls',
                'average_duration',
                'total_duration',
                'calls_by_hour',
                'calls_by_day',
            ]);
    }

    /** @test */
    public function can_export_calls()
    {
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/calls/export', [
            'format' => 'csv',
            'date_from' => now()->subMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'download_url',
                'expires_at',
            ]);
    }

    /** @test */
    public function can_get_call_activities()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Simulate activities
        $call->activities()->create([
            'type' => 'status_changed',
            'description' => 'Status changed from pending to completed',
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/calls/{$call->id}/activities");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'description',
                        'user',
                        'created_at',
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_bulk_tag_calls()
    {
        $calls = Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $callIds = $calls->pluck('id')->toArray();

        $response = $this->postJson('/api/calls/bulk-tag', [
            'call_ids' => $callIds,
            'tags' => ['follow-up-required', 'interested'],
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => '3 calls tagged successfully',
            ]);
    }

    /** @test */
    public function can_get_calls_by_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}/calls");

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function cannot_access_calls_from_other_companies()
    {
        $otherCompany = Company::factory()->create();
        $otherCall = Call::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->getJson("/api/calls/{$otherCall->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function can_get_call_cost_breakdown()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration' => 300, // 5 minutes
            'cost' => 2.50,
        ]);

        $response = $this->getJson("/api/calls/{$call->id}/cost-breakdown");

        $response->assertOk()
            ->assertJsonStructure([
                'duration_seconds',
                'duration_minutes',
                'rate_per_minute',
                'base_cost',
                'additional_charges',
                'total_cost',
            ]);
    }

    /** @test */
    public function can_retry_failed_call_processing()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'failed',
            'metadata' => ['error' => 'Transcription failed'],
        ]);

        $response = $this->postJson("/api/calls/{$call->id}/retry-processing");

        $response->assertOk()
            ->assertJson([
                'message' => 'Call processing queued for retry',
                'job_id' => $response->json('job_id'),
            ]);
    }
}