<?php

namespace App\Services\ServiceGateway\OutputHandlers;

use App\Mail\BackupNotificationMail;
use App\Mail\CustomTemplateEmail;
use App\Mail\ServiceCaseNotification;
use App\Models\ServiceCase;
use App\Models\ServiceCaseActivityLog;
use App\Models\ServiceOutputConfiguration;
use App\Services\ServiceGateway\EmailTemplateService;
use App\Services\ServiceGateway\ExchangeLogService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * EmailOutputHandler
 *
 * Handler for delivering service case notifications via email.
 * Supports multiple recipients, fallback addresses, and retry logic.
 * Implements OutputHandlerInterface for service gateway integration.
 *
 * Flow:
 * 1. Validate email configuration exists
 * 2. Determine recipient list (primary + fallbacks)
 * 3. Queue emails for delivery
 * 4. Update case output status
 */
class EmailOutputHandler implements OutputHandlerInterface
{
    /**
     * Exchange log service for audit logging
     */
    private ExchangeLogService $exchangeLogService;

    /**
     * Email template service for rendering custom templates
     */
    private EmailTemplateService $emailTemplateService;

    public function __construct(?ExchangeLogService $exchangeLogService = null, ?EmailTemplateService $emailTemplateService = null)
    {
        $this->exchangeLogService = $exchangeLogService ?? app(ExchangeLogService::class);
        $this->emailTemplateService = $emailTemplateService ?? app(EmailTemplateService::class);
    }

