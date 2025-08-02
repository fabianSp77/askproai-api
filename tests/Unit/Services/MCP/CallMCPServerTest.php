<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\CallMCPServer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Call;
use App\Models\RetellAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CallMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected CallMCPServer $mcp;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected RetellAgent $agent;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new CallMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->agent = RetellAgent::factory()->create(['company_id' => $this->company->id]);
        
        // Set company context
        app()->instance('currentCompany', $this->company);
    }
    
    public function test_get_tools_returns_correct_structure()
    {
        $tools = $this->mcp->getTools();
        
        $this->assertIsArray($tools);
        $this->assertCount(7, $tools);
        
        $toolNames = array_column($tools, 'name');
        $expectedTools = [
            'listCalls',
            'getCallDetails',
            'getCallRecording',
            'getCallTranscript',
            'analyzeCallSentiment',
            'getCallStats',
            'searchCalls'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
    }
    
    public function test_list_calls_with_filters()
    {
        // Create calls
        $call1 = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'call_id' => 'call_123',
            'status' => 'ended',
            'duration' => 120,
            'created_at' => Carbon::now()->subDays(1)
        ]);
        
        $call2 = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'call_id' => 'call_456',
            'status' => 'in_progress',
            'duration' => 0,
            'created_at' => Carbon::now()
        ]);
        
        // Test without filters
        $result = $this->mcp->executeTool('listCalls', []);
        
        $this->assertArrayHasKey('calls', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('filters_applied', $result);
        $this->assertEquals(2, $result['total']);
        
        // Test with status filter
        $result = $this->mcp->executeTool('listCalls', [
            'status' => 'ended'
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($call1->id, $result['calls'][0]['id']);
        
        // Test with date range filter
        $result = $this->mcp->executeTool('listCalls', [
            'date_from' => Carbon::now()->format('Y-m-d'),
            'date_to' => Carbon::now()->format('Y-m-d')
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($call2->id, $result['calls'][0]['id']);
        
        // Test with customer filter
        $otherCustomer = Customer::factory()->create(['company_id' => $this->company->id]);
        $result = $this->mcp->executeTool('listCalls', [
            'customer_id' => $otherCustomer->id
        ]);
        
        $this->assertEquals(0, $result['total']);
    }
    
    public function test_get_call_details()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'call_id' => 'call_789',
            'status' => 'ended',
            'duration' => 180,
            'recording_url' => 'https://example.com/recording.mp3',
            'transcript' => 'Hello, how can I help you today?',
            'summary' => 'Customer called about appointment',
            'sentiment' => 'positive'
        ]);
        
        $result = $this->mcp->executeTool('getCallDetails', [
            'call_id' => $call->id
        ]);
        
        $this->assertEquals($call->id, $result['id']);
        $this->assertEquals('ended', $result['status']);
        $this->assertEquals(180, $result['duration']);
        $this->assertEquals('positive', $result['sentiment']);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('agent', $result);
        $this->assertArrayHasKey('branch', $result);
    }
    
    public function test_get_call_details_not_found()
    {
        $result = $this->mcp->executeTool('getCallDetails', [
            'call_id' => 99999
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not found', $result['error']);
    }
    
    public function test_get_call_recording()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => 'call_rec_123',
            'recording_url' => 'https://example.com/recording.mp3',
            'status' => 'ended'
        ]);
        
        $result = $this->mcp->executeTool('getCallRecording', [
            'call_id' => $call->id
        ]);
        
        $this->assertArrayHasKey('recording_url', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertArrayHasKey('format', $result);
        $this->assertEquals('https://example.com/recording.mp3', $result['recording_url']);
    }
    
    public function test_get_call_recording_not_available()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'call_id' => 'call_no_rec',
            'recording_url' => null,
            'status' => 'ended'
        ]);
        
        $result = $this->mcp->executeTool('getCallRecording', [
            'call_id' => $call->id
        ]);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not available', $result['error']);
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
            'call_id' => 'call_trans_123',
            'transcript' => json_encode($transcript),
            'status' => 'ended'
        ]);
        
        $result = $this->mcp->executeTool('getCallTranscript', [
            'call_id' => $call->id,
            'format' => 'structured'
        ]);
        
        $this->assertArrayHasKey('transcript', $result);
        $this->assertArrayHasKey('format', $result);
        $this->assertArrayHasKey('word_count', $result);
        $this->assertArrayHasKey('speakers', $result);
        $this->assertCount(3, $result['transcript']);
        $this->assertEquals('structured', $result['format']);
    }
    
    public function test_analyze_call_sentiment()
    {
        // Mock HTTP response for sentiment analysis
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
            'call_id' => 'call_sentiment_123',
            'transcript' => 'Customer was happy with the service',
            'status' => 'ended'
        ]);
        
        $result = $this->mcp->executeTool('analyzeCallSentiment', [
            'call_id' => $call->id
        ]);
        
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('emotions', $result);
        $this->assertArrayHasKey('keywords', $result);
        $this->assertArrayHasKey('topics', $result);
        $this->assertEquals('positive', $result['sentiment']);
        $this->assertEquals(0.85, $result['score']);
    }
    
    public function test_get_call_stats()
    {
        // Create various calls
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ended',
            'duration' => 120,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ended',
            'duration' => 300,
            'sentiment' => 'positive',
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'missed',
            'duration' => 0,
            'created_at' => Carbon::now()->subDays(1)
        ]);
        
        $result = $this->mcp->executeTool('getCallStats', [
            'period' => 'last_7_days'
        ]);
        
        $this->assertArrayHasKey('total_calls', $result);
        $this->assertArrayHasKey('calls_by_status', $result);
        $this->assertArrayHasKey('average_duration', $result);
        $this->assertArrayHasKey('calls_by_day', $result);
        $this->assertArrayHasKey('calls_by_hour', $result);
        $this->assertArrayHasKey('sentiment_breakdown', $result);
        $this->assertArrayHasKey('top_agents', $result);
        
        $this->assertEquals(18, $result['total_calls']);
        $this->assertEquals(15, $result['calls_by_status']['ended']);
        $this->assertEquals(3, $result['calls_by_status']['missed']);
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
        $result = $this->mcp->executeTool('searchCalls', [
            'query' => 'appointment'
        ]);
        
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(2, $result['total']);
        
        $foundIds = array_column($result['results'], 'id');
        $this->assertContains($call1->id, $foundIds);
        $this->assertContains($call3->id, $foundIds);
        $this->assertNotContains($call2->id, $foundIds);
        
        // Search with filters
        $result = $this->mcp->executeTool('searchCalls', [
            'query' => 'reschedule',
            'search_in' => ['summary']
        ]);
        
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($call1->id, $result['results'][0]['id']);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
    
    public function test_call_duration_formatting()
    {
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'duration' => 3665, // 1 hour, 1 minute, 5 seconds
            'status' => 'ended'
        ]);
        
        $result = $this->mcp->executeTool('getCallDetails', [
            'call_id' => $call->id
        ]);
        
        $this->assertArrayHasKey('duration_formatted', $result);
        $this->assertEquals('1h 1m 5s', $result['duration_formatted']);
    }
    
    public function test_concurrent_call_handling()
    {
        // Create multiple in-progress calls
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'in_progress',
            'created_at' => Carbon::now()
        ]);
        
        $result = $this->mcp->executeTool('listCalls', [
            'status' => 'in_progress'
        ]);
        
        $this->assertEquals(5, $result['total']);
        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('concurrent calls', $result['warning']);
    }
}