<?php

namespace Tests\Mocks;

use App\Services\RetellV2Service;
use Illuminate\Support\Str;

/**
 * Mock implementation of RetellV2Service for testing
 */
class MockRetellService extends RetellV2Service
{
    private array $calls = [];
    private array $agents = [];
    private array $phoneNumbers = [];
    private array $callAnalyses = [];
    private bool $shouldFail = false;
    private ?string $failureMessage = null;
    private int $callDelay = 0;
    
    /**
     * Constructor doesn't need API key for mock
     */
    public function __construct()
    {
        // Skip parent constructor
    }
    
    /**
     * Configure mock to fail
     */
    public function shouldFail(string $message = 'Mock failure'): self
    {
        $this->shouldFail = true;
        $this->failureMessage = $message;
        return $this;
    }
    
    /**
     * Configure call delay (simulate network latency)
     */
    public function withDelay(int $milliseconds): self
    {
        $this->callDelay = $milliseconds;
        return $this;
    }
    
    /**
     * Reset mock state
     */
    public function reset(): void
    {
        $this->calls = [];
        $this->agents = [];
        $this->phoneNumbers = [];
        $this->callAnalyses = [];
        $this->shouldFail = false;
        $this->failureMessage = null;
        $this->callDelay = 0;
    }
    
    /**
     * Mock get calls
     */
    public function getCalls(array $filters = []): array
    {
        $this->simulateDelay();
        
        if ($this->shouldFail) {
            throw new \Exception($this->failureMessage);
        }
        
        $calls = $this->calls;
        
        // Apply filters
        if (isset($filters['status'])) {
            $calls = array_filter($calls, fn($call) => $call['status'] === $filters['status']);
        }
        
        if (isset($filters['from'])) {
            $calls = array_filter($calls, fn($call) => $call['start_timestamp'] >= strtotime($filters['from']));
        }
        
        if (isset($filters['to'])) {
            $calls = array_filter($calls, fn($call) => $call['start_timestamp'] <= strtotime($filters['to']));
        }
        
        return array_values($calls);
    }
    
    /**
     * Mock get call details
     */
    public function getCall(string $callId): ?array
    {
        $this->simulateDelay();
        
        if ($this->shouldFail) {
            throw new \Exception($this->failureMessage);
        }
        
        return $this->calls[$callId] ?? null;
    }
    
    /**
     * Mock create agent
     */
    public function createAgent(array $config): array
    {
        $this->simulateDelay();
        
        if ($this->shouldFail) {
            throw new \Exception($this->failureMessage);
        }
        
        $agentId = 'agent_' . Str::random(16);
        
        $agent = [
            'agent_id' => $agentId,
            'agent_name' => $config['agent_name'] ?? 'Test Agent',
            'voice_id' => $config['voice_id'] ?? 'de-DE-FlorianNeural',
            'language' => $config['language'] ?? 'de-DE',
            'webhook_url' => $config['webhook_url'] ?? null,
            'custom_keywords' => $config['custom_keywords'] ?? [],
            'custom_functions' => $config['custom_functions'] ?? [],
            'created_at' => now()->toIso8601String(),
            'status' => 'active'
        ];
        
        $this->agents[$agentId] = $agent;
        
        return $agent;
    }
    
    /**
     * Mock update agent
     */
    public function updateAgent(string $agentId, array $config): array
    {
        $this->simulateDelay();
        
        if ($this->shouldFail) {
            throw new \Exception($this->failureMessage);
        }
        
        if (!isset($this->agents[$agentId])) {
            throw new \Exception("Agent not found: {$agentId}");
        }
        
        $this->agents[$agentId] = array_merge($this->agents[$agentId], $config);
        $this->agents[$agentId]['updated_at'] = now()->toIso8601String();
        
        return $this->agents[$agentId];
    }
    
    /**
     * Mock delete agent
     */
    public function deleteAgent(string $agentId): bool
    {
        $this->simulateDelay();
        
        if ($this->shouldFail) {
            throw new \Exception($this->failureMessage);
        }
        
        if (!isset($this->agents[$agentId])) {
            return false;
        }
        
        unset($this->agents[$agentId]);
        return true;
    }
    
    /**
     * Mock update phone number
     */
    public function updatePhoneNumber(string $phoneNumber, array $config): array
    {
        $this->simulateDelay();
        
        if ($this->shouldFail) {
            throw new \Exception($this->failureMessage);
        }
        
        $this->phoneNumbers[$phoneNumber] = array_merge(
            $this->phoneNumbers[$phoneNumber] ?? [],
            $config,
            ['phone_number' => $phoneNumber]
        );
        
        return $this->phoneNumbers[$phoneNumber];
    }
    
