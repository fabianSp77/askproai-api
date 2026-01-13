<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ServiceCase;
use App\Models\ServiceOutputConfiguration;
use App\Services\ServiceGateway\Traits\TemplateRendererTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * CustomTemplateEmail
 *
 * Mailable that renders custom HTML templates from ServiceOutputConfiguration.
 * Uses Mustache-style template syntax: {{variable}} and {{#conditional}}...{{/conditional}}
 *
 * This is used when email_body_template is configured in ServiceOutputConfiguration,
 * allowing per-category custom email designs.
 *
 * Falls back to ServiceCaseNotification when no custom template exists.
 *
 * @package App\Mail
 */
class CustomTemplateEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TemplateRendererTrait;

    /**
     * The service case instance.
     */
    public ServiceCase $case;

    /**
     * The output configuration with templates.
     */
    public ServiceOutputConfiguration $config;

    /**
     * Pre-rendered HTML body.
     */
    private string $renderedHtml;

    /**
     * Pre-rendered subject line.
     */
    private string $renderedSubject;

    /**
     * Create a new message instance.
     *
     * @param ServiceCase $case Service case to notify about
     * @param ServiceOutputConfiguration $config Configuration with email templates
     */
    public function __construct(ServiceCase $case, ServiceOutputConfiguration $config)
    {
        $this->case = $case;
        $this->config = $config;

        // Load relationships if not already loaded
        $this->loadCaseRelationships($case);

        // Pre-render templates (done in constructor for serialization)
        $this->renderedHtml = $this->renderMustacheTemplate(
            $config->email_body_template ?? '',
            $case
        );

        $this->renderedSubject = $this->renderMustacheTemplate(
            $config->email_subject_template ?? '{{ticket_id}}: {{subject}}',
            $case
        );
    }

    /**
     * Load all required relationships for template rendering.
     */
    private function loadCaseRelationships(ServiceCase $case): void
    {
        $relationships = ['category', 'customer', 'company', 'call'];

        foreach ($relationships as $relation) {
            if (!$case->relationLoaded($relation)) {
                $case->load($relation);
            }
        }
    }

    /**
     * Get the message envelope.
     *
     * Uses rendered subject from email_subject_template.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderedSubject,
            from: config('mail.from.address'),
            replyTo: config('mail.from.address'),
        );
    }

    /**
     * Build the message.
     *
     * Uses raw HTML from rendered email_body_template.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->html($this->renderedHtml);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
