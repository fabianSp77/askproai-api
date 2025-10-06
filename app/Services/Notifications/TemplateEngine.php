<?php

namespace App\Services\Notifications;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class TemplateEngine
{
    protected array $variables = [];
    protected array $translations = [];
    protected string $defaultLanguage = 'de';

    public function __construct()
    {
        $this->defaultLanguage = config('app.locale', 'de');
        $this->loadTranslations();
    }

    /**
     * Render notification from template
     */
    public function render(NotificationTemplate $template, array $data, string $language = 'de'): array
    {
        // Get content for language
        $content = $this->getLocalizedContent($template, $language);

        // Process variables
        $processedContent = $this->processVariables($content, $data);

        // Apply formatting
        $formattedContent = $this->applyFormatting($processedContent, $template->channel);

        return [
            'subject' => $formattedContent['subject'] ?? null,
            'content' => $formattedContent['content'],
            'preview' => $this->generatePreview($formattedContent['content']),
            'metadata' => [
                'template_id' => $template->id,
                'template_key' => $template->key,
                'language' => $language,
                'rendered_at' => now()->toIso8601String()
            ]
        ];
    }

    /**
     * Get localized content from template
     */
    protected function getLocalizedContent(NotificationTemplate $template, string $language): array
    {
        $content = [];

        // Get subject
        if ($template->subject) {
            $content['subject'] = $template->subject[$language]
                ?? $template->subject[$this->defaultLanguage]
                ?? array_values($template->subject)[0]
                ?? '';
        }

        // Get body content
        if ($template->content) {
            $content['content'] = $template->content[$language]
                ?? $template->content[$this->defaultLanguage]
                ?? array_values($template->content)[0]
                ?? '';
        }

        return $content;
    }

