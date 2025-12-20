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

        Http::preventStrayRequests(); // Prevent real HTTP calls
        Mail::fake(); // Prevent real email sending

        $this->handler = new HybridOutputHandler();
        $this->company = Company::factory()->create();

        $this->config = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Hybrid Integration',
            'output_type' => 'hybrid',
            'webhook_url' => 'https://jira.example.com/rest/api/2/issue',
            'webhook_secret' => 'test-secret-key',
            'email_to' => 'support@example.com',
            'email_cc' => 'manager@example.com',
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

    /** @test */
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

        // Verify email was sent
        Mail::assertSent(\App\Mail\ServiceCaseNotification::class, function ($mail) use ($case) {
            return $mail->hasTo('support@example.com') &&
                   $mail->hasCc('manager@example.com');
        });

        // Verify external reference stored from webhook
        $case->refresh();
        $this->assertNotNull($case->external_reference);
    }

    /** @test */
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

        // Verify email was sent
        Mail::assertSent(\App\Mail\ServiceCaseNotification::class);

        // Verify no external reference stored (webhook failed)
        $case->refresh();
        $this->assertNull($case->external_reference);
    }

    /** @test */
    public function test_hybrid_webhook_only_succeeds(): void
    {
        // Configure invalid email to cause failure
        $this->config->update([
            'email_to' => null, // Invalid config
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

        // Verify email was NOT sent due to invalid config
        Mail::assertNothingSent();

        // Verify external reference stored from webhook
        $case->refresh();
        $this->assertNotNull($case->external_reference);
    }

    /** @test */
    public function test_hybrid_both_fail(): void
    {
        // Webhook fails
        Http::fake([
            'jira.example.com/*' => Http::response([
                'error' => 'Internal Server Error',
            ], 500),
        ]);

        // Email fails (invalid config)
        $this->config->update([
            'email_to' => null,
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

        // Verify no email was sent
        Mail::assertNothingSent();

        // Verify no external reference stored
        $case->refresh();
        $this->assertNull($case->external_reference);
    }

    /** @test */
    public function test_hybrid_test_returns_both_results(): void
    {
        // Webhook succeeds
        Http::fake([
            'jira.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $result = $this->handler->test($this->config);

        // Test should return true if at least one handler succeeds
        $this->assertTrue($result);

        // Verify webhook test was called
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'jira.example.com');
        });

        // Verify email test was called
        Mail::assertSent(\App\Mail\ServiceCaseNotification::class);
    }

    /** @test */
    public function test_hybrid_test_fails_when_both_handlers_fail(): void
    {
        // Webhook fails
        Http::fake([
            'jira.example.com/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        // Email fails (invalid config)
        $this->config->update([
            'email_to' => null,
        ]);

        $result = $this->handler->test($this->config);

        // Both failed, test should return false
        $this->assertFalse($result);
    }

    /** @test */
    public function test_hybrid_validates_at_least_one_handler_configured(): void
    {
        // Create config with both handlers missing required fields
        $invalidConfig = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Invalid Hybrid',
            'output_type' => 'hybrid',
            'webhook_url' => null, // Missing webhook
            'email_to' => null,    // Missing email
            'is_active' => true,
        ]);

        $result = $this->handler->validate($invalidConfig);

        // Should fail validation if neither handler is properly configured
        $this->assertFalse($result);
    }

    /** @test */
    public function test_hybrid_validates_with_only_webhook_configured(): void
    {
        // Create config with only webhook configured
        $webhookOnlyConfig = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Webhook Only Hybrid',
            'output_type' => 'hybrid',
            'webhook_url' => 'https://jira.example.com/rest/api/2/issue',
            'email_to' => null, // No email configured
            'is_active' => true,
        ]);

        $result = $this->handler->validate($webhookOnlyConfig);

        // Should pass validation with at least one handler configured
        $this->assertTrue($result);
    }

    /** @test */
    public function test_hybrid_validates_with_only_email_configured(): void
    {
        // Create config with only email configured
        $emailOnlyConfig = ServiceOutputConfiguration::create([
            'company_id' => $this->company->id,
            'name' => 'Email Only Hybrid',
            'output_type' => 'hybrid',
            'webhook_url' => null, // No webhook configured
            'email_to' => 'support@example.com',
            'is_active' => true,
        ]);

        $result = $this->handler->validate($emailOnlyConfig);

        // Should pass validation with at least one handler configured
        $this->assertTrue($result);
    }

    /** @test */
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
            $body = json_decode($request->body(), true);
            return $body['fields']['summary'] === 'Independent Delivery Test';
        });

        // Verify email received the case
        Mail::assertSent(\App\Mail\ServiceCaseNotification::class, function ($mail) {
            return $mail->serviceCase->subject === 'Independent Delivery Test';
        });
    }
}
