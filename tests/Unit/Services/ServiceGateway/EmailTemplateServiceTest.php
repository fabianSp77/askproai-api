<?php

namespace Tests\Unit\Services\ServiceGateway;

use App\Models\EmailTemplate;
use App\Services\ServiceGateway\EmailTemplateService;
use Tests\TestCase;

class EmailTemplateServiceTest extends TestCase
{
    private EmailTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailTemplateService;
    }

    public function test_render_replaces_variables_with_data(): void
    {
        $template = new EmailTemplate([
            'subject' => 'Hello {{customer_name}}',
            'body_html' => '<p>Dear {{customer_name}}, your case {{case_number}} is {{case_status}}.</p>',
        ]);

        $data = [
            'customer_name' => 'John Doe',
            'case_number' => 'CASE-123',
            'case_status' => 'open',
        ];

        $result = $this->service->render($template, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('body', $result);
        $this->assertEquals('Hello John Doe', $result['subject']);
        $this->assertEquals('<p>Dear John Doe, your case CASE-123 is open.</p>', $result['body']);
    }

    public function test_render_handles_missing_variables_gracefully(): void
    {
        $template = new EmailTemplate([
            'subject' => 'Hello {{customer_name}}',
            'body_html' => '<p>Case: {{case_number}}, Status: {{missing_variable}}</p>',
        ]);

        $data = [
            'customer_name' => 'Jane Smith',
            'case_number' => 'CASE-456',
        ];

        $result = $this->service->render($template, $data);

        $this->assertEquals('Hello Jane Smith', $result['subject']);
        $this->assertEquals('<p>Case: CASE-456, Status: </p>', $result['body']);
    }

    public function test_render_handles_no_variables(): void
    {
        $template = new EmailTemplate([
            'subject' => 'Static Subject',
            'body_html' => '<p>This is a static email with no variables.</p>',
        ]);

        $data = [
            'customer_name' => 'Test User',
        ];

        $result = $this->service->render($template, $data);

        $this->assertEquals('Static Subject', $result['subject']);
        $this->assertEquals('<p>This is a static email with no variables.</p>', $result['body']);
    }

    public function test_render_handles_empty_data(): void
    {
        $template = new EmailTemplate([
            'subject' => 'Hello {{customer_name}}',
            'body_html' => '<p>Email for {{customer_email}}</p>',
        ]);

        $data = [];

        $result = $this->service->render($template, $data);

        $this->assertEquals('Hello ', $result['subject']);
        $this->assertEquals('<p>Email for </p>', $result['body']);
    }

    public function test_render_handles_multiple_occurrences_of_same_variable(): void
    {
        $template = new EmailTemplate([
            'subject' => '{{company_name}} - Update',
            'body_html' => '<p>Hello from {{company_name}}. {{company_name}} appreciates your business.</p>',
        ]);

        $data = [
            'company_name' => 'AskPro',
        ];

        $result = $this->service->render($template, $data);

        $this->assertEquals('AskPro - Update', $result['subject']);
        $this->assertEquals('<p>Hello from AskPro. AskPro appreciates your business.</p>', $result['body']);
    }
}