    /**
     * Process variables in content
     */
    protected function processVariables(array $content, array $data): array
    {
        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $content[$key] = $this->replaceVariables($value, $data);
            }
        }

        return $content;
    }

    /**
     * Replace variables in string
     */
    protected function replaceVariables(string $text, array $data): string
    {
        // Simple variable replacement: {variable}
        $text = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? $matches[0];
        }, $text);

        // Nested variable replacement: {customer.name}
        $text = preg_replace_callback('/\{(\w+)\.(\w+)\}/', function ($matches) use ($data) {
            $key1 = $matches[1];
            $key2 = $matches[2];
            return $data[$key1][$key2] ?? $matches[0];
        }, $text);

        // Date formatting: {date:format}
        $text = preg_replace_callback('/\{(\w+):date\(([^)]+)\)\}/', function ($matches) use ($data) {
            $key = $matches[1];
            $format = $matches[2];

            if (isset($data[$key])) {
                $date = is_string($data[$key])
                    ? \Carbon\Carbon::parse($data[$key])
                    : $data[$key];

                if ($date instanceof \Carbon\Carbon) {
                    return $date->format($format);
                }
            }

            return $matches[0];
        }, $text);

        // Currency formatting: {amount:currency}
        $text = preg_replace_callback('/\{(\w+):currency\}/', function ($matches) use ($data) {
            $key = $matches[1];

            if (isset($data[$key])) {
                return $this->formatCurrency($data[$key]);
            }

            return $matches[0];
        }, $text);

        // Conditional blocks: {if:variable}...{/if}
        $text = preg_replace_callback('/\{if:(\w+)\}(.*?)\{\/if\}/s', function ($matches) use ($data) {
            $condition = $matches[1];
            $content = $matches[2];

            if (!empty($data[$condition])) {
                return $this->replaceVariables($content, $data);
            }

            return '';
        }, $text);

        // Default values: {variable|default}
        $text = preg_replace_callback('/\{(\w+)\|([^}]+)\}/', function ($matches) use ($data) {
            $key = $matches[1];
            $default = $matches[2];

            return $data[$key] ?? $default;
        }, $text);

        return $text;
    }

    /**
     * Apply channel-specific formatting
     */
    protected function applyFormatting(array $content, string $channel): array
    {
        switch ($channel) {
            case 'email':
                return $this->formatForEmail($content);

            case 'sms':
                return $this->formatForSms($content);

            case 'whatsapp':
                return $this->formatForWhatsApp($content);

            case 'push':
                return $this->formatForPush($content);

            default:
                return $content;
        }
    }

    /**
     * Format content for email
     */
    protected function formatForEmail(array $content): array
    {
        // Convert markdown to HTML if needed
        if (isset($content['content']) && $this->isMarkdown($content['content'])) {
            $content['html'] = Str::markdown($content['content']);
            $content['text'] = strip_tags($content['html']);
        }

        return $content;
    }

    /**
     * Format content for SMS
     */
    protected function formatForSms(array $content): array
    {
        if (isset($content['content'])) {
            // Remove HTML tags
            $content['content'] = strip_tags($content['content']);

            // Limit length
            if (strlen($content['content']) > 160) {
                $content['content'] = substr($content['content'], 0, 157) . '...';
            }
        }

        return $content;
    }

    /**
     * Format content for WhatsApp
     */
    protected function formatForWhatsApp(array $content): array
    {
        if (isset($content['content'])) {
            // Convert basic markdown for WhatsApp
            $text = $content['content'];

            // Bold: **text** -> *text*
            $text = preg_replace('/\*\*(.*?)\*\*/', '*$1*', $text);

            // Italic: _text_ stays the same

            // Strikethrough: ~~text~~ -> ~text~
            $text = preg_replace('/~~(.*?)~~/', '~$1~', $text);

            // Code: `text` -> ```text```
            $text = preg_replace('/`([^`]+)`/', '```$1```', $text);

            $content['content'] = $text;
        }

        return $content;
    }

    /**
     * Format content for push notifications
     */
    protected function formatForPush(array $content): array
    {
        if (isset($content['content'])) {
            // Limit length for push
            if (strlen($content['content']) > 200) {
                $content['content'] = substr($content['content'], 0, 197) . '...';
            }

            // Remove formatting
            $content['content'] = strip_tags($content['content']);
        }

        // Ensure title is present
        if (!isset($content['title']) && isset($content['subject'])) {
            $content['title'] = $content['subject'];
        }

        return $content;
    }

    /**
     * Generate preview text
     */
    protected function generatePreview(string $content, int $length = 100): string
    {
        $preview = strip_tags($content);
        $preview = str_replace(["\r", "\n"], ' ', $preview);
        $preview = preg_replace('/\s+/', ' ', $preview);

        if (strlen($preview) > $length) {
            $preview = substr($preview, 0, $length - 3) . '...';
        }

        return trim($preview);
    }

    /**
     * Check if content is markdown
     */
    protected function isMarkdown(string $content): bool
    {
        // Check for common markdown patterns
        $patterns = [
            '/^#{1,6}\s/',     // Headers
            '/\*\*.+?\*\*/',   // Bold
            '/\*.+?\*/',       // Italic
            '/\[.+?\]\(.+?\)/', // Links
            '/^[-*+]\s/',      // Lists
            '/```/',           // Code blocks
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format currency
     */
    protected function formatCurrency($amount, string $currency = 'EUR'): string
    {
        return number_format($amount, 2, ',', '.') . ' €';
    }

    /**
     * Load translations
     */
    protected function loadTranslations(): void
    {
        $this->translations = Cache::remember('notification_translations', 3600, function () {
            return [
                'de' => [
                    'appointment' => 'Termin',
                    'confirmation' => 'Bestätigung',
                    'reminder' => 'Erinnerung',
                    'cancelled' => 'Storniert',
                    'rescheduled' => 'Verschoben',
                    'tomorrow' => 'Morgen',
                    'today' => 'Heute',
                    'at' => 'um',
                    'with' => 'mit',
                    'dear' => 'Liebe(r)',
                    'sincerely' => 'Mit freundlichen Grüßen',
                ],
                'en' => [
                    'appointment' => 'Appointment',
                    'confirmation' => 'Confirmation',
                    'reminder' => 'Reminder',
                    'cancelled' => 'Cancelled',
                    'rescheduled' => 'Rescheduled',
                    'tomorrow' => 'Tomorrow',
                    'today' => 'Today',
                    'at' => 'at',
                    'with' => 'with',
                    'dear' => 'Dear',
                    'sincerely' => 'Sincerely',
                ]
            ];
        });
    }

    /**
     * Translate key
     */
    public function translate(string $key, string $language = 'de'): string
    {
        return $this->translations[$language][$key]
            ?? $this->translations[$this->defaultLanguage][$key]
            ?? $key;
    }

    /**
     * Validate template variables
     */
    public function validateTemplate(string $template, array $requiredVariables = []): array
    {
        $errors = [];
        $foundVariables = [];

        // Extract variables from template
        preg_match_all('/\{(\w+)(?:[:|.][\w()]+)?\}/', $template, $matches);

        if (!empty($matches[1])) {
            $foundVariables = array_unique($matches[1]);
        }

        // Check required variables
        foreach ($requiredVariables as $required) {
            if (!in_array($required, $foundVariables)) {
                $errors[] = "Required variable '{$required}' not found in template";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'variables' => $foundVariables
        ];
    }

    /**
     * Create template from string
     */
    public function createTemplate(
        string $key,
        string $name,
        string $channel,
        string $type,
        array $content,
        array $variables = []
    ): NotificationTemplate {
        // Validate content for each language
        foreach ($content as $lang => $langContent) {
            if (isset($langContent['content'])) {
                $validation = $this->validateTemplate($langContent['content'], $variables);

                if (!$validation['valid']) {
                    throw new \InvalidArgumentException(
                        "Template validation failed for language {$lang}: " .
                        implode(', ', $validation['errors'])
                    );
                }
            }
        }

        return NotificationTemplate::create([
            'key' => $key,
            'name' => $name,
            'channel' => $channel,
            'type' => $type,
            'subject' => array_map(function($c) {
                return $c['subject'] ?? null;
            }, $content),
            'content' => array_map(function($c) {
                return $c['content'] ?? null;
            }, $content),
            'variables' => $variables,
            'is_active' => true
        ]);
    }
}