<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServiceOutputConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CLI tool to simulate different webhook response scenarios.
 *
 * Use this to understand how our system handles various responses
 * from external webhook endpoints (Visionary, etc.)
 */
class TestWebhookResponsesCommand extends Command
{
    protected $signature = 'webhook:test-responses
        {config_id : The ServiceOutputConfiguration ID to test}
        {--scenario=all : Scenario to test (all|success|client-error|server-error|connection)}
        {--real : Send real request instead of simulation}';

    protected $description = 'Simulate different webhook response scenarios for testing';

    /**
     * Response scenarios to test
     */
    private array $scenarios = [
        'success' => [
            '200_ok' => [200, ['message' => 'Workflow was started']],
            '201_created' => [201, ['id' => 'ticket-123', 'status' => 'created']],
            '202_accepted' => [202, ['status' => 'queued', 'job_id' => 'async-456']],
        ],
        'client-error' => [
            '400_bad_request' => [400, ['error' => 'Invalid payload format']],
            '401_unauthorized' => [401, ['error' => 'Invalid signature']],
            '403_forbidden' => [403, ['error' => 'Access denied', 'reason' => 'IP not whitelisted']],
            '404_not_found' => [404, ['error' => 'Endpoint not found']],
            '422_validation' => [422, ['error' => 'Validation failed', 'errors' => ['priority' => 'Invalid']]],
            '429_rate_limit' => [429, ['error' => 'Rate limit exceeded', 'retry_after' => 60]],
        ],
        'server-error' => [
            '500_internal' => [500, ['error' => 'Internal server error']],
            '502_bad_gateway' => [502, '<html>Bad Gateway</html>'],
            '503_unavailable' => [503, ['error' => 'Service temporarily unavailable']],
        ],
        'connection' => [
            'timeout' => ['exception', 'Connection timed out after 30 seconds'],
            'dns_failure' => ['exception', 'Could not resolve host: invalid-host.example.com'],
            'ssl_error' => ['exception', 'SSL certificate problem: unable to verify'],
        ],
    ];

    public function handle(): int
    {
        $configId = (int) $this->argument('config_id');
        $scenario = $this->option('scenario');
        $real = $this->option('real');

        $config = ServiceOutputConfiguration::find($configId);

        if (!$config) {
            $this->error("Configuration ID {$configId} not found!");
            return 1;
        }

        if (empty($config->webhook_url)) {
            $this->error("Configuration has no webhook URL configured!");
            return 1;
        }

        $this->info("Testing webhook for: {$config->name}");
        $this->info("URL: {$config->webhook_url}");
        $this->newLine();

        if ($real) {
            return $this->sendRealRequest($config);
        }

        return $this->runSimulations($config, $scenario);
    }

