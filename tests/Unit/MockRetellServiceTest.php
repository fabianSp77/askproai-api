<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\Mocks\MockRetellService;
use Tests\TestCase;

class MockRetellServiceTest extends TestCase
{
    private MockRetellService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MockRetellService();
    }
    
    protected function tearDown(): void
    {
        $this->service->reset();
        parent::tearDown();
    }
    
    #[Test]
    
    public function test_can_add_and_retrieve_mock_calls()
    {
        // Add a mock call
        $callId = $this->service->addMockCall([
            'from_number' => '+491234567890',
            'transcript' => 'Test call'
        ]);
        
        // Retrieve the call
        $call = $this->service->getCall($callId);
        
        $this->assertNotNull($call);
        $this->assertEquals('+491234567890', $call['from_number']);
        $this->assertEquals('Test call', $call['transcript']);
    }
    
    #[Test]
    
    public function test_can_filter_calls_by_status()
    {
        // Add multiple calls
        $this->service->addMockCall(['status' => 'ended']);
        $this->service->addMockCall(['status' => 'active']);
        $this->service->addMockCall(['status' => 'ended']);
        
        // Filter by status
        $endedCalls = $this->service->getCalls(['status' => 'ended']);
        $activeCalls = $this->service->getCalls(['status' => 'active']);
        
        $this->assertCount(2, $endedCalls);
        $this->assertCount(1, $activeCalls);
    }
    
    #[Test]
    
    public function test_can_create_and_update_agents()
    {
        // Create agent
        $agent = $this->service->createAgent([
            'agent_name' => 'Test Agent',
            'voice_id' => 'de-DE-KatjaNeural'
        ]);
        
        $this->assertNotNull($agent['agent_id']);
        $this->assertEquals('Test Agent', $agent['agent_name']);
        
        // Update agent
        $updated = $this->service->updateAgent($agent['agent_id'], [
            'agent_name' => 'Updated Agent'
        ]);
        
        $this->assertEquals('Updated Agent', $updated['agent_name']);
        $this->assertEquals('de-DE-KatjaNeural', $updated['voice_id']);
    }
    
    #[Test]
    
    public function test_can_simulate_failures()
    {
        $this->service->shouldFail('API Error');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Error');
        
        $this->service->getCalls();
    }
    
    #[Test]
    
    public function test_can_generate_realistic_scenarios()
    {
        // Test appointment booking scenario
        $call = $this->service->generateRealisticCall([
            'scenario' => 'appointment_booking'
        ]);
        
        $this->assertNotNull($call['call_analysis']);
        $this->assertTrue($call['call_analysis']['appointment_request']);
        $this->assertNotNull($call['call_analysis']['customer_name']);
        $this->assertStringContainsString('Termin', $call['transcript']);
    }
    
    #[Test]
    
    public function test_can_simulate_webhooks()
    {
        // Create a call
        $callData = [
            'call_id' => 'test-123',
            'from_number' => '+491234567890'
        ];
        
        // Simulate webhook
        $webhook = $this->service->simulateWebhook('call_ended', $callData);
        
        $this->assertEquals('call_ended', $webhook['payload']['event']);
        $this->assertEquals($callData, $webhook['payload']['call']);
        $this->assertNotEmpty($webhook['headers']['x-retell-signature']);
    }
    
    #[Test]
    
    public function test_can_add_call_analysis()
    {
        $callId = $this->service->addMockCall([]);
        
        $this->service->addCallAnalysis($callId, [
            'customer_name' => 'Test Customer',
            'appointment_request' => true,
            'sentiment' => 'positive'
        ]);
        
        $call = $this->service->getCall($callId);
        
        $this->assertEquals('Test Customer', $call['call_analysis']['customer_name']);
        $this->assertTrue($call['call_analysis']['appointment_request']);
        $this->assertEquals('positive', $call['call_analysis']['sentiment']);
    }
    
    #[Test]
    
    public function test_can_simulate_delay()
    {
        $this->service->withDelay(100); // 100ms delay
        
        $start = microtime(true);
        $this->service->getCalls();
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertGreaterThan(90, $duration); // Allow some variance
    }
    
    #[Test]
    
    public function test_update_phone_number()
    {
        $result = $this->service->updatePhoneNumber('+491234567890', [
            'agent_id' => 'test-agent',
            'inbound_enabled' => true
        ]);
        
        $this->assertEquals('+491234567890', $result['phone_number']);
        $this->assertEquals('test-agent', $result['agent_id']);
        $this->assertTrue($result['inbound_enabled']);
    }
    
    #[Test]
    
    public function test_different_call_scenarios()
    {
        $scenarios = [
            'appointment_booking',
            'information_request',
            'cancellation',
            'wrong_number'
        ];
        
        foreach ($scenarios as $scenario) {
            $call = $this->service->generateRealisticCall(['scenario' => $scenario]);
            
            $this->assertNotEmpty($call['transcript']);
            $this->assertNotNull($call['call_analysis']);
            $this->assertArrayHasKey('summary', $call['call_analysis']);
        }
    }
}