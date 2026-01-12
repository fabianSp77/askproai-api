<?php

namespace App\Services\ServiceGateway;

use App\Models\EmailTemplate;

class EmailTemplateService
{
    /**
     * Render an email template with the provided data.
     *
     * @return array ['subject' => string, 'body' => string]
     */
    public function render(EmailTemplate $template, array $data): array
    {
        $subject = $this->replaceVariables($template->subject, $data);
        $body = $this->replaceVariables($template->body_html, $data);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Replace {{variable}} placeholders with values from data array.
     * Missing variables are replaced with empty string.
     */
    private function replaceVariables(string $text, array $data): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($data) {
            $variableName = $matches[1];

            return $data[$variableName] ?? '';
        }, $text);
    }
}
