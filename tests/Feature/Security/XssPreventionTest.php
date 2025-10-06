<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Policy;
use App\Models\PolicyConfiguration;
use App\Models\NotificationConfiguration;
use App\Models\CallbackRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * XSS Prevention Test Suite
 *
 * Tests XSS sanitization across the application:
 * - Observer-based XSS prevention
 * - Script tag removal
 * - Malicious input sanitization
 * - HTML entity encoding
 */
class XssPreventionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /**
     * @test
     * Test basic script tag removal
     */
    public function it_removes_basic_script_tags(): void
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'description' => '<script>alert("XSS")</script>Safe content',
        ];

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => $maliciousData,
        ]);

        $config->refresh();

        $description = $config->config_data['description'] ?? '';

        $this->assertStringNotContainsString('<script>', strtolower($description));
        $this->assertStringNotContainsString('</script>', strtolower($description));
    }

    /**
     * @test
     * Test inline JavaScript event handler removal
     */
    public function it_removes_inline_event_handlers(): void
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'template' => '<div onclick="alert(\'XSS\')">Click me</div>',
        ];

        $config = NotificationConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'settings' => $maliciousData,
        ]);

        $config->refresh();

        $template = $config->settings['template'] ?? '';

        $this->assertStringNotContainsString('onclick=', strtolower($template));
    }

    /**
     * @test
     * Test JavaScript protocol URL removal
     */
    public function it_removes_javascript_protocol_urls(): void
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'link' => '<a href="javascript:alert(\'XSS\')">Click</a>',
        ];

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => $maliciousData,
        ]);

        $config->refresh();

        $link = $config->config_data['link'] ?? '';

        $this->assertStringNotContainsString('javascript:', strtolower($link));
    }

    /**
     * @test
     * Test encoded script tag handling
     */
    public function it_handles_encoded_script_tags(): void
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'content' => '&lt;script&gt;alert("XSS")&lt;/script&gt;',
        ];

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => $maliciousData,
        ]);

        $config->refresh();

        $content = $config->config_data['content'] ?? '';

        // Should remain encoded or be stripped
        $decodedContent = html_entity_decode($content);
        $this->assertStringNotContainsString('<script>', $decodedContent);
    }

    /**
     * @test
     * Test iframe injection prevention
     */
    public function it_prevents_iframe_injection(): void
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'content' => '<iframe src="https://evil.com"></iframe>Normal content',
        ];

        $config = NotificationConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'settings' => $maliciousData,
        ]);

        $config->refresh();

        $content = $config->settings['content'] ?? '';

        $this->assertStringNotContainsString('<iframe', strtolower($content));
    }

    /**
     * @test
     * Test object and embed tag removal
     */
    public function it_removes_object_and_embed_tags(): void
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'content' => '<object data="malicious.swf"></object><embed src="evil.swf">',
        ];

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => $maliciousData,
        ]);

        $config->refresh();

        $content = $config->config_data['content'] ?? '';

        $this->assertStringNotContainsString('<object', strtolower($content));
        $this->assertStringNotContainsString('<embed', strtolower($content));
    }

    /**
     * @test
     * Test callback request payload sanitization
     */
    public function it_sanitizes_callback_request_payloads(): void
    {
        $this->actingAs($this->user);

        $maliciousPayload = [
            'user_input' => '<script>document.cookie</script>',
            'message' => 'Normal message',
        ];

        $callback = CallbackRequest::factory()->create([
            'company_id' => $this->company->id,
            'payload' => $maliciousPayload,
        ]);

        $callback->refresh();

        $userInput = $callback->payload['user_input'] ?? '';

        $this->assertStringNotContainsString('<script>', strtolower($userInput));
    }

    /**
     * @test
     * Test XSS prevention preserves safe HTML
     */
    public function it_preserves_safe_html_content(): void
    {
        $this->actingAs($this->user);

        $safeData = [
            'content' => '<p>This is <strong>safe</strong> content</p>',
        ];

        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'config_data' => $safeData,
        ]);

        $config->refresh();

        $content = $config->config_data['content'] ?? '';

        // Safe tags should be preserved (depending on sanitization strategy)
        // At minimum, the safe text should remain
        $this->assertStringContainsString('safe', strtolower($content));
        $this->assertStringContainsString('content', strtolower($content));
    }
}
