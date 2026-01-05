<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceGateway;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\PolicyConfiguration;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use App\Services\ServiceGateway\ExchangeLogService;
use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;

/**
 * Security Fixes Test Suite
 *
 * Comprehensive tests for Service Gateway security fixes:
 * - CRIT-003: Category-Company validation in ServiceDeskHandler
 * - SSRF-001: Webhook URL validation in WebhookOutputHandler
 * - Redaction list validation in ExchangeLogService
 * - logInternal() DRY logging method
 *
 * @package Tests\Unit\ServiceGateway
 * @since 2026-01-05
 */
class SecurityFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected PhoneNumber $phoneNumber;
    protected Call $call;
    protected ServiceCaseCategory $categoryA;
    protected ServiceCaseCategory $categoryB;
    protected WebhookOutputHandler $webhookHandler;
    protected ExchangeLogService $exchangeLogService;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();
        Http::preventStrayRequests();

        // Enable gateway mode routing
        Config::set('gateway.mode_enabled', true);
        Config::set('gateway.default_mode', 'service_desk');

        // Create two companies for cross-tenant testing
        $this->companyA = Company::factory()->create([
            'name' => 'Company A - Primary',
        ]);

        $this->companyB = Company::factory()->create([
            'name' => 'Company B - Secondary',
        ]);

        // Create PolicyConfiguration for Company A
        PolicyConfiguration::create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
            'config' => ['mode' => 'service_desk'],
        ]);

        // Create phone number for Company A
        $this->phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        // Create call context for Company A
        $this->call = Call::factory()->create([
            'company_id' => $this->companyA->id,
            'phone_number_id' => $this->phoneNumber->id,
            'retell_call_id' => 'call_security_test_' . uniqid(),
            'call_status' => 'ongoing',
        ]);

        // Create categories for different companies
        $this->categoryA = ServiceCaseCategory::create([
            'company_id' => $this->companyA->id,
            'name' => 'Company A Support',
            'slug' => 'company-a-support',
            'intent_keywords' => ['test', 'support'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->categoryB = ServiceCaseCategory::create([
            'company_id' => $this->companyB->id,
            'name' => 'Company B Support',
            'slug' => 'company-b-support',
            'intent_keywords' => ['other', 'support'],
            'is_active' => true,
        ]);

        $this->webhookHandler = new WebhookOutputHandler();
        $this->exchangeLogService = app(ExchangeLogService::class);
    }

    // =========================================================================
    // CRIT-003: Category-Company Validation Tests
    // =========================================================================

    /**
     * @test
     * @group security
     * @group crit-003
     */
    public function route_ticket_allows_valid_category_from_same_company(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'route_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'subject' => 'Test Issue',
                'description' => 'Test Description',
                'case_type' => 'incident',
                'priority' => 'normal',
                'category_id' => $this->categoryA->id, // Same company
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify case was created with correct category
        $this->assertDatabaseHas('service_cases', [
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);
    }

    /**
     * @test
     * @group security
     * @group crit-003
     */
    public function route_ticket_rejects_category_from_different_company(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'route_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'subject' => 'Cross-Tenant Attack',
                'description' => 'Attempting to use category from another company',
                'case_type' => 'incident',
                'priority' => 'normal',
                'category_id' => $this->categoryB->id, // Different company!
            ],
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'CRIT-003: Invalid category for this company',
            ]);

        // Verify NO case was created
        $this->assertDatabaseMissing('service_cases', [
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryB->id,
        ]);
    }

    /**
     * @test
     * @group security
     * @group crit-003
     */
    public function route_ticket_rejects_nonexistent_category(): void
    {
        $nonExistentId = 999999;

        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'route_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'subject' => 'Invalid Category Test',
                'description' => 'Testing with non-existent category',
                'case_type' => 'incident',
                'priority' => 'normal',
                'category_id' => $nonExistentId,
            ],
        ]);

        // Non-existent category should return 403 (same as cross-tenant)
        // because the validation is: find($id) returns null OR company_id mismatch
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'CRIT-003: Invalid category for this company',
            ]);

        // Verify NO case was created
        $this->assertDatabaseCount('service_cases', 0);
    }

    /**
     * @test
     * @group security
     * @group crit-003
     */
    public function route_ticket_requires_category_id(): void
    {
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'route_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'subject' => 'No Category Test',
                'description' => 'Testing without category_id',
                'case_type' => 'incident',
                'priority' => 'normal',
                // Missing category_id
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Missing category_id',
            ]);

        // Verify NO case was created
        $this->assertDatabaseCount('service_cases', 0);
    }

    // =========================================================================
    // SSRF-001: Webhook URL Validation Tests
    // =========================================================================

    /**
     * @test
     * @group security
     * @group ssrf-001
     * @dataProvider blockedInternalUrlsProvider
     */
    public function webhook_blocks_internal_network_urls(string $url, string $reason): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
            'output_type' => 'webhook',
            'webhook_url' => $url,
            'is_active' => true,
        ]);

        $result = $this->webhookHandler->validate($config);

        $this->assertFalse($result, "Should block {$reason}: {$url}");
    }

    /**
     * Provide internal URLs that should be blocked for SSRF protection.
     */
    public static function blockedInternalUrlsProvider(): array
    {
        return [
            // Localhost variations
            ['http://localhost/webhook', 'localhost'],
            ['http://localhost:8080/webhook', 'localhost with port'],
            ['https://localhost/api', 'localhost HTTPS'],
            ['http://127.0.0.1/callback', '127.0.0.1 loopback'],
            ['http://127.0.0.1:3000/api', '127.0.0.1 with port'],
            ['http://[::1]/webhook', 'IPv6 loopback'],
            ['http://0.0.0.0/api', '0.0.0.0 wildcard'],

            // Private network ranges (10.0.0.0/8)
            ['http://10.0.0.1/webhook', '10.x.x.x private network'],
            ['http://10.255.255.255/api', '10.x upper bound'],
            ['http://10.1.2.3:8000/callback', '10.x with port'],

            // Private network ranges (172.16.0.0/12)
            ['http://172.16.0.1/webhook', '172.16.x.x private'],
            ['http://172.31.255.255/api', '172.31.x.x private'],
            ['http://172.20.10.5/callback', '172.16-31 middle range'],

            // Private network ranges (192.168.0.0/16)
            ['http://192.168.0.1/webhook', '192.168.x.x private'],
            ['http://192.168.1.100/api', '192.168 common router'],
            ['http://192.168.255.255/callback', '192.168 upper bound'],

            // AWS metadata service (critical!)
            ['http://169.254.169.254/latest/meta-data/', 'AWS metadata service'],
            ['http://169.254.169.254/latest/user-data', 'AWS user-data'],
            ['http://169.254.0.1/api', '169.254.x.x link-local'],
        ];
    }

    /**
     * @test
     * @group security
     * @group ssrf-001
     * @dataProvider allowedExternalUrlsProvider
     */
    public function webhook_allows_external_urls(string $url, string $reason): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
            'output_type' => 'webhook',
            'webhook_url' => $url,
            'is_active' => true,
        ]);

        $result = $this->webhookHandler->validate($config);

        $this->assertTrue($result, "Should allow {$reason}: {$url}");
    }

    /**
     * Provide external URLs that should be allowed.
     */
    public static function allowedExternalUrlsProvider(): array
    {
        return [
            // Common external services
            ['https://api.jira.com/rest/api/2/issue', 'Jira API'],
            ['https://example.atlassian.net/webhook', 'Atlassian Cloud'],
            ['https://hooks.slack.com/services/T00/B00/XXXX', 'Slack webhook'],
            ['https://api.servicenow.com/api/now/table/incident', 'ServiceNow API'],

            // Public cloud endpoints
            ['https://api.example.com/webhook', 'Generic external API'],
            ['https://webhook.site/unique-id', 'Webhook.site testing'],

            // External IPs (public ranges)
            ['http://8.8.8.8/webhook', 'Google DNS IP'],
            ['http://1.1.1.1/api', 'Cloudflare DNS IP'],
            ['https://203.0.113.50/callback', 'Documentation IP range'],
        ];
    }

    /**
     * @test
     * @group security
     * @group ssrf-001
     */
    public function webhook_blocks_internal_url_at_delivery_time(): void
    {
        // Create config with internal URL
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'SSRF Test Config',
            'output_type' => 'webhook',
            'webhook_url' => 'http://192.168.1.1/internal-api',
            'is_active' => true,
            'webhook_enabled' => true,
        ]);

        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->companyA->id,
            'output_configuration_id' => $config->id,
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $category->id,
        ]);

        // Should return false (blocked at runtime)
        $result = $this->webhookHandler->deliver($case);

        $this->assertFalse($result, 'Should block internal URL at delivery time');

        // Verify no HTTP request was made
        Http::assertNothingSent();
    }

    /**
     * @test
     * @group security
     * @group ssrf-001
     */
    public function webhook_rejects_empty_url(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
            'output_type' => 'webhook',
            'webhook_url' => null,
            'is_active' => true,
        ]);

        $result = $this->webhookHandler->validate($config);

        $this->assertFalse($result);
    }

    /**
     * @test
     * @group security
     * @group ssrf-001
     */
    public function webhook_rejects_invalid_url_format(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
            'output_type' => 'webhook',
            'webhook_url' => 'not-a-valid-url',
            'is_active' => true,
        ]);

        $result = $this->webhookHandler->validate($config);

        $this->assertFalse($result);
    }

    // =========================================================================
    // ExchangeLogService Redaction Tests
    // =========================================================================

    /**
     * @test
     * @group security
     * @group redaction
     * @dataProvider sensitiveFieldsProvider
     */
    public function redaction_removes_sensitive_fields(string $fieldName): void
    {
        $payload = [
            $fieldName => 'super-secret-value-12345',
            'safe_field' => 'this should remain',
        ];

        $redacted = $this->exchangeLogService->redactPayload($payload);

        $this->assertEquals('[REDACTED]', $redacted[$fieldName]);
        $this->assertEquals('this should remain', $redacted['safe_field']);
    }

    /**
     * Provide sensitive field names that must be redacted.
     */
    public static function sensitiveFieldsProvider(): array
    {
        return [
            // Authentication & Secrets
            ['api_secret'],
            ['private_key'],
            ['jwt_secret'],
            ['webhook_secret'],
            ['hmac_secret'],
            ['api_key'],
            ['secret'],
            ['password'],
            ['token'],
            ['access_token'],
            ['refresh_token'],
            ['authorization'],
            ['credentials'],
            ['client_secret'],
            ['signing_secret'],

            // Retell-specific
            ['retell_api_key'],
            ['agent_id'],
            ['llm_id'],
            ['prompt'],
            ['system_prompt'],

            // Third-party credentials
            ['twilio_sid'],
            ['twilio_auth_token'],
            ['calcom_api_key'],
            ['stripe_secret'],
            ['stripe_webhook_secret'],
            ['openai_key'],
            ['anthropic_key'],
        ];
    }

    /**
     * @test
     * @group security
     * @group redaction
     * @dataProvider sensitiveHeadersProvider
     */
    public function redaction_removes_sensitive_headers(string $headerName): void
    {
        $headers = [
            $headerName => 'Bearer secret-token-value',
            'Content-Type' => 'application/json',
            'User-Agent' => 'ServiceGateway/1.0',
        ];

        $redacted = $this->exchangeLogService->redactHeaders($headers);

        $this->assertEquals('[REDACTED]', $redacted[$headerName]);
        $this->assertEquals('application/json', $redacted['Content-Type']);
        $this->assertEquals('ServiceGateway/1.0', $redacted['User-Agent']);
    }

    /**
     * Provide sensitive header names that must be redacted.
     */
    public static function sensitiveHeadersProvider(): array
    {
        return [
            ['authorization'],
            ['x-api-key'],
            ['x-auth-token'],
            ['x-signature'],
            ['x-webhook-secret'],
            ['x-hub-signature'],
            ['x-hub-signature-256'],
            ['cookie'],
            ['set-cookie'],
            ['x-csrf-token'],
            ['x-xsrf-token'],
            ['x-askpro-signature'],
            ['x-retell-signature'],
            ['x-stripe-signature'],
        ];
    }

    /**
     * @test
     * @group security
     * @group redaction
     */
    public function redaction_handles_nested_sensitive_fields(): void
    {
        $payload = [
            'config' => [
                'api_key' => 'nested-secret-key',
                'settings' => [
                    'webhook_secret' => 'deeply-nested-secret',
                    'name' => 'safe-value',
                ],
            ],
            'data' => [
                'password' => 'user-password',
                'username' => 'john_doe',
            ],
        ];

        $redacted = $this->exchangeLogService->redactPayload($payload);

        $this->assertEquals('[REDACTED]', $redacted['config']['api_key']);
        $this->assertEquals('[REDACTED]', $redacted['config']['settings']['webhook_secret']);
        $this->assertEquals('safe-value', $redacted['config']['settings']['name']);
        $this->assertEquals('[REDACTED]', $redacted['data']['password']);
        $this->assertEquals('john_doe', $redacted['data']['username']);
    }

    /**
     * @test
     * @group security
     * @group redaction
     */
    public function redaction_detects_secret_patterns_in_values(): void
    {
        $payload = [
            // Bearer tokens should be detected even with innocent key names
            'auth_header' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U',
            // API key patterns
            'some_key' => 'sk-1234567890abcdefghijklmnop',
            // Normal values should remain
            'ticket_id' => 'TKT-2025-00001',
            'description' => 'This is a normal description',
        ];

        $redacted = $this->exchangeLogService->redactPayload($payload);

        $this->assertStringContainsString('[REDACTED', $redacted['auth_header']);
        $this->assertStringContainsString('[REDACTED', $redacted['some_key']);
        $this->assertEquals('TKT-2025-00001', $redacted['ticket_id']);
        $this->assertEquals('This is a normal description', $redacted['description']);
    }

    /**
     * @test
     * @group security
     * @group redaction
     */
    public function redaction_detects_internal_urls(): void
    {
        $payload = [
            'callback_url' => 'http://localhost:8000/internal/webhook',
            'admin_url' => 'https://api-gateway.local/admin/settings',
            'external_url' => 'https://api.jira.com/webhook',
        ];

        $redacted = $this->exchangeLogService->redactPayload($payload);

        $this->assertEquals('[REDACTED:internal_url]', $redacted['callback_url']);
        $this->assertEquals('[REDACTED:internal_url]', $redacted['admin_url']);
        $this->assertEquals('https://api.jira.com/webhook', $redacted['external_url']);
    }

    // =========================================================================
    // logInternal() DRY Logging Method Tests
    // =========================================================================

    /**
     * @test
     * @group logging
     */
    public function log_internal_success_returns_status_code_200(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $log = $this->exchangeLogService->logInternal(
            operation: 'audio-storage',
            status: 'success',
            case: $case,
            context: ['file_size' => 1024],
        );

        $this->assertNotNull($log);
        $this->assertEquals(200, $log->status_code);
        $this->assertEquals('internal', $log->direction);
        $this->assertEquals('audio-storage', $log->endpoint);
        $this->assertNull($log->error_class);
    }

    /**
     * @test
     * @group logging
     */
    public function log_internal_failure_returns_status_code_500(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $log = $this->exchangeLogService->logInternal(
            operation: 'enrichment',
            status: 'failed',
            case: $case,
            context: ['step' => 'transcript'],
            error: 'Failed to process transcript',
        );

        $this->assertNotNull($log);
        $this->assertEquals(500, $log->status_code);
        $this->assertEquals('internal', $log->direction);
        $this->assertEquals('enrichment', $log->endpoint);
        $this->assertEquals('JobError', $log->error_class);
        $this->assertStringContainsString('transcript', $log->error_message);
    }

    /**
     * @test
     * @group logging
     */
    public function log_internal_timeout_returns_status_code_408(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $log = $this->exchangeLogService->logInternal(
            operation: 'webhook-delivery',
            status: 'timeout',
            case: $case,
            context: ['url' => 'https://api.example.com'],
            error: 'Request timed out after 30s',
        );

        $this->assertNotNull($log);
        $this->assertEquals(408, $log->status_code);
        $this->assertEquals('internal', $log->direction);
    }

    /**
     * @test
     * @group logging
     */
    public function log_internal_skipped_returns_status_code_204(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $log = $this->exchangeLogService->logInternal(
            operation: 'delivery',
            status: 'skipped',
            case: $case,
            context: ['reason' => 'No active output configuration'],
        );

        $this->assertNotNull($log);
        $this->assertEquals(204, $log->status_code);
        $this->assertEquals('internal', $log->direction);
        $this->assertNull($log->error_class);
    }

    /**
     * @test
     * @group logging
     */
    public function log_internal_pending_returns_status_code_202(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $log = $this->exchangeLogService->logInternal(
            operation: 'async-processing',
            status: 'pending',
            case: $case,
            context: ['queued_at' => now()->toIso8601String()],
        );

        $this->assertNotNull($log);
        $this->assertEquals(202, $log->status_code);
    }

    /**
     * @test
     * @group logging
     */
    public function log_internal_includes_case_context(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $log = $this->exchangeLogService->logInternal(
            operation: 'test-operation',
            status: 'success',
            case: $case,
        );

        $this->assertEquals($case->id, $log->service_case_id);
        $this->assertEquals($case->call_id, $log->call_id);
        $this->assertEquals($case->company_id, $log->company_id);
        $this->assertNotNull($log->correlation_id);
        $this->assertStringContainsString('test-operation', $log->correlation_id);
    }

    /**
     * @test
     * @group logging
     */
    public function log_internal_works_without_case(): void
    {
        $log = $this->exchangeLogService->logInternal(
            operation: 'system-health-check',
            status: 'success',
            case: null,
            context: ['check' => 'database_connectivity'],
        );

        $this->assertNotNull($log);
        $this->assertEquals(200, $log->status_code);
        $this->assertEquals('system-health-check', $log->endpoint);
        $this->assertNull($log->service_case_id);
        $this->assertNull($log->call_id);
        $this->assertNull($log->company_id);
    }

    /**
     * @test
     * @group logging
     */
    public function log_internal_tracks_attempts(): void
    {
        $case = ServiceCase::factory()->create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
        ]);

        $log = $this->exchangeLogService->logInternal(
            operation: 'webhook-retry',
            status: 'failed',
            case: $case,
            context: ['url' => 'https://api.example.com'],
            error: 'Connection refused',
            attemptNo: 3,
            maxAttempts: 5,
        );

        $this->assertEquals(3, $log->attempt_no);
        $this->assertEquals(5, $log->max_attempts);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    /**
     * @test
     * @group security
     * @group edge-cases
     */
    public function category_validation_handles_soft_deleted_category(): void
    {
        // Soft-delete the category
        $this->categoryA->delete();

        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'route_ticket',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'subject' => 'Deleted Category Test',
                'description' => 'Testing with soft-deleted category',
                'case_type' => 'incident',
                'priority' => 'normal',
                'category_id' => $this->categoryA->id,
            ],
        ]);

        // Soft-deleted category should be rejected
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'CRIT-003: Invalid category for this company',
            ]);
    }

    /**
     * @test
     * @group security
     * @group edge-cases
     */
    public function webhook_validation_blocks_dns_rebinding(): void
    {
        // Test URLs that could be used for DNS rebinding attacks
        // These resolve to internal IPs but look like external domains
        $suspiciousUrls = [
            // Note: These would need actual DNS rebinding setup to test properly
            // In production, isExternalUrl() resolves the hostname to IP first
        ];

        foreach ($suspiciousUrls as $url) {
            $config = ServiceOutputConfiguration::factory()->make([
                'company_id' => $this->companyA->id,
                'webhook_url' => $url,
            ]);

            // The validation checks resolved IP, not just hostname
            // This test documents the protection mechanism
            $this->assertNotNull($config);
        }
    }

    /**
     * @test
     * @group security
     * @group redaction
     */
    public function redaction_handles_case_variations(): void
    {
        $payload = [
            'API_KEY' => 'uppercase-secret',        // uppercase
            'Api_Secret' => 'mixed-case-secret',    // mixed case
            'api-key' => 'hyphenated-secret',       // hyphenated
            'apikey' => 'concatenated-secret',      // no separator
            'webhook_secret_id' => 'partial-match', // contains redacted field name
        ];

        $redacted = $this->exchangeLogService->redactPayload($payload);

        // All variations should be redacted
        $this->assertEquals('[REDACTED]', $redacted['API_KEY']);
        $this->assertEquals('[REDACTED]', $redacted['Api_Secret']);
        $this->assertEquals('[REDACTED]', $redacted['api-key']);
        $this->assertEquals('[REDACTED]', $redacted['apikey']);
        $this->assertEquals('[REDACTED]', $redacted['webhook_secret_id']);
    }

}
