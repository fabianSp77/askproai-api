<?php

namespace Tests\Unit\Mail\Transport;

use Tests\TestCase;
use App\Mail\Transport\ResendTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResendTransportTest extends TestCase
{
    private ResendTransport $transport;
    private string $apiKey = 'test_api_key';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transport = new ResendTransport($this->apiKey);
    }

    /** @test */
    public function it_sends_simple_email_via_resend_api()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response([
                'id' => 'email_123',
                'from' => 'sender@example.com',
                'to' => ['recipient@example.com'],
                'created_at' => now()->toIso8601String(),
            ], 200),
        ]);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test Email')
            ->text('This is a test email')
            ->html('<p>This is a test email</p>');

        // Act
        $this->transport->send($email);

        // Assert
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.resend.com/emails' &&
                   $request->hasHeader('Authorization', 'Bearer ' . $this->apiKey) &&
                   $request['from'] === 'sender@example.com' &&
                   $request['to'] === ['recipient@example.com'] &&
                   $request['subject'] === 'Test Email' &&
                   $request['text'] === 'This is a test email' &&
                   $request['html'] === '<p>This is a test email</p>';
        });
    }

    /** @test */
    public function it_handles_multiple_recipients()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email_123'], 200),
        ]);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient1@example.com', 'recipient2@example.com')
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->subject('Multi-recipient Email');

        // Act
        $this->transport->send($email);

        // Assert
        Http::assertSent(function ($request) {
            return $request['to'] === ['recipient1@example.com', 'recipient2@example.com'] &&
                   $request['cc'] === ['cc@example.com'] &&
                   $request['bcc'] === ['bcc@example.com'];
        });
    }

    /** @test */
    public function it_handles_attachments()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email_123'], 200),
        ]);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Email with Attachment')
            ->attach('Test content', 'test.txt', 'text/plain');

        // Act
        $this->transport->send($email);

        // Assert
        Http::assertSent(function ($request) {
            return isset($request['attachments']) &&
                   count($request['attachments']) === 1 &&
                   $request['attachments'][0]['filename'] === 'test.txt' &&
                   $request['attachments'][0]['content'] === base64_encode('Test content');
        });
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response([
                'error' => 'Invalid API key',
            ], 401),
        ]);

        Log::shouldReceive('error')->once();

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test Email');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to send email via Resend');
        
        $this->transport->send($email);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response([
                'error' => 'Rate limit exceeded',
            ], 429),
        ]);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test Email');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to send email via Resend: 429');
        
        $this->transport->send($email);
    }

    /** @test */
    public function it_includes_reply_to_header()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email_123'], 200),
        ]);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->replyTo('replyto@example.com')
            ->subject('Test Email');

        // Act
        $this->transport->send($email);

        // Assert
        Http::assertSent(function ($request) {
            return $request['reply_to'] === ['replyto@example.com'];
        });
    }

    /** @test */
    public function it_handles_custom_headers()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email_123'], 200),
        ]);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test Email');
        
        $email->getHeaders()
            ->addTextHeader('X-Custom-Header', 'custom-value')
            ->addTextHeader('X-Campaign-ID', '12345');

        // Act
        $this->transport->send($email);

        // Assert
        Http::assertSent(function ($request) {
            return isset($request['headers']) &&
                   $request['headers']['X-Custom-Header'] === 'custom-value' &&
                   $request['headers']['X-Campaign-ID'] === '12345';
        });
    }

    /** @test */
    public function it_strips_html_for_text_content_if_not_provided()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email_123'], 200),
        ]);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test Email')
            ->html('<p>This is <strong>HTML</strong> content</p>');

        // Act
        $this->transport->send($email);

        // Assert
        Http::assertSent(function ($request) {
            return $request['html'] === '<p>This is <strong>HTML</strong> content</p>' &&
                   $request['text'] === 'This is HTML content';
        });
    }

    /** @test */
    public function it_handles_large_attachments()
    {
        // Arrange
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email_123'], 200),
        ]);

        // Create a 5MB attachment
        $largeContent = str_repeat('x', 5 * 1024 * 1024);
        
        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Email with Large Attachment')
            ->attach($largeContent, 'large-file.txt', 'text/plain');

        // Act
        $this->transport->send($email);

        // Assert
        Http::assertSent(function ($request) use ($largeContent) {
            return isset($request['attachments']) &&
                   $request['attachments'][0]['content'] === base64_encode($largeContent);
        });
    }

    /** @test */
    public function it_validates_email_addresses()
    {
        // Arrange
        $email = (new Email())
            ->from('invalid-email')
            ->to('recipient@example.com')
            ->subject('Test Email');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid from address');
        
        $this->transport->send($email);
    }
}