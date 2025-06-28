<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
                ->post($this->url . '/create-phone-call', $payload)
                ->throw()
                ->json();
        });
    }
    
    /**
     * Create a new agent
     */
    public function createAgent(array $config): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($config) {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->post($this->url . '/create-agent', $config);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->logger->logApiCall('RetellV2', 'createAgent', $this->masker->mask($config), $data, 0);
                return $data;
            }
            
            $this->logger->logError(new \Exception('Failed to create agent'), [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
                'data' => $this->masker->mask($config)
            ]);
            
            return null;
        });
    }
    
    /**
     * Update existing agent
     */
    public function updateAgent(string $agentId, array $config): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($agentId, $config) {
            // Remove agent_id from payload if present as it goes in the URL
            unset($config['agent_id']);
            
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->patch($this->url . '/update-agent/' . $agentId, $config);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->logger->logApiCall('RetellV2', 'updateAgent', $this->masker->mask($config), $data, 0);
                return $data;
            }
            
            $this->logger->logError(new \Exception('Failed to update agent'), [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
                'agent_id' => $agentId,
                'data' => $this->masker->mask($config)
            ]);
            
            return null;
        });
    }
    
    /**
     * Get agent details
     */
    public function getAgent(string $agentId): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($agentId) {
            $url = $this->url . '/get-agent/' . $agentId;
            
            // Log the API call with masked data
            $this->logger->logApiCall('RetellV2', 'getAgent', ['agent_id' => $agentId], null, 0);
            
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->get($url);
            
            if ($response->successful()) {
                $agent = $response->json();
                // Cache agent for fallback
                Cache::put("retell_agent_{$agentId}", $agent, 3600);
                return $agent;
            }
            
            $this->logger->logError(new \Exception('Failed to get agent'), [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500), // Limit body size
                'agent_id' => $agentId
            ]);
            
            return null;
        }, function() use ($agentId) {
            // Fallback: Return cached agent if available
            $cachedAgent = Cache::get("retell_agent_{$agentId}");
            if ($cachedAgent) {
                Log::warning('Using cached agent due to circuit breaker', [
                    'agent_id' => $agentId
                ]);
                return $cachedAgent;
            }
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
                ->get($this->url . '/list-agents');
            
            if ($response->successful()) {
                $agents = $response->json();
                // Wrap in agents key if it's a direct array
                if (is_array($agents) && !isset($agents['agents'])) {
                    return ['agents' => $agents];
                }
                return $agents;
            }
            
            return ['agents' => []];
        }, function() {
            // Fallback: Return cached agents if available
            $cachedAgents = Cache::get('retell_agents_fallback', []);
            Log::warning('Using cached agents due to circuit breaker', [
                'cached_count' => count($cachedAgents)
            ]);
            return ['agents' => $cachedAgents];
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
                ->delete($this->url . '/delete-agent/' . $agentId);
            
            return $response->successful();
        });
    }
    
    /**
     * Update phone number configuration
     */
    public function updatePhoneNumber(string $phoneNumber, array $config): array
    {
        return $this->circuitBreaker->call('retell', function() use ($phoneNumber, $config) {
            // Remove phone_number from payload if present as it goes in the URL
            unset($config['phone_number']);
            
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->patch($this->url . '/update-phone-number/' . urlencode($phoneNumber), $config);
            
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
                ->get($this->url . '/get-call/' . $callId);
            
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
                $data = $response->json();
                // Normalize response to always have 'calls' key
                if (isset($data['results'])) {
                    return ['calls' => $data['results']];
                }
                return ['calls' => $data];
            }
            
            return ['calls' => []];
        });
    }
    
    /**
     * List all phone numbers
     */
    public function listPhoneNumbers(): array
    {
        return $this->circuitBreaker->call('retell', function() {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->get($this->url . '/list-phone-numbers');
            
            if ($response->successful()) {
                $phoneNumbers = $response->json();
                // Wrap in phone_numbers key if it's a direct array
                if (is_array($phoneNumbers) && !isset($phoneNumbers['phone_numbers'])) {
                    return ['phone_numbers' => $phoneNumbers];
                }
                return $phoneNumbers;
            }
            
            return ['phone_numbers' => []];
        });
    }
    
    /**
     * Get phone number details
     */
    public function getPhoneNumber(string $phoneNumberId): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($phoneNumberId) {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->get($this->url . '/get-phone-number/' . $phoneNumberId);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        });
    }
    
    
    /**
     * Get agent prompt only
     */
    public function getAgentPrompt(string $agentId): ?string
    {
        $agent = $this->getAgent($agentId);
        return $agent['prompt'] ?? null;
    }
    
    /**
     * Get Retell LLM configuration (contains prompt, model, custom functions)
     */
    public function getRetellLLM(string $llmId): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($llmId) {
            $url = $this->url . '/get-retell-llm/' . $llmId;
            
            $this->logger->logApiCall('RetellV2', 'getRetellLLM', ['llm_id' => $llmId], null, 0);
            
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            $this->logger->logError(new \Exception('Failed to get LLM'), [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
                'llm_id' => $llmId
            ]);
            
            return null;
        });
    }
    
    /**
     * Update Retell LLM configuration
     */
    public function updateRetellLLM(string $llmId, array $config): ?array
    {
        return $this->circuitBreaker->call('retell', function() use ($llmId, $config) {
            $url = $this->url . '/update-retell-llm/' . $llmId;
            
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->patch($url, $config);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->logger->logApiCall('RetellV2', 'updateRetellLLM', $this->masker->mask($config), $data, 0);
                return $data;
            }
            
            throw new \Exception("Failed to update LLM: " . $response->body());
        });
    }
    
    /**
     * List all Retell LLMs
     */
    public function listRetellLLMs(): array
    {
        return $this->circuitBreaker->call('retell', function() {
            $response = $this->httpWithRetry()
                ->withToken($this->token)
                ->get($this->url . '/list-retell-llms');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        });
    }
}
