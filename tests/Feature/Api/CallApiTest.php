<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Call;
use App\Models\RetellAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CallApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected RetellAgent $agent;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->agent = RetellAgent::factory()->create(['company_id' => $this->company->id]);
        
        // Authenticate user
        Sanctum::actingAs($this->user);
    }
    
    public function test_list_calls_returns_paginated_results()
    {
        // Create calls
        Call::factory()->count(30)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id
        ]);
        
        $response = $this->getJson('/api/calls');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'calls' => [
                        '*' => [
                            'id',
                            'call_id',
                            'customer',
                            'agent',
                            'branch',
                            'status',
                            'duration',
                            'duration_formatted',
                            'sentiment',
                            'created_at'
                        ]
                    ],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page'
                    ]
                ]
            ]);
        
        $this->assertEquals(30, $response->json('data.pagination.total'));
        $this->assertEquals(20, $response->json('data.pagination.per_page'));
    }
    
    public function test_list_calls_with_filters()
    {
        // Create calls with different statuses
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ended',
            'created_at' => Carbon::yesterday()
        ]);
        
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'in_progress',
            'created_at' => Carbon::now()
        ]);
        
        // Filter by status
        $response = $this->getJson('/api/calls?status=ended');
        
        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('data.pagination.total'));
        
        // Filter by date
        $response = $this->getJson('/api/calls?date_from=' . Carbon::yesterday()->format('Y-m-d') . 
                                   '&date_to=' . Carbon::yesterday()->format('Y-m-d'));
        
        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('data.pagination.total'));
        
        // Filter by customer
        $response = $this->getJson("/api/calls?customer_id={$this->customer->id}");
        
        $response->assertStatus(200);
        $this->assertEquals(15, $response->json('data.pagination.total'));
    }
    
    public function test_get_call_details()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'call_id' => 'call_12345',
            'status' => 'ended',
            'duration' => 180,
            'recording_url' => 'https://example.com/recording.mp3',
            'transcript' => 'Test transcript',
            'summary' => 'Test summary',
            'sentiment' => 'positive'
        ]);
        
        $response = $this->getJson("/api/calls/{$call->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $call->id,
                    'call_id' => 'call_12345',
                    'status' => 'ended',
                    'duration' => 180,
                    'duration_formatted' => '3m 0s',
                    'sentiment' => 'positive',
                    'recording_url' => 'https://example.com/recording.mp3',
                    'transcript' => 'Test transcript',
                    'summary' => 'Test summary',
                    'customer' => [
                        'id' => $this->customer->id,
                        'full_name' => $this->customer->full_name
                    ],
                    'agent' => [
                        'id' => $this->agent->id,
                        'name' => $this->agent->name
                    ]
                ]
            ]);
    }
    
    public function test_get_call_recording()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => 'call_rec_123',
            'recording_url' => 'https://example.com/recording.mp3',
            'duration' => 120,
            'status' => 'ended'
        ]);
        
        $response = $this->getJson("/api/calls/{$call->id}/recording");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'recording_url' => 'https://example.com/recording.mp3',
                    'duration' => 120,
                    'format' => 'mp3',
                    'size_estimate' => '1.8 MB'
                ]
            ]);
    }
    
    public function test_get_call_recording_not_available()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'recording_url' => null,
            'status' => 'ended'
        ]);
        
        $response = $this->getJson("/api/calls/{$call->id}/recording");
        
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Recording not available for this call'
            ]);
    }
    
    public function test_get_call_transcript()
    {
        $transcript = [
            ['speaker' => 'agent', 'text' => 'Hello, how can I help you?', 'timestamp' => 0],
            ['speaker' => 'customer', 'text' => 'I need to book an appointment', 'timestamp' => 2.5],
            ['speaker' => 'agent', 'text' => 'Sure, I can help with that', 'timestamp' => 5.0]
        ];
        
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => json_encode($transcript),
            'status' => 'ended'
        ]);
        
        $response = $this->getJson("/api/calls/{$call->id}/transcript");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'transcript' => $transcript,
                    'format' => 'structured',
                    'word_count' => 15,
                    'speakers' => ['agent', 'customer']
                ]
            ]);
    }
    
    public function test_analyze_call_sentiment()
    {
        // Mock Retell API response
        Http::fake([
            'api.retellai.com/*' => Http::response([
                'sentiment' => 'positive',
                'score' => 0.85,
                'emotions' => [
                    'happy' => 0.7,
                    'satisfied' => 0.8,
                    'frustrated' => 0.1
                ],
                'keywords' => ['appointment', 'helpful', 'thank you'],
                'topics' => ['scheduling', 'customer service']
            ], 200)
        ]);
        
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => 'Customer was happy with the service',
            'status' => 'ended'
        ]);
        
        $response = $this->postJson("/api/calls/{$call->id}/analyze-sentiment");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sentiment' => 'positive',
                    'score' => 0.85,
                    'emotions' => [
                        'happy' => 0.7,
                        'satisfied' => 0.8,
                        'frustrated' => 0.1
                    ],
                    'keywords' => ['appointment', 'helpful', 'thank you'],
                    'topics' => ['scheduling', 'customer service']
                ]
            ]);
        
        // Verify sentiment was saved
        $call->refresh();
        $this->assertEquals('positive', $call->sentiment);
    }
    
    public function test_get_call_stats()
    {
        // Create calls with various attributes
        Call::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ended',
            'duration' => 120,
            'sentiment' => 'positive',
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ended',
            'duration' => 300,
            'sentiment' => 'neutral',
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'missed',
            'duration' => 0,
            'created_at' => Carbon::now()
        ]);
        
        $response = $this->getJson('/api/calls/stats?period=last_7_days');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_calls',
                    'calls_by_status',
                    'average_duration',
                    'calls_by_day',
                    'calls_by_hour',
                    'sentiment_breakdown',
                    'top_agents'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(35, $data['total_calls']);
        $this->assertEquals(30, $data['calls_by_status']['ended']);
        $this->assertEquals(5, $data['calls_by_status']['missed']);
        $this->assertEquals(180, $data['average_duration']); // (20*120 + 10*300) / 30
    }
    
    public function test_search_calls()
    {
        // Create calls with searchable content
        $call1 = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => 'I need to reschedule my appointment for next week',
            'summary' => 'Customer wants to reschedule',
            'status' => 'ended'
        ]);
        
        $call2 = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => 'Can you tell me your business hours?',
            'summary' => 'Customer inquiry about hours',
            'status' => 'ended'
        ]);
        
        $call3 = Call::factory()->create([
            'company_id' => $this->company->id,
            'transcript' => 'I want to cancel my appointment',
            'summary' => 'Cancellation request',
            'status' => 'ended'
        ]);
        
        // Search for "appointment"
        $response = $this->getJson('/api/calls/search?query=appointment');
        
        $response->assertStatus(200);
        $results = $response->json('data.results');
        $this->assertEquals(2, $response->json('data.total'));
        
        $foundIds = array_column($results, 'id');
        $this->assertContains($call1->id, $foundIds);
        $this->assertContains($call3->id, $foundIds);
        
        // Search in summary only
        $response = $this->getJson('/api/calls/search?query=reschedule&search_in[]=summary');
        
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals($call1->id, $response->json('data.results.0.id'));
    }
    
    public function test_concurrent_calls_warning()
    {
        // Create multiple in-progress calls
        Call::factory()->count(6)->create([
            'company_id' => $this->company->id,
            'status' => 'in_progress',
            'created_at' => Carbon::now()
        ]);
        
        $response = $this->getJson('/api/calls?status=in_progress');
        
        $response->assertStatus(200)
            ->assertJsonPath('data.warning', 'High number of concurrent calls detected');
    }
    
    public function test_call_not_found()
    {
        $response = $this->getJson('/api/calls/99999');
        
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Call not found'
            ]);
    }
    
    public function test_unauthorized_access()
    {
        auth()->logout();
        
        $response = $this->getJson('/api/calls');
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
    
    public function test_cross_company_isolation()
    {
        // Create another company with calls
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        Call::factory()->count(5)->create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id
        ]);
        
        // Create own company calls
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);
        
        // Should only see calls from user's company
        $response = $this->getJson('/api/calls');
        
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.pagination.total'));
        
        $calls = $response->json('data.calls');
        foreach ($calls as $call) {
            $this->assertEquals($this->company->id, $call['company_id']);
        }
    }
    
    public function test_rate_limiting()
    {
        // Make multiple requests quickly
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/calls');
        }
        
        // 61st request should be rate limited
        $response = $this->getJson('/api/calls');
        
        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Too Many Attempts.'
            ]);
    }
}