    /**
     * Deliver service case notification via email.
     *
     * Sends emails to all configured recipients using Laravel's
     * queue system for async delivery. Falls back to fallback_emails
     * if primary recipients are not configured.
     *
     * @param  \App\Models\ServiceCase  $case  Service case to notify about
     * @return bool True if emails were queued successfully
     */
    public function deliver(ServiceCase $case): bool
    {
        $startTime = microtime(true);

        Log::info('[EmailOutputHandler] Starting email delivery', [
            'case_id' => $case->id,
            'case_type' => $case->case_type,
            'priority' => $case->priority,
        ]);

        // Load configuration relationship (with null-safety for cases without category)
        if (! $case->relationLoaded('category')) {
            $case->load('category.outputConfiguration');
        } elseif ($case->category !== null && ! $case->category->relationLoaded('outputConfiguration')) {
            $case->category->load('outputConfiguration');
        }

        $config = $case->category?->outputConfiguration ?? null;

        // Validate email configuration
        if (! $config || ! $this->supportsEmail($config)) {
            Log::warning('[EmailOutputHandler] No email config for case', [
                'case_id' => $case->id,
                'category_id' => $case->category_id,
                'has_config' => ! is_null($config),
            ]);

            // Log configuration failure to exchange log
            $this->logToExchange(
                case: $case,
                config: $config,
                statusCode: 422,
                recipients: [],
                startTime: $startTime,
                errorClass: 'ConfigurationError',
                errorMessage: $config ? 'Email output type not enabled' : 'No output configuration found'
            );

            return false;
        }

        // Get recipient list
        $recipients = $this->getRecipients($config);

        if (empty($recipients)) {
            Log::error('[EmailOutputHandler] No recipients configured', [
                'case_id' => $case->id,
                'category_id' => $case->category_id,
                'config_id' => $config->id,
            ]);

            // Log no recipients failure
            $this->logToExchange(
                case: $case,
                config: $config,
                statusCode: 422,
                recipients: [],
                startTime: $startTime,
                errorClass: 'ConfigurationError',
                errorMessage: 'No email recipients configured'
            );

            return false;
        }

        // Queue emails for delivery
        try {
            $queued = 0;
            $queuedRecipients = [];

            foreach ($recipients as $email) {
                if (! $this->isValidEmail($email)) {
                    Log::warning('[EmailOutputHandler] Invalid email address', [
                        'case_id' => $case->id,
                        'email' => $email,
                    ]);

                    continue;
                }

                // Select appropriate mailable based on configuration
                $mailable = $this->selectMailable($case, $config);

                Mail::to($email)->queue($mailable);
                $queued++;
                $queuedRecipients[] = $email;

                Log::debug('[EmailOutputHandler] Email queued', [
                    'case_id' => $case->id,
                    'recipient' => $email,
                    'mailable' => get_class($mailable),
                ]);
            }

            if ($queued === 0) {
                Log::error('[EmailOutputHandler] No valid recipients', [
                    'case_id' => $case->id,
                    'attempted' => count($recipients),
                ]);

                // Log invalid recipients failure
                $this->logToExchange(
                    case: $case,
                    config: $config,
                    statusCode: 422,
                    recipients: $recipients,
                    startTime: $startTime,
                    errorClass: 'ValidationError',
                    errorMessage: 'No valid email addresses in recipient list'
                );

                return false;
            }

            Log::info('[EmailOutputHandler] Emails queued successfully', [
                'case_id' => $case->id,
                'recipients' => $queued,
            ]);

            // Log successful email queuing to exchange log
            $this->logToExchange(
                case: $case,
                config: $config,
                statusCode: 200,
                recipients: $queuedRecipients,
                startTime: $startTime
            );

            // Log to activity trail
            ServiceCaseActivityLog::logAction(
                case: $case,
                action: ServiceCaseActivityLog::ACTION_EMAIL_SENT,
                user: null, // System action
                oldValues: null,
                newValues: [
                    'recipients' => $this->maskRecipients($queuedRecipients),
                    'count' => $queued,
                    'mailable' => get_class($this->selectMailable($case, $config)),
                ],
                reason: "E-Mail-Benachrichtigung an {$queued} Empfänger gesendet"
            );

            return true;

        } catch (\Exception $e) {
            Log::error('[EmailOutputHandler] Failed to queue emails', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log exception to exchange log
            $this->logToExchange(
                case: $case,
                config: $config,
                statusCode: 500,
                recipients: $recipients,
                startTime: $startTime,
                errorClass: get_class($e),
                errorMessage: $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Test email delivery configuration without sending.
     *
     * Validates configuration and returns diagnostic information
     * about recipient setup and email readiness.
     *
     * @param  \App\Models\ServiceCase  $case  Service case to test
     * @return array Test results with configuration status
     */
    public function test(ServiceCase $case): array
    {
        Log::info('[EmailOutputHandler] Testing email configuration', [
            'case_id' => $case->id,
        ]);

        $results = [
            'handler' => 'email',
            'case_id' => $case->id,
            'status' => 'failed',
            'can_deliver' => false,
            'issues' => [],
            'recipients' => [],
        ];

        // Load configuration
        if (! $case->relationLoaded('category')) {
            $case->load('category.outputConfiguration');
        }

        $config = $case->category->outputConfiguration ?? null;

        if (! $config) {
            $results['issues'][] = 'No output configuration found';

            return $results;
        }

        if (! $this->supportsEmail($config)) {
            $results['issues'][] = 'Configuration does not support email output';

            return $results;
        }

        // Check recipients
        $recipients = $this->getRecipients($config);

        if (empty($recipients)) {
            $results['issues'][] = 'No recipients configured';

            return $results;
        }

        // Validate recipient emails
        $validRecipients = [];
        $invalidRecipients = [];

        foreach ($recipients as $email) {
            if ($this->isValidEmail($email)) {
                $validRecipients[] = $email;
            } else {
                $invalidRecipients[] = $email;
            }
        }

        $results['recipients'] = [
            'total' => count($recipients),
            'valid' => $validRecipients,
            'invalid' => $invalidRecipients,
        ];

        if (empty($validRecipients)) {
            $results['issues'][] = 'No valid email addresses found';

            return $results;
        }

        // Configuration is valid
        $results['status'] = 'ready';
        $results['can_deliver'] = true;

        $templateType = $config->email_template_type ?? 'standard';
        $results['config'] = [
            'id' => $config->id,
            'name' => $config->name,
            'email_template_type' => $templateType,
            'has_custom_template' => $this->hasCustomEmailTemplate($config),
            'has_custom_subject' => ! empty($config->email_subject_template),
            'mailable_class' => $this->getMailableClassName($config),
        ];

        // Features based on template type
        if (in_array($templateType, ['technical', 'admin'])) {
            $results['features'] = [
                'json_attachment' => true,
                'sanitized_data' => true,
                'no_provider_refs' => true,
            ];
        }

        Log::info('[EmailOutputHandler] Configuration test passed', [
            'case_id' => $case->id,
            'valid_recipients' => count($validRecipients),
            'template_type' => $templateType,
        ]);

        return $results;
    }

    /**
     * Get the output handler type identifier.
     *
     * @return string Handler type
     */
    public function getType(): string
    {
        return 'email';
    }

    /**
     * Check if configuration supports email output.
     *
     * @param  mixed  $config  Output configuration model
     * @return bool True if email is supported
     */
    private function supportsEmail($config): bool
    {
        if (! $config) {
            return false;
        }

        return method_exists($config, 'sendsEmail') && $config->sendsEmail();
    }

    /**
     * Get recipient list from configuration.
     *
     * Returns active (non-muted) primary recipients if configured,
     * otherwise falls back to fallback_emails (also respecting muted state).
     *
     * @param  mixed  $config  Output configuration model
     * @return array List of active email addresses
     */
    private function getRecipients($config): array
    {
        if (! $config) {
            return [];
        }

        // Get active recipients (excluding muted)
        $recipients = method_exists($config, 'getActiveRecipients')
            ? $config->getActiveRecipients()
            : ($config->email_recipients ?? []);

        // Log muted recipients for visibility
        $mutedCount = method_exists($config, 'getMutedCount')
            ? $config->getMutedCount()
            : 0;

        if ($mutedCount > 0) {
            Log::info('[EmailOutputHandler] Muted recipients skipped', [
                'config_id' => $config->id,
                'muted_count' => $mutedCount,
                'active_count' => count($recipients),
            ]);
        }

        // Fall back to fallback emails if no active primary recipients
        if (empty($recipients)) {
            $fallback = $config->fallback_emails ?? [];
            $muted = $config->muted_recipients ?? [];

            // Also apply muting to fallback emails
            $recipients = array_values(array_diff($fallback, $muted));

            if (! empty($recipients)) {
                Log::info('[EmailOutputHandler] Using fallback recipients (after muting)', [
                    'config_id' => $config->id,
                    'count' => count($recipients),
                    'muted_from_fallback' => count($fallback) - count($recipients),
                ]);
            }
        }

        // Ensure recipients is an array
        if (! is_array($recipients)) {
            return [];
        }

        return $recipients;
    }

    /**
     * Validate email address format.
     *
     * @param  string  $email  Email address to validate
     * @return bool True if valid email format
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Select appropriate mailable based on configuration.
     *
     * Checks for custom template in this order:
     * 1. template_id (EmailTemplate model) - uses EmailTemplateService
     * 2. email_template_type ('technical', 'admin', 'custom')
     * 3. email_body_template (legacy custom template field)
     * 4. Standard ServiceCaseNotification (default fallback)
     *
     * @param  ServiceCase  $case  Service case to notify about
     * @param  ServiceOutputConfiguration  $config  Output configuration
     * @return Mailable Selected mailable instance
     */
    private function selectMailable(ServiceCase $case, ServiceOutputConfiguration $config): Mailable
    {
        // Priority 1: Check for EmailTemplate model via template_id
        if ($config->template_id && $config->relationLoaded('emailTemplate') === false) {
            $config->load('emailTemplate');
        }

        if ($config->template_id && $config->emailTemplate) {
            Log::info('[EmailOutputHandler] Using EmailTemplate from database', [
                'case_id' => $case->id,
                'config_id' => $config->id,
                'template_id' => $config->template_id,
                'template_name' => $config->emailTemplate->name,
            ]);

            return $this->buildMailableFromTemplate($case, $config);
        }

        $templateType = $config->email_template_type ?? 'standard';

        if ($templateType !== 'standard') {
            return $this->selectMailableByType($case, $config, $templateType);
        }

        // Custom template via email_body_template field (for 'standard' type with custom template)
        if ($this->hasCustomEmailTemplate($config)) {
            Log::debug('[EmailOutputHandler] Using CustomTemplateEmail', [
                'case_id' => $case->id,
                'config_id' => $config->id,
                'template_length' => strlen($config->email_body_template ?? ''),
            ]);

            return new CustomTemplateEmail($case, $config);
        }

        // Default: use standard notification
        Log::debug('[EmailOutputHandler] Using ServiceCaseNotification (default fallback)', [
            'case_id' => $case->id,
            'template_type' => $templateType,
        ]);

        return new ServiceCaseNotification($case, 'internal');
    }

    /**
     * Build mailable from EmailTemplate using EmailTemplateService.
     *
     * Renders the template with ServiceCase data and returns a mailable
     * that can be sent via Laravel's mail system.
     *
     * @param  ServiceCase  $case  Service case to notify about
     * @param  ServiceOutputConfiguration  $config  Output configuration with emailTemplate relationship
     * @return Mailable Mailable instance with rendered content
     */
    private function buildMailableFromTemplate(ServiceCase $case, ServiceOutputConfiguration $config): Mailable
    {
        $template = $config->emailTemplate;

        // Prepare template variables from ServiceCase
        $data = [
            'customer_name' => $case->customer?->name ?? '',
            'customer_email' => $case->customer?->email ?? '',
            'company_name' => $case->company?->name ?? '',
            'case_number' => $case->case_number ?? '',
            'case_subject' => $case->subject ?? '',
            'case_description' => $case->description ?? '',
            'case_status' => $case->status ?? '',
            'case_priority' => $case->priority ?? '',
            'created_at' => $case->created_at?->format('d.m.Y H:i') ?? '',
        ];

        // Render template using EmailTemplateService
        $rendered = $this->emailTemplateService->render($template, $data);

        // Create a simple mailable with rendered content
        return new class($rendered['subject'], $rendered['body']) extends Mailable
        {
            public function __construct(
                private string $emailSubject,
                private string $emailBody
            ) {
                $this->subject = $emailSubject;
            }

            public function build()
            {
                return $this->html($this->emailBody);
            }
        };
    }

    /**
     * Select mailable based on explicit email_template_type.
     *
     * @param  ServiceCase  $case  Service case to notify about
     * @param  ServiceOutputConfiguration  $config  Output configuration
     * @param  string  $templateType  The email_template_type value
     * @return Mailable Selected mailable instance
     */
    private function selectMailableByType(ServiceCase $case, ServiceOutputConfiguration $config, string $templateType): Mailable
    {
        Log::debug('[EmailOutputHandler] Selecting mailable by template type', [
            'case_id' => $case->id,
            'config_id' => $config->id,
            'template_type' => $templateType,
        ]);

        return match ($templateType) {
            'technical' => new BackupNotificationMail($case, $config),
            'admin' => new BackupNotificationMail($case, $config),
            'custom' => new CustomTemplateEmail($case, $config),
            default => new ServiceCaseNotification($case, 'internal'),
        };
    }

    /**
     * Check if configuration has a custom email template.
     *
     * @param  ServiceOutputConfiguration|null  $config  Output configuration
     * @return bool True if custom template is configured
     */
    private function hasCustomEmailTemplate($config): bool
    {
        if (! $config instanceof ServiceOutputConfiguration) {
            return false;
        }

        return ! empty($config->email_body_template);
    }

    /**
     * Get the class name of the mailable that would be used.
     *
     * @param  ServiceOutputConfiguration|null  $config  Output configuration
     * @return string Mailable class name (short) with mode info
     */
    private function getMailableClassName($config): string
    {
        if (! $config instanceof ServiceOutputConfiguration) {
            return 'ServiceCaseNotification';
        }

        $templateType = $config->email_template_type ?? 'standard';

        return match ($templateType) {
            'technical' => 'BackupNotificationMail (MODE_TECHNICAL)',
            'admin' => 'BackupNotificationMail (MODE_ADMINISTRATIVE)',
            'custom' => 'CustomTemplateEmail',
            default => $this->hasCustomEmailTemplate($config)
                ? 'CustomTemplateEmail'
                : 'ServiceCaseNotification',
        };
    }

    /**
     * Log email delivery attempt to ExchangeLog for audit trail.
     *
     * Creates a record in service_gateway_exchange_logs visible in Filament.
     * Uses 'mailto:' prefix for endpoint to distinguish from HTTP endpoints.
     *
     * @param  ServiceCase  $case  The service case being delivered
     * @param  ServiceOutputConfiguration|null  $config  Output configuration
     * @param  int  $statusCode  HTTP-style status code (200=success, 422=config error, 500=exception)
     * @param  array  $recipients  List of email recipients
     * @param  float  $startTime  Microtime when delivery started
     * @param  string|null  $errorClass  Exception class name if failed
     * @param  string|null  $errorMessage  Error message if failed
     */
    private function logToExchange(
        ServiceCase $case,
        ?ServiceOutputConfiguration $config,
        int $statusCode,
        array $recipients,
        float $startTime,
        ?string $errorClass = null,
        ?string $errorMessage = null
    ): void {
        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        // Build request body for audit log
        $requestBody = [
            'handler' => 'email',
            'mailable' => $config ? $this->getMailableClassName($config) : null,
            'recipients' => $this->maskRecipients($recipients),
            'recipient_count' => count($recipients),
        ];

        // Build response body
        $responseBody = $statusCode === 200
            ? ['queued' => true, 'count' => count($recipients)]
            : ['queued' => false, 'error' => $errorMessage];

        // Create mailto: endpoint format for Filament display
        $endpoint = count($recipients) > 0
            ? 'mailto:'.$this->maskRecipients($recipients)[0].(count($recipients) > 1 ? ' (+'.(count($recipients) - 1).')' : '')
            : 'mailto:(no recipients)';

        try {
            $this->exchangeLogService->logOutbound(
                endpoint: $endpoint,
                method: 'MAIL',
                requestBody: $requestBody,
                responseBody: $responseBody,
                statusCode: $statusCode,
                durationMs: $durationMs,
                callId: $case->call_id,
                serviceCaseId: $case->id,
                companyId: $case->company_id,
                correlationId: $case->id.'-email-'.now()->format('His'),
                errorClass: $errorClass,
                errorMessage: $errorMessage,
                outputConfigurationId: $config?->id
            );
        } catch (\Exception $e) {
            // Don't let logging failures break email delivery
            Log::warning('[EmailOutputHandler] Failed to log to exchange', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mask email addresses for privacy in logs.
     *
     * Shows first 3 chars + domain, e.g., "adm***@example.com"
     *
     * @param  array  $emails  List of email addresses
     * @return array Masked email addresses
     */
    private function maskRecipients(array $emails): array
    {
        return array_map(function ($email) {
            if (! $this->isValidEmail($email)) {
                return '(invalid)';
            }

            $parts = explode('@', $email);
            $local = $parts[0];
            $domain = $parts[1] ?? '';

            $maskedLocal = strlen($local) > 3
                ? substr($local, 0, 3).'***'
                : $local[0].'***';

            return $maskedLocal.'@'.$domain;
        }, $emails);
    }

    /**
     * Send a test email with sample data.
     *
     * Used for previewing email templates and testing configuration.
     * Renders the mailable HTML and sends as raw email to avoid queue serialization
     * issues with non-persisted sample cases.
     *
     * @param  ServiceCase  $sampleCase  Sample service case (from SampleServiceCaseFactory)
     * @param  string  $testEmail  Recipient email address for test
     * @param  ServiceOutputConfiguration|null  $config  Optional config override
     *
     * @throws \Exception If email sending fails
     */
    public function sendTestEmail(ServiceCase $sampleCase, string $testEmail, ?ServiceOutputConfiguration $config = null): void
    {
        // Use provided config or get from case's category
        $config = $config ?? $sampleCase->category?->outputConfiguration;

        if (! $config) {
            throw new \InvalidArgumentException('No output configuration provided or found on sample case');
        }

        if (! $this->isValidEmail($testEmail)) {
            throw new \InvalidArgumentException('Invalid test email address: '.$testEmail);
        }

        Log::info('[EmailOutputHandler] Sending test email', [
            'test_email' => $testEmail,
            'config_id' => $config->id,
            'template_type' => $config->email_template_type ?? 'standard',
        ]);

        // Select appropriate mailable based on configuration
        $mailable = $this->selectMailable($sampleCase, $config);

        // Render the mailable to get HTML content
        // This avoids queue serialization issues with non-persisted sample cases
        $htmlContent = $mailable->render();
        $subject = '[TEST] '.($mailable->subject ?? 'Service Case Notification');

        // Send as raw HTML email (truly synchronous, bypasses ShouldQueue)
        Mail::html($htmlContent, function ($message) use ($testEmail, $subject) {
            $message->to($testEmail)
                ->subject($subject)
                ->from(config('mail.from.address'), config('mail.from.name'));
        });

        Log::info('[EmailOutputHandler] Test email sent successfully', [
            'test_email' => $testEmail,
            'mailable' => get_class($mailable),
        ]);
    }
}