    /**
     * Add a mock call to the service
     */
    public function addMockCall(array $callData): string
    {
        $callId = $callData['call_id'] ?? 'call_' . Str::random(16);
        
        $defaultCall = [
            'call_id' => $callId,
            'agent_id' => 'agent_test',
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'status' => 'ended',
            'start_timestamp' => time() - 300,
            'end_timestamp' => time(),
            'duration' => 300,
            'transcript' => 'Test conversation transcript',
            'recording_url' => 'https://example.com/recording.mp3',
            'public_log_url' => 'https://example.com/log',
            'call_analysis' => null,
            'metadata' => []
        ];
        
        $this->calls[$callId] = array_merge($defaultCall, $callData);
        
        return $callId;
    }
    
    /**
     * Add call analysis to a call
     */
    public function addCallAnalysis(string $callId, array $analysis): void
    {
        if (isset($this->calls[$callId])) {
            $this->calls[$callId]['call_analysis'] = array_merge([
                'summary' => 'Customer inquiry',
                'sentiment' => 'positive',
                'customer_name' => null,
                'customer_email' => null,
                'customer_phone' => null,
                'appointment_request' => false,
                'appointment_date' => null,
                'appointment_time' => null,
                'service_requested' => null,
                'action_items' => []
            ], $analysis);
        }
    }
    
    /**
     * Simulate webhook call
     */
    public function simulateWebhook(string $event, array $callData): array
    {
        $webhook = [
            'event' => $event,
            'timestamp' => time(),
            'call' => $callData
        ];
        
        // Generate signature
        $secret = config('services.retell.webhook_secret', 'test-secret');
        $body = json_encode($webhook);
        $signature = hash_hmac('sha256', $body, $secret);
        
        return [
            'payload' => $webhook,
            'headers' => [
                'x-retell-signature' => $signature,
                'x-retell-timestamp' => (string)time()
            ]
        ];
    }
    
    /**
     * Generate realistic call data
     */
    public function generateRealisticCall(array $options = []): array
    {
        $scenarios = [
            'appointment_booking' => [
                'transcript' => "Guten Tag, ich möchte gerne einen Termin vereinbaren. Am liebsten nächste Woche Dienstag um 14 Uhr.",
                'analysis' => [
                    'summary' => 'Kunde möchte Termin nächste Woche Dienstag um 14 Uhr',
                    'appointment_request' => true,
                    'customer_name' => 'Max Mustermann',
                    'appointment_date' => 'nächste Woche Dienstag',
                    'appointment_time' => '14:00',
                    'service_requested' => 'Beratung'
                ]
            ],
            'information_request' => [
                'transcript' => "Hallo, ich wollte nur wissen, ob Sie samstags geöffnet haben?",
                'analysis' => [
                    'summary' => 'Kunde fragt nach Öffnungszeiten am Samstag',
                    'appointment_request' => false,
                    'action_items' => ['Öffnungszeiten mitgeteilt']
                ]
            ],
            'cancellation' => [
                'transcript' => "Ich muss leider meinen Termin morgen absagen. Können wir einen neuen Termin ausmachen?",
                'analysis' => [
                    'summary' => 'Kunde möchte Termin absagen und neu vereinbaren',
                    'appointment_request' => true,
                    'action_items' => ['Termin stornieren', 'Neuen Termin vereinbaren']
                ]
            ],
            'wrong_number' => [
                'transcript' => "Entschuldigung, ich glaube ich habe mich verwählt.",
                'analysis' => [
                    'summary' => 'Falsche Nummer',
                    'appointment_request' => false
                ]
            ]
        ];
        
        $scenario = $options['scenario'] ?? array_rand($scenarios);
        $scenarioData = $scenarios[$scenario];
        
        $callId = $this->addMockCall(array_merge([
            'transcript' => $scenarioData['transcript'],
            'duration' => rand(30, 300),
            'from_number' => $options['from_number'] ?? '+49' . rand(1000000000, 9999999999)
        ], $options));
        
        $this->addCallAnalysis($callId, $scenarioData['analysis']);
        
        return $this->calls[$callId];
    }
    
    /**
     * Get all mock agents
     */
    public function getMockAgents(): array
    {
        return $this->agents;
    }
    
    /**
     * Get all mock calls
     */
    public function getMockCalls(): array
    {
        return $this->calls;
    }
    
    /**
     * Simulate delay if configured
     */
    private function simulateDelay(): void
    {
        if ($this->callDelay > 0) {
            usleep($this->callDelay * 1000);
        }
    }
}