    /**
     * Send real request to webhook endpoint
     */
    private function sendRealRequest(ServiceOutputConfiguration $config): int
    {
        $this->warn("Sending REAL request to webhook endpoint...");
        $this->newLine();

        $payload = $this->buildTestPayload($config);
        $headers = $this->buildHeaders($config, $payload);

        try {
            $startTime = microtime(true);

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($config->webhook_url, $payload);

            $duration = round((microtime(true) - $startTime) * 1000);

            $this->displayResponse(
                'Real Request',
                $response->status(),
                $response->body(),
                $duration
            );

            return $response->successful() ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("Connection Error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Run simulated response scenarios
     */
    private function runSimulations(ServiceOutputConfiguration $config, string $scenario): int
    {
        $scenariosToRun = [];

        if ($scenario === 'all') {
            foreach ($this->scenarios as $group => $tests) {
                $scenariosToRun = array_merge($scenariosToRun, $tests);
            }
        } elseif (isset($this->scenarios[$scenario])) {
            $scenariosToRun = $this->scenarios[$scenario];
        } else {
            $this->error("Unknown scenario: {$scenario}");
            $this->info("Available: all, success, client-error, server-error, connection");
            return 1;
        }

        $this->info("Running " . count($scenariosToRun) . " simulated scenarios...");
        $this->newLine();

        $results = [];

        foreach ($scenariosToRun as $name => $definition) {
            $result = $this->simulateScenario($config, $name, $definition);
            $results[$name] = $result;
        }

        $this->displaySummary($results);

        return 0;
    }

    /**
     * Simulate a single scenario
     */
    private function simulateScenario(ServiceOutputConfiguration $config, string $name, array $definition): array
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Scenario: {$name}");

        $payload = $this->buildTestPayload($config);
        $headers = $this->buildHeaders($config, $payload);

        if ($definition[0] === 'exception') {
            // Simulate connection error
            $this->warn("  → Simulating exception: {$definition[1]}");
            $handling = $this->describeExceptionHandling($name);
            $this->line("  → Our handling: {$handling}");

            return [
                'status' => 'exception',
                'message' => $definition[1],
                'handling' => $handling,
            ];
        }

        [$status, $body] = $definition;

        $this->line("  → Status: {$status}");
        $this->line("  → Body: " . (is_array($body) ? json_encode($body) : substr($body, 0, 50)));

        $handling = $this->describeStatusHandling($status);
        $this->line("  → Our handling: {$handling}");

        return [
            'status' => $status,
            'body' => $body,
            'handling' => $handling,
        ];
    }

    /**
     * Describe how we handle different status codes
     */
    private function describeStatusHandling(int $status): string
    {
        return match (true) {
            $status >= 200 && $status < 300 => '✅ SUCCESS - Logged as successful delivery',
            $status === 400 => '❌ BAD REQUEST - Check payload format, no retry',
            $status === 401 => '🔐 UNAUTHORIZED - Check HMAC secret, no retry',
            $status === 403 => '🚫 FORBIDDEN - Check IP whitelist/permissions, no retry',
            $status === 404 => '❓ NOT FOUND - Check webhook URL, no retry',
            $status === 422 => '⚠️ VALIDATION - Check payload data, no retry',
            $status === 429 => '⏳ RATE LIMITED - Should retry after delay',
            $status >= 500 && $status < 600 => '🔄 SERVER ERROR - Should retry with backoff',
            default => '❓ UNKNOWN - Logged for investigation',
        };
    }

    /**
     * Describe how we handle exceptions
     */
    private function describeExceptionHandling(string $type): string
    {
        return match ($type) {
            'timeout' => '⏱️ TIMEOUT - Increase timeout or check endpoint, may retry',
            'dns_failure' => '🌐 DNS ERROR - Check URL hostname, no retry until fixed',
            'ssl_error' => '🔒 SSL ERROR - Check certificate validity, no retry until fixed',
            default => '❓ CONNECTION ERROR - Investigate and may retry',
        };
    }

    /**
     * Display real response
     */
    private function displayResponse(string $label, int $status, string $body, float|int $durationMs): void
    {
        $statusColor = match (true) {
            $status >= 200 && $status < 300 => 'green',
            $status >= 400 && $status < 500 => 'yellow',
            default => 'red',
        };

        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info($label);
        $this->line("  Status: <fg={$statusColor}>{$status}</>");
        $this->line("  Duration: {$durationMs}ms");
        $this->line("  Response: " . substr($body, 0, 200));

        if (strlen($body) > 200) {
            $this->line("  ... (truncated)");
        }
    }

    /**
     * Display summary table
     */
    private function displaySummary(array $results): void
    {
        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("SUMMARY - How to handle each response:");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->newLine();
        $this->table(
            ['Scenario', 'Status', 'Action'],
            collect($results)->map(fn ($r, $name) => [
                $name,
                is_int($r['status']) ? $r['status'] : $r['status'],
                $r['handling'],
            ])->toArray()
        );

        $this->newLine();
        $this->info("Recommendations:");
        $this->line("• 2xx responses: Log success, continue normally");
        $this->line("• 4xx responses: Log error, alert admin, don't auto-retry");
        $this->line("• 429 response: Respect Retry-After header, retry later");
        $this->line("• 5xx responses: Log error, retry with exponential backoff");
        $this->line("• Connection errors: Log, check configuration, may retry");
    }

    /**
     * Build test payload
     */
    private function buildTestPayload(ServiceOutputConfiguration $config): array
    {
        return [
            'fields' => [
                'summary' => '[CLI-TEST] Webhook Response Test',
                'description' => 'Testing webhook response handling from CLI',
                'issuetype' => ['name' => 'Task'],
                'priority' => ['name' => 'Low'],
            ],
            'meta' => [
                'source' => 'askpro_service_gateway',
                'is_test' => true,
                'company_id' => $config->company_id,
                'test_type' => 'cli_response_test',
            ],
            'test' => [
                'is_test' => true,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Build HTTP headers
     */
    private function buildHeaders(ServiceOutputConfiguration $config, array $payload): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'AskProServiceGateway/1.0',
            'X-AskPro-Event' => 'webhook.test',
            'X-AskPro-Test' => 'true',
        ];

        if (!empty($config->webhook_secret)) {
            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, $config->webhook_secret);
            $headers['X-AskPro-Signature'] = $signature;
        }

        return $headers;
    }
}
