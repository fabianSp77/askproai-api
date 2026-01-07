<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BackupNotificationMail;
use App\Mail\CustomTemplateEmail;
use App\Mail\ServiceCaseNotification;
use App\Models\ServiceOutputConfiguration;
use App\Services\ServiceGateway\SampleServiceCaseFactory;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller for generating email template previews in the admin panel.
 *
 * Renders email templates with sample data so admins can see
 * how different template types look before saving configuration.
 */
class EmailPreviewController extends Controller
{
    /**
     * Generate email preview for a given template type.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_type' => 'required|in:standard,technical,admin,custom',
            'include_transcript' => 'nullable|boolean',
            'include_summary' => 'nullable|boolean',
            'email_audio_option' => 'nullable|in:none,link,attachment',
            'email_show_admin_link' => 'nullable|boolean',
            'custom_subject' => 'nullable|string|max:500',
            'custom_body' => 'nullable|string|max:50000',
        ]);

        try {
            // Create sample ServiceCase with realistic data
            $sampleCase = SampleServiceCaseFactory::create('incident');

            // Build temporary config for preview (not persisted)
            $previewConfig = new ServiceOutputConfiguration([
                'name' => 'Preview Config',
                'email_template_type' => $validated['template_type'],
                'include_transcript' => $this->parseBoolean($request, 'include_transcript', true),
                'include_summary' => $this->parseBoolean($request, 'include_summary', true),
                'email_audio_option' => $validated['email_audio_option'] ?? 'none',
                'email_show_admin_link' => $this->parseBoolean($request, 'email_show_admin_link', false),
                'email_subject_template' => $validated['custom_subject'] ?? null,
                'email_body_template' => $validated['custom_body'] ?? null,
            ]);

            // Create the appropriate Mailable
            $mailable = $this->createMailable($sampleCase, $previewConfig);

            // Render to HTML
            $html = $mailable->render();

            // Get subject (handle different Mailable types)
            $subject = $this->extractSubject($mailable);

            Log::debug('[EmailPreview] Generated preview', [
                'template_type' => $validated['template_type'],
                'subject' => $subject,
                'html_length' => strlen($html),
            ]);

            return response()->json([
                'success' => true,
                'html' => $html,
                'subject' => $subject,
                'template_type' => $validated['template_type'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[EmailPreview] Failed to generate preview', [
                'template_type' => $validated['template_type'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Vorschau konnte nicht generiert werden: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create the appropriate Mailable based on template type.
     *
     * @param \App\Models\ServiceCase $case
     * @param ServiceOutputConfiguration $config
     * @return Mailable
     */
    private function createMailable($case, ServiceOutputConfiguration $config): Mailable
    {
        $templateType = $config->email_template_type;

        return match ($templateType) {
            'technical', 'admin' => new BackupNotificationMail($case, $config),
            'custom' => $this->createCustomMailable($case, $config),
            default => new ServiceCaseNotification($case, 'internal'),
        };
    }

    /**
     * Create custom template mailable or fallback.
     *
     * @param \App\Models\ServiceCase $case
     * @param ServiceOutputConfiguration $config
     * @return Mailable
     */
    private function createCustomMailable($case, ServiceOutputConfiguration $config): Mailable
    {
        // Check if custom template is defined
        if (!empty($config->email_body_template)) {
            return new CustomTemplateEmail($case, $config);
        }

        // Fallback to standard notification
        return new ServiceCaseNotification($case, 'internal');
    }

    /**
     * Extract subject from Mailable.
     *
     * @param Mailable $mailable
     * @return string
     */
    private function extractSubject(Mailable $mailable): string
    {
        // Try accessing subject property directly
        if (property_exists($mailable, 'subject') && !empty($mailable->subject)) {
            return $mailable->subject;
        }

        // For BackupNotificationMail, subject is set during build
        if ($mailable instanceof BackupNotificationMail) {
            return $mailable->subject ?? 'Service Case Benachrichtigung';
        }

        // Default fallback
        return 'E-Mail Vorschau';
    }

    /**
     * Parse boolean from request (handles '0', '1', 'true', 'false').
     *
     * @param Request $request
     * @param string $key
     * @param bool $default
     * @return bool
     */
    private function parseBoolean(Request $request, string $key, bool $default = false): bool
    {
        if (!$request->has($key)) {
            return $default;
        }

        $value = $request->input($key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
