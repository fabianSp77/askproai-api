<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Traits\RetryableHttpClient;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\Logging\ProductionLogger;
use App\Services\Security\SensitiveDataMasker;

class RetellV2Service          //  Telefon- & Agent-API (AWS)
{
    use RetryableHttpClient;
    
    private string $url;   // z. B. https://api.retellai.com
    private string $token;
    private CircuitBreaker $circuitBreaker;
    private ProductionLogger $logger;
    private SensitiveDataMasker $masker;

    public function __construct(?string $apiKey = null)
    {
        // Check multiple config locations
        $this->url = rtrim(
            config('retellai.base_url') ?? 
            config('services.retell.base_url', 'https://api.retellai.com'), 
            '/'
        );
        $this->token = $apiKey ?? config('services.retell.api_key');
        $this->circuitBreaker = new CircuitBreaker();
        $this->logger = new ProductionLogger();
        $this->masker = new SensitiveDataMasker();
    }

    /**
     *  Einen Anruf starten.
     *  Erforderlich   : from_number  (+E.164)
     *  Entweder ODER  : to_number    (+E.164)  **oder**  agent_id
     */
    public function createPhoneCall(array $payload): array
    {
        return $this->circuitBreaker->call('retell', function() use ($payload) {
            return $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/v2/create-phone-call', $payload)
                ->throw()
                ->json();
        });
    }
    
    /**
     * Create a new agent
     */
    public function createAgent(array $config): array
    {
        return $this->circuitBreaker->call('retell', function() use ($config) {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/v2/create-agent', $config);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->logger->logApiCall('RetellV2', 'createAgent', $this->masker->mask($config), $data, 0);
                return $data;
            }
            
            throw new \Exception("Failed to create agent: " . $response->body());
        });
    }
    
    /**
     * Update existing agent
     */
    public function updateAgent(string $agentId, array $config): array
    {
        return $this->circuitBreaker->call('retell', function() use ($agentId, $config) {
            $payload = array_merge(['agent_id' => $agentId], $config);
            
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/v2/update-agent', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->logger->logApiCall('RetellV2', 'updateAgent', $this->masker->mask($payload), $data, 0);
                return $data;
            }
            
            throw new \Exception("Failed to update agent: " . $response->body());
        });
    }
    
    /**
     * Get agent details
     */
    public function getAgent(string $agentId): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($agentId) {
            $url = $this->url . '/v2/list-agents';
            
            // Log the API call with masked data
            $this->logger->logApiCall('RetellV2', 'getAgent', ['agent_id' => $agentId], null, 0);
            
            // First try to get from list of agents
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($url, []);
            
            if ($response->successful()) {
                $data = $response->json();
                $agents = $data['agents'] ?? [];
                
                // Find the specific agent
                foreach ($agents as $agent) {
                    if ($agent['agent_id'] === $agentId) {
                        return $agent;
                    }
                }
            }
            
            $this->logger->logError(new \Exception('Failed to get agent'), [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500), // Limit body size
                'agent_id' => $agentId
            ]);
            
            return null;
        });
    }
    
    /**
     * List all agents
     */
    public function listAgents(): array
    {
        return $this->circuitBreaker->call('retell', function() {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/v2/list-agents', []);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['agents' => []];
        });
    }
    
    /**
     * Delete an agent
     */
    public function deleteAgent(string $agentId): bool
    {
        return $this->circuitBreaker->call('retell', function() use ($agentId) {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/v2/delete-agent', ['agent_id' => $agentId]);
            
            return $response->successful();
        });
    }
    
    /**
     * Update phone number configuration
     */
    public function updatePhoneNumber(string $phoneNumber, array $config): array
    {
        return $this->circuitBreaker->call('retell', function() use ($phoneNumber, $config) {
            $payload = array_merge(['phone_number' => $phoneNumber], $config);
            
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/v2/update-phone-number', $payload);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new \Exception("Failed to update phone number: " . $response->body());
        });
    }
    
    /**
     * Get call details
     */
    public function getCall(string $callId): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($callId) {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->get($this->url . '/v2/get-call/' . $callId);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        });
    }
    
    /**
     * List recent calls
     */
    public function listCalls(int $limit = 50): array
    {
        return $this->circuitBreaker->call('retell', function() use ($limit) {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/v2/list-calls', [
                    'limit' => $limit,
                    'sort_order' => 'descending'
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['calls' => []];
        });
    }
}
