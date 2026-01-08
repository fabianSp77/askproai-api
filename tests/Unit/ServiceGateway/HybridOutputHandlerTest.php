<?php

namespace Tests\Unit\ServiceGateway;

use Tests\TestCase;
use App\Services\ServiceGateway\OutputHandlers\HybridOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HybridOutputHandlerTest extends TestCase
{
    use RefreshDatabase;

    private HybridOutputHandler $handler;
    private Company $company;
    private ServiceOutputConfiguration $config;
    private ServiceCaseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Mail::fake();

        // Use Laravel DI to properly resolve handler with dependencies
        $this->handler = app(HybridOutputHandler::class);
        $this->company = Company::factory()->create();

        $this->config = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Hybrid Integration',
            'output_type' => 'hybrid',
            'webhook_url' => 'https://jira.example.com/rest/api/2/issue',
            'webhook_secret' => 'test-secret-key',
            'webhook_enabled' => true,
            'email_recipients' => ['support@example.com', 'manager@example.com'],
            'is_active' => true,
        ]);

        $this->category = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'IT Support',
            'slug' => 'it-support',
            'output_configuration_id' => $this->config->id,
            'is_active' => true,
        ]);
    }

    public function test_hybrid_both_succeed(): void
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
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ]);

        $result = $this->handler->deliver($case);

        // Both should succeed, overall result should be true
        $this->assertTrue($result);

        // Verify webhook was sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com');
        });

        // Verify emails were queued (not sent - handler uses queue())
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class);
    }

    public function test_hybrid_email_only_succeeds(): void
    {
        // Webhook fails with 500 error
        Http::fake([
            'jira.example.com/*' => Http::response([
                'error' => 'Internal Server Error',
            ], 500),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Email Only Test',
            'description' => 'Webhook should fail, email should succeed',
            'case_type' => 'incident',
            'priority' => 'medium',
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
        ]);

        $result = $this->handler->deliver($case);

        // Email succeeds, so overall result should be true (partial success)
        $this->assertTrue($result);

        // Verify webhook was attempted
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com');
        });

        // Verify email was queued
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class);
    }

    public function test_hybrid_webhook_only_succeeds(): void
    {
        // Configure empty recipients to cause email failure
        $this->config->update([
            'email_recipients' => [],
        ]);

        Http::fake([
            'jira.example.com/*' => Http::response([
                'id' => '12345',
                'key' => 'ASKPRO-123',
            ], 201),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Webhook Only Test',
            'description' => 'Email should fail, webhook should succeed',
            'case_type' => 'feature_request',
            'priority' => 'low',
        ]);

        $result = $this->handler->deliver($case);

        // Webhook succeeds, so overall result should be true (partial success)
        $this->assertTrue($result);

        // Verify webhook was sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com');
        });

        // Verify no email was queued due to empty recipients
        Mail::assertNothingQueued();
    }

    public function test_hybrid_both_fail(): void
    {
        // Webhook fails
        Http::fake([
            'jira.example.com/*' => Http::response([
                'error' => 'Internal Server Error',
            ], 500),
        ]);

        // Email fails (empty recipients)
        $this->config->update([
            'email_recipients' => [],
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Both Fail Test',
            'description' => 'Both webhook and email should fail',
            'case_type' => 'incident',
            'priority' => 'critical',
        ]);

        $result = $this->handler->deliver($case);

        // Both failed, overall result should be false
        $this->assertFalse($result);

        // Verify webhook was attempted
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com');
        });

        // Verify no email was queued
        Mail::assertNothingQueued();
    }

    public function test_hybrid_test_returns_array_with_both_channel_results(): void
    {
        // Webhook succeeds
        Http::fake([
            'jira.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Test Configuration Check',
            'description' => 'Testing hybrid configuration',
            'case_type' => 'incident',
            'priority' => 'medium',
        ]);

        $result = $this->handler->test($case);

        // test() returns an array, not a bool
        $this->assertIsArray($result);
        $this->assertEquals('hybrid', $result['handler']);
        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('email', $result['channels']);
        $this->assertArrayHasKey('webhook', $result['channels']);
        $this->assertArrayHasKey('can_deliver', $result);
        $this->assertTrue($result['can_deliver']);
    }

    public function test_hybrid_test_fails_when_both_handlers_misconfigured(): void
    {
        // Webhook disabled + empty email recipients
        $this->config->update([
            'webhook_url' => null,
            'webhook_enabled' => false,
            'email_recipients' => [],
        ]);

        $case = ServiceCase::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'subject' => 'Misconfigured Test',
            'description' => 'Neither channel should work',
            'case_type' => 'incident',
            'priority' => 'low',
        ]);

        $result = $this->handler->test($case);

        // Both failed, test should indicate not ready
        $this->assertIsArray($result);
        $this->assertEquals('failed', $result['overall_status']);
        $this->assertFalse($result['can_deliver']);
        $this->assertNotEmpty($result['issues']);
    }

    public function test_hybrid_delivers_to_both_handlers_independently(): void
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
            'subject' => 'Independent Delivery Test',
            'description' => 'Both handlers should receive the case',
            'case_type' => 'complaint',
            'priority' => 'high',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
        ]);

        $result = $this->handler->deliver($case);

        $this->assertTrue($result);

        // Verify webhook received the case
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com');
        });

        // Verify email was queued
        Mail::assertQueued(\App\Mail\ServiceCaseNotification::class);
    }

    public function test_handler_returns_correct_type(): void
    {
        $this->assertEquals('hybrid', $this->handler->getType());
    }
}
