<?php

namespace Tests\Unit\ServiceGateway;

use Tests\TestCase;
use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookOutputHandlerTest extends TestCase
{
    use RefreshDatabase;

    private WebhookOutputHandler $handler;
    private Company $company;
    private ServiceOutputConfiguration $config;
    private ServiceCaseCategory $category;
    private ServiceCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests(); // Prevent real HTTP calls

        $this->handler = new WebhookOutputHandler();
        $this->company = Company::factory()->create();

        $this->config = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Jira Integration',
            'output_type' => 'webhook',
            'webhook_url' => 'https://jira.example.com/rest/api/2/issue',
            'webhook_secret' => 'test-secret-key',
            'is_active' => true,
        ]);

        $this->category = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'IT Support',
            'slug' => 'it-support',
            'output_configuration_id' => $this->config->id,
            'is_active' => true,
        ]);

        $this->case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Case for Webhook',
            'description' => 'Testing webhook delivery',
            'case_type' => 'incident',
            'priority' => 'normal',
        ]);
    }

    /** @test */
    public function test_webhook_delivery_success(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response([
                'id' => '12345',
                'key' => 'ASKPRO-123',
            ], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => 'high',
        ]);

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com') &&
                   $request->hasHeader('Content-Type', 'application/json');
        });

        // Verify external reference stored
        $case->refresh();
        $this->assertNotNull($case->external_reference);
        $externalRef = json_decode($case->external_reference, true);
        $this->assertEquals('12345', $externalRef['id']);
        $this->assertEquals('ASKPRO-123', $externalRef['key']);
    }

    /** @test */
    public function test_webhook_delivery_failure_http_error(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response([
                'errorMessages' => ['Project does not exist'],
            ], 404),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => 'high',
        ]);

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com');
        });

        // Verify no external reference stored on failure
        $case->refresh();
        $this->assertNull($case->external_reference);
    }

    /** @test */
    public function test_webhook_delivery_failure_server_error(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response([
                'error' => 'Internal Server Error',
            ], 500),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => 'critical',
        ]);

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_webhook_delivery_failure_timeout(): void
    {
        Http::fake([
            'jira.example.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'question',
            'priority' => 'low',
        ]);

        $result = $this->handler->deliver($case);

        $this->assertFalse($result);

        // Verify exception was caught and logged
        $case->refresh();
        $this->assertNull($case->external_reference);
    }

    /** @test */
    public function test_hmac_signature_generation(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response(['id' => '12345'], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => 'high',
        ]);

        $this->handler->deliver($case);

        Http::assertSent(function ($request) {
            // Verify X-Webhook-Signature header is present
            if (!$request->hasHeader('X-Webhook-Signature')) {
                return false;
            }

            $signature = $request->header('X-Webhook-Signature')[0];

            // Verify it's a valid HMAC SHA256 signature (64 hex chars)
            return preg_match('/^[a-f0-9]{64}$/', $signature) === 1;
        });
    }

    /** @test */
    public function test_hmac_signature_not_included_without_secret(): void
    {
        // Create config without webhook_secret
        $configNoSecret = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Jira No Secret',
            'output_type' => 'webhook',
            'webhook_url' => 'https://jira.example.com/rest/api/2/issue',
            'webhook_secret' => null,
            'is_active' => true,
        ]);

        $categoryNoSecret = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'IT Support No Secret',
            'slug' => 'it-support-no-secret',
            'output_configuration_id' => $configNoSecret->id,
            'is_active' => true,
        ]);

        Http::fake([
            'jira.example.com/*' => Http::response(['id' => '12345'], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $categoryNoSecret->id,
            'subject' => 'Test Issue',
            'description' => 'Test Description',
            'case_type' => 'incident',
            'priority' => 'high',
        ]);

        $this->handler->deliver($case);

        Http::assertSent(function ($request) {
            // Verify X-Webhook-Signature header is NOT present
            return !$request->hasHeader('X-Webhook-Signature');
        });
    }

    /** @test */
    public function test_payload_template_rendering(): void
    {
        // Update config with custom payload template
        $this->config->update([
            'payload_template' => json_encode([
                'fields' => [
                    'project' => ['key' => 'ASKPRO'],
                    'summary' => '{{subject}}',
                    'description' => '{{description}}',
                    'issuetype' => ['name' => '{{mapped_case_type}}'],
                    'priority' => ['name' => '{{mapped_priority}}'],
                    'labels' => ['askpro-ai', 'category-{{category_slug}}'],
                ],
            ]),
        ]);

        Http::fake([
            'jira.example.com/*' => Http::response(['id' => '12345'], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Customer Complaint',
            'description' => 'Payment processing failed',
            'case_type' => 'incident',
            'priority' => 'critical',
        ]);

        $this->handler->deliver($case);

        Http::assertSent(function ($request) use ($case) {
            $body = json_decode($request->body(), true);

            return $body['fields']['summary'] === 'Customer Complaint' &&
                   $body['fields']['description'] === 'Payment processing failed' &&
                   $body['fields']['issuetype']['name'] === 'Bug' &&
                   $body['fields']['priority']['name'] === 'Highest' &&
                   in_array('category-it-support', $body['fields']['labels']);
        });
    }

    /** @test */
    public function test_default_jira_payload_format(): void
    {
        // Use config without custom payload template (should use default)
        Http::fake([
            'jira.example.com/*' => Http::response(['id' => '12345', 'key' => 'ASKPRO-123'], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Default Format Test',
            'description' => 'Testing default payload structure',
            'case_type' => 'feature_request',
            'priority' => 'medium',
        ]);

        $this->handler->deliver($case);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            // Verify default Jira structure
            return isset($body['fields']) &&
                   isset($body['fields']['project']) &&
                   isset($body['fields']['summary']) &&
                   isset($body['fields']['description']) &&
                   isset($body['fields']['issuetype']) &&
                   isset($body['fields']['priority']) &&
                   $body['fields']['summary'] === 'Default Format Test' &&
                   $body['fields']['description'] === 'Testing default payload structure';
        });
    }

    /** @test */
    public function test_external_reference_stored(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response([
                'id' => '67890',
                'key' => 'SUPPORT-456',
                'self' => 'https://jira.example.com/rest/api/2/issue/67890',
            ], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'External Ref Test',
            'description' => 'Testing external reference storage',
            'case_type' => 'incident',
            'priority' => 'high',
        ]);

        $this->assertNull($case->external_reference);

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        $case->refresh();
        $this->assertNotNull($case->external_reference);

        $externalRef = json_decode($case->external_reference, true);
        $this->assertEquals('67890', $externalRef['id']);
        $this->assertEquals('SUPPORT-456', $externalRef['key']);
        $this->assertEquals('https://jira.example.com/rest/api/2/issue/67890', $externalRef['self']);
    }

    /** @test */
    public function test_custom_headers_merged(): void
    {
        // Update config with custom headers
        $this->config->update([
            'custom_headers' => json_encode([
                'X-Atlassian-Token' => 'no-check',
                'X-Custom-Header' => 'custom-value',
                'Authorization' => 'Bearer custom-token',
            ]),
        ]);

        Http::fake([
            'jira.example.com/*' => Http::response(['id' => '12345'], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Custom Headers Test',
            'description' => 'Testing custom header merging',
            'case_type' => 'incident',
            'priority' => 'high',
        ]);

        $this->handler->deliver($case);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Atlassian-Token', 'no-check') &&
                   $request->hasHeader('X-Custom-Header', 'custom-value') &&
                   $request->hasHeader('Authorization', 'Bearer custom-token') &&
                   $request->hasHeader('Content-Type', 'application/json');
        });
    }

    /** @test */
    public function test_case_type_mapping(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response(['id' => '12345'], 201),
        ]);

        $testCases = [
            ['case_type' => 'incident', 'expected' => 'Bug'],
            ['case_type' => 'feature_request', 'expected' => 'Story'],
            ['case_type' => 'question', 'expected' => 'Task'],
            ['case_type' => 'complaint', 'expected' => 'Task'],
            ['case_type' => 'unknown_type', 'expected' => 'Task'], // Default fallback
        ];

        foreach ($testCases as $testCase) {
            $case = ServiceCase::create([
                'company_id' => $this->company->id,
                'category_id' => $this->category->id,
                'subject' => "Type: {$testCase['case_type']}",
                'description' => 'Testing case type mapping',
                'case_type' => $testCase['case_type'],
                'priority' => 'medium',
            ]);

            $this->handler->deliver($case);

            Http::assertSent(function ($request) use ($testCase) {
                $body = json_decode($request->body(), true);
                return isset($body['fields']['issuetype']['name']) &&
                       $body['fields']['issuetype']['name'] === $testCase['expected'];
            });
        }
    }

    /** @test */
    public function test_priority_mapping(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response(['id' => '12345'], 201),
        ]);

        $testCases = [
            ['priority' => 'critical', 'expected' => 'Highest'],
            ['priority' => 'high', 'expected' => 'High'],
            ['priority' => 'medium', 'expected' => 'Medium'],
            ['priority' => 'low', 'expected' => 'Low'],
            ['priority' => 'unknown_priority', 'expected' => 'Medium'], // Default fallback
        ];

        foreach ($testCases as $testCase) {
            $case = ServiceCase::create([
                'company_id' => $this->company->id,
                'category_id' => $this->category->id,
                'subject' => "Priority: {$testCase['priority']}",
                'description' => 'Testing priority mapping',
                'case_type' => 'incident',
                'priority' => $testCase['priority'],
            ]);

            $this->handler->deliver($case);

            Http::assertSent(function ($request) use ($testCase) {
                $body = json_decode($request->body(), true);
                return isset($body['fields']['priority']['name']) &&
                       $body['fields']['priority']['name'] === $testCase['expected'];
            });
        }
    }

    /** @test */
    public function test_webhook_test_connection_success(): void
    {
        Http::fake([
            'jira.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $result = $this->handler->test($this->case);

        $this->assertIsArray($result);
        $this->assertTrue($result['can_deliver'] ?? false);
        $this->assertEquals('ready', $result['status'] ?? '');
    }

    /** @test */
    public function test_webhook_test_without_config(): void
    {
        // Create category without output config
        $categoryWithoutConfig = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'No Output Config',
            'slug' => 'no-output-config',
            'output_configuration_id' => null,
            'is_active' => true,
        ]);

        // Create case with category but no output config
        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $categoryWithoutConfig->id,
            'subject' => 'No Config Case',
            'description' => 'Testing without configuration',
            'case_type' => 'incident',
            'priority' => 'normal',
        ]);

        $result = $this->handler->test($case);

        $this->assertIsArray($result);
        $this->assertFalse($result['can_deliver'] ?? true);
        $this->assertNotEmpty($result['issues']);
    }

    /** @test */
    public function test_webhook_validates_required_configuration(): void
    {
        // Create config without webhook_url
        $invalidConfig = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Invalid Config',
            'output_type' => 'webhook',
            'webhook_url' => null,
            'is_active' => true,
        ]);

        $result = $this->handler->validate($invalidConfig);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_webhook_validates_url_format(): void
    {
        // Create config with invalid URL
        $invalidConfig = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Invalid URL',
            'output_type' => 'webhook',
            'webhook_url' => 'not-a-valid-url',
            'is_active' => true,
        ]);

        $result = $this->handler->validate($invalidConfig);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_webhook_accepts_valid_https_url(): void
    {
        $result = $this->handler->validate($this->config);

        $this->assertTrue($result);
    }
}
