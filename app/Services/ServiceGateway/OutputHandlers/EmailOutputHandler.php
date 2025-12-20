<?php

namespace App\Services\ServiceGateway\OutputHandlers;

use App\Mail\ServiceCaseNotification;
use App\Mail\VisionaryDataBackupMail;
use App\Models\ServiceCase;
use App\Models\ServiceOutputConfiguration;
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
 *
 * @package App\Services\ServiceGateway\OutputHandlers
 */
class EmailOutputHandler implements OutputHandlerInterface
{
    /**
     * Deliver service case notification via email.
     *
     * Sends emails to all configured recipients using Laravel's
     * queue system for async delivery. Falls back to fallback_emails
     * if primary recipients are not configured.
     *
     * @param \App\Models\ServiceCase $case Service case to notify about
     * @return bool True if emails were queued successfully
     */
    public function deliver(ServiceCase $case): bool
    {
        Log::info('[EmailOutputHandler] Starting email delivery', [
            'case_id' => $case->id,
            'case_type' => $case->case_type,
            'priority' => $case->priority,
        ]);

        // Load configuration relationship
        if (!$case->relationLoaded('category')) {
            $case->load('category.outputConfiguration');
        } else if (!$case->category->relationLoaded('outputConfiguration')) {
            $case->category->load('outputConfiguration');
        }

        $config = $case->category->outputConfiguration ?? null;

        // Validate email configuration
        if (!$config || !$this->supportsEmail($config)) {
            Log::warning('[EmailOutputHandler] No email config for case', [
                'case_id' => $case->id,
                'category_id' => $case->category_id,
                'has_config' => !is_null($config),
            ]);
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
            return false;
        }

        // Queue emails for delivery
        try {
            $queued = 0;

            foreach ($recipients as $email) {
                if (!$this->isValidEmail($email)) {
                    Log::warning('[EmailOutputHandler] Invalid email address', [
                        'case_id' => $case->id,
                        'email' => $email,
                    ]);
                    continue;
                }

                // Use VisionaryDataBackupMail for Visionary Data configs
                $mailable = $this->isVisionaryDataConfig($config)
                    ? new VisionaryDataBackupMail($case)
                    : new ServiceCaseNotification($case, 'internal');

                Mail::to($email)->queue($mailable);
                $queued++;

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
                return false;
            }

            Log::info('[EmailOutputHandler] Emails queued successfully', [
                'case_id' => $case->id,
                'recipients' => $queued,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[EmailOutputHandler] Failed to queue emails', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Test email delivery configuration without sending.
     *
     * Validates configuration and returns diagnostic information
     * about recipient setup and email readiness.
     *
     * @param \App\Models\ServiceCase $case Service case to test
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
        if (!$case->relationLoaded('category')) {
            $case->load('category.outputConfiguration');
        }

        $config = $case->category->outputConfiguration ?? null;

        if (!$config) {
            $results['issues'][] = 'No output configuration found';
            return $results;
        }

        if (!$this->supportsEmail($config)) {
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

        Log::info('[EmailOutputHandler] Configuration test passed', [
            'case_id' => $case->id,
            'valid_recipients' => count($validRecipients),
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
     * @param mixed $config Output configuration model
     * @return bool True if email is supported
     */
    private function supportsEmail($config): bool
    {
        if (!$config) {
            return false;
        }

        return method_exists($config, 'sendsEmail') && $config->sendsEmail();
    }

    /**
     * Get recipient list from configuration.
     *
     * Returns primary recipients if configured, otherwise
     * falls back to fallback_emails.
     *
     * @param mixed $config Output configuration model
     * @return array List of email addresses
     */
    private function getRecipients($config): array
    {
        if (!$config) {
            return [];
        }

        // Try primary recipients first
        $recipients = $config->email_recipients ?? [];

        // Fall back to fallback emails if no primary recipients
        if (empty($recipients)) {
            $recipients = $config->fallback_emails ?? [];

            if (!empty($recipients)) {
                Log::info('[EmailOutputHandler] Using fallback recipients', [
                    'config_id' => $config->id,
                    'count' => count($recipients),
                ]);
            }
        }

        // Ensure recipients is an array
        if (!is_array($recipients)) {
            return [];
        }

        return $recipients;
    }

    /**
     * Validate email address format.
     *
     * @param string $email Email address to validate
     * @return bool True if valid email format
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if configuration is for Visionary Data backup emails.
     *
     * Visionary Data configurations require the full backup email
     * with transcript and JSON data block instead of the standard
     * internal notification.
     *
     * Detection: Configuration name contains "visionary" (case-insensitive)
     *
     * @param ServiceOutputConfiguration|null $config Output configuration
     * @return bool True if this is a Visionary Data config
     */
    private function isVisionaryDataConfig($config): bool
    {
        if (!$config instanceof ServiceOutputConfiguration) {
            return false;
        }

        $name = strtolower($config->name ?? '');

        return str_contains($name, 'visionary');
    }
}
