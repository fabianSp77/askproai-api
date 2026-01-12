<?php

namespace Database\Seeders;

use App\Models\EmailTemplatePreset;
use Illuminate\Database\Seeder;

class EmailTemplatePresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $presets = [
            [
                'key' => 'standard',
                'name' => 'Standard Ticket Benachrichtigung (HTML)',
                'description' => 'Moderne, responsive HTML-Vorlage mit vollständiger Ticket-Information. Unterstützt interne und externe Ansichten mit Badges, SLA-Warnungen und formatiertem Layout.',
                'subject' => '{{#is_internal}}Neues Support-Ticket{{/is_internal}}{{^is_internal}}Ihre Anfrage wurde erfasst{{/is_internal}} - {{ticket_id}}',
                'body_html' => $this->getStandardPreset(),
                'variables_hint' => 'Kunde: customer_name, customer_phone, customer_email; Fall: ticket_id, subject, description, status, priority, case_type, category; Anruf: called_company_name, called_branch_name, service_number_formatted, call_duration, caller_number; SLA: sla_response_due, sla_resolution_due, is_overdue, is_at_risk; Admin: admin_url',
            ],
            [
                'key' => 'technical',
                'name' => 'Backup/Technische Benachrichtigung',
                'description' => 'Kompakte technische Vorlage mit strukturierter Darstellung. Optimiert für IT-Support mit Anruf-Details, Problem-Beschreibung und optionalen Transkript/Audio-Bereichen.',
                'subject' => 'Support-Ticket Backup - {{ticket_id}}',
                'body_html' => $this->getTechnicalPreset(),
                'variables_hint' => 'Kunde: customer_name, customer_phone, customer_email, customer_location; Fall: ticket_id, subject, description, status, priority, case_type, category, source; Anruf: called_company_name, called_branch_name, service_number_formatted, call_duration; Problem: problem_since, others_affected; Audio: audio_url, has_audio; Admin: admin_url',
            ],
            [
                'key' => 'helpdesk',
                'name' => 'IT-Support Detailansicht',
                'description' => 'Ausführliche IT-Support Vorlage mit JSON-Datenblock, Transkript-Unterstützung und vollständigen technischen Details. Ideal für dokumentationspflichtige Tickets.',
                'subject' => 'Support-Ticket {{ticket_id}} - {{subject}}',
                'body_html' => $this->getHelpdeskPreset(),
                'variables_hint' => 'Kunde: customer_name, customer_phone, customer_email, customer_location; Fall: ticket_id, subject, description, status, priority, case_type, category, source; Anruf: called_company_name, called_branch_name, service_number_formatted, call_duration, caller_number, service_number; Problem: problem_since, others_affected; Transcript: transcript, transcript_truncated, transcript_length; Admin: admin_url',
            ],
            [
                'key' => 'plaintext',
                'name' => 'Einfache Text-Benachrichtigung',
                'description' => 'Klartext-Vorlage ohne HTML. Optimal für E-Mail-Clients mit deaktiviertem HTML oder als Fallback. Unterstützt interne und Kunden-Ansicht.',
                'subject' => '{{#is_internal}}IT Support Ticket{{/is_internal}}{{^is_internal}}Bestätigung Ihrer Anfrage{{/is_internal}} - {{ticket_id}}',
                'body_html' => $this->getPlaintextPreset(),
                'variables_hint' => 'Kunde: customer_name, customer_phone, customer_email, customer_location; Fall: ticket_id, subject, description, status, priority, case_type, category, source; Anruf: called_company_name, called_branch_name, service_number_formatted, call_duration; SLA: sla_response_due, sla_resolution_due, is_response_overdue, is_resolution_overdue; AI: ai_summary, ai_confidence; Admin: admin_url',
            ],
        ];

        foreach ($presets as $preset) {
            EmailTemplatePreset::updateOrCreate(
                ['key' => $preset['key']],
                $preset
            );
        }
    }

    /**
     * Get the standard preset HTML (based on notification-html-v2.blade.php)
     */
    private function getStandardPreset(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ticket_id}} - {{subject}}</title>
    <style>
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .stack-column { display: block !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">

                    <!-- Priority Header -->
                    <tr>
                        <td style="background-color: #DBEAFE; border-left: 5px solid #3B82F6; padding: 16px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #1D4ED8; font-weight: 700; font-size: 13px; text-transform: uppercase;">{{priority}} PRIORITÄT</td>
                                    <td style="text-align: right; color: #6B7280; font-size: 12px;">{{created_date}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Ticket ID -->
                    <tr>
                        <td style="text-align: center; padding: 32px 24px 16px;">
                            <div style="font-family: 'SF Mono', Monaco, monospace; font-size: 28px; font-weight: 700; color: #1F2937;">{{ticket_id}}</div>
                            <div style="color: #6B7280; font-size: 12px; margin-top: 4px;">Ticket-Nummer</div>
                        </td>
                    </tr>

                    <!-- Badges -->
                    <tr>
                        <td style="text-align: center; padding: 0 24px 24px;">
                            <span style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">{{status}}</span>
                            <span style="display: inline-block; background-color: #FEF3C7; color: #D97706; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">{{case_type}}</span>
                            <span style="display: inline-block; background-color: #F3F4F6; color: #374151; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">{{category}}</span>
                            <span style="display: inline-block; background-color: #dbeafe; color: #1e40af; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">{{source}}</span>
                        </td>
                    </tr>

                    <tr><td style="padding: 0 24px;"><div style="border-top: 1px solid #E5E7EB;"></div></td></tr>

                    <!-- Subject & Description -->
                    <tr>
                        <td style="padding: 24px 24px 8px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; margin-bottom: 6px;">BETREFF</div>
                            <div style="color: #1F2937; font-size: 18px; font-weight: 600;">{{subject}}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 24px 24px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; margin-bottom: 6px;">BESCHREIBUNG</div>
                            <div style="background-color: #F9FAFB; border-radius: 8px; padding: 16px; color: #374151; font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{description}}</div>
                        </td>
                    </tr>

                    <!-- Contact Card -->
                    <tr>
                        <td style="padding: 0 24px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #1D4ED8; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 12px;">KONTAKT / KUNDE</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Name</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{customer_name}}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Telefon</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;"><a href="tel:{{customer_phone}}" style="color: #1F2937; text-decoration: none;">{{customer_phone}}</a></td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px;">E-Mail</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right;"><a href="mailto:{{customer_email}}" style="color: #1D4ED8; text-decoration: none;">{{customer_email}}</a></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Call Details Card -->
                    <tr>
                        <td style="padding: 0 24px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #7C3AED; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 12px;">ANRUF-DETAILS</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Angerufene Nummer</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;"><a href="tel:{{service_number}}" style="color: #1F2937; text-decoration: none;">{{service_number_formatted}}</a></td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Zugehöriges Unternehmen</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{receiver_display}}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px;">Gesprächsdauer</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right;">{{call_duration}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Action Button -->
                    <tr>
                        <td style="text-align: center; padding: 8px 24px 32px;">
                            <a href="{{admin_url}}" style="display: inline-block; background-color: #2563EB; color: #ffffff; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;">TICKET BEARBEITEN</a>
                            <p style="margin: 8px 0 0; font-size: 11px; color: #9CA3AF;">Link gültig für 72 Stunden</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 16px 24px; border-top: 1px solid #E5E7EB;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #9CA3AF; font-size: 11px;">Gesendet: {{sent_date}}</td>
                                    <td style="text-align: right; color: #9CA3AF; font-size: 11px;">{{company_name}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get the technical preset HTML (based on backup-notification.blade.php)
     */
    private function getTechnicalPreset(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support-Ticket Backup</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color:#f3f4f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.07);">

                    <!-- Priority Header -->
                    <tr>
                        <td style="background-color:#2563eb; padding:14px 24px; border-radius:12px 12px 0 0;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color:#ffffff; font-size:13px; font-weight:700;">{{priority}} PRIORITÄT</td>
                                    <td align="right" style="color:rgba(255,255,255,0.85); font-size:12px;">{{created_date}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Ticket ID Hero -->
                    <tr>
                        <td align="center" style="padding:28px 24px 12px;">
                            <div style="font-family:'SF Mono', Monaco, monospace; font-size:32px; font-weight:700; color:#111827;">{{ticket_id}}</div>
                            <div style="color:#6b7280; font-size:12px; margin-top:4px;">Ticket-Nummer</div>
                        </td>
                    </tr>

                    <!-- Status Badges -->
                    <tr>
                        <td align="center" style="padding:0 24px 20px;">
                            <span style="display:inline-block; background-color:#f3f4f6; color:#374151; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; margin:3px;">{{status}}</span>
                            <span style="display:inline-block; background-color:#fef3c7; color:#92400e; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; margin:3px;">{{case_type}}</span>
                            <span style="display:inline-block; background-color:#dbeafe; color:#1e40af; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; margin:3px;">{{category}}</span>
                            <span style="display:inline-block; background-color:#dbeafe; color:#1e40af; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; margin:3px;">{{source}}</span>
                        </td>
                    </tr>

                    <tr><td style="padding:0 24px;"><div style="border-top:1px solid #e5e7eb;"></div></td></tr>

                    <!-- Contact Info -->
                    <tr>
                        <td style="padding:20px 24px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-left:3px solid #3b82f6; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <div style="color:#1f2937; font-size:12px; font-weight:600; text-transform:uppercase; margin-bottom:12px;">Kontaktdaten</div>
                                        <table width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-bottom:8px; width:100px;">Name</td>
                                                <td style="color:#111827; font-size:13px; font-weight:500; padding-bottom:8px;">{{customer_name}}</td>
                                            </tr>
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-bottom:8px;">Telefon</td>
                                                <td style="padding-bottom:8px;"><a href="tel:{{customer_phone}}" style="color:#2563eb; font-size:13px; font-weight:500; text-decoration:none;">{{customer_phone}}</a></td>
                                            </tr>
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px;">E-Mail</td>
                                                <td><a href="mailto:{{customer_email}}" style="color:#2563eb; font-size:13px; font-weight:500; text-decoration:none;">{{customer_email}}</a></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Call Details -->
                    <tr>
                        <td style="padding:0 24px 20px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-left:3px solid #8b5cf6; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <div style="color:#1f2937; font-size:12px; font-weight:600; text-transform:uppercase; margin-bottom:12px;">Anruf-Details</div>
                                        <table width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-bottom:8px; width:140px;">Angerufene Nummer</td>
                                                <td style="padding-bottom:8px;"><a href="tel:{{service_number}}" style="color:#2563eb; font-size:13px; font-weight:500; text-decoration:none;">{{service_number_formatted}}</a></td>
                                            </tr>
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-bottom:8px;">Zugehöriges Unternehmen</td>
                                                <td style="color:#111827; font-size:13px; font-weight:500; padding-bottom:8px;">{{receiver_display}}</td>
                                            </tr>
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px;">Gesprächsdauer</td>
                                                <td style="color:#111827; font-size:13px; font-weight:500;">{{call_duration}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Issue Details -->
                    <tr>
                        <td style="padding:0 24px 20px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-left:3px solid #f59e0b; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <div style="color:#1f2937; font-size:12px; font-weight:600; text-transform:uppercase; margin-bottom:12px;">Problembeschreibung</div>
                                        <div style="color:#111827; font-size:16px; font-weight:600; margin-bottom:12px;">{{subject}}</div>
                                        <div style="background-color:#ffffff; border-radius:6px; padding:12px; color:#374151; font-size:13px; line-height:1.6;">{{description}}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Action Button -->
                    <tr>
                        <td align="center" style="padding:0 24px 24px;">
                            <a href="{{admin_url}}" style="display:inline-block; background-color:#2563eb; color:#ffffff; padding:14px 32px; border-radius:8px; font-size:14px; font-weight:600; text-decoration:none;">Ticket bearbeiten</a>
                            <p style="margin:8px 0 0; font-size:11px; color:#9ca3af;">Link gültig für 72 Stunden</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9fafb; padding:16px 24px; border-radius:0 0 12px 12px; border-top:1px solid #e5e7eb;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color:#9ca3af; font-size:11px;">Gesendet: {{sent_date}}</td>
                                    <td align="right" style="color:#9ca3af; font-size:11px;">{{company_name}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get the helpdesk preset HTML (based on it-support-notification.blade.php)
     */
    private function getHelpdeskPreset(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support-Ticket {{ticket_id}}</title>
    <style>
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .stack-column { display: block !important; width: 100% !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="640" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07);">

                    <!-- Priority Header -->
                    <tr>
                        <td style="background-color: #DBEAFE; border-left: 6px solid #3B82F6; padding: 14px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #1D4ED8; font-weight: 700; font-size: 12px; text-transform: uppercase;">{{priority}} PRIORITÄT</td>
                                    <td style="text-align: right; color: #6B7280; font-size: 12px;">{{created_date}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Ticket ID -->
                    <tr>
                        <td style="text-align: center; padding: 28px 24px 16px;">
                            <div style="font-family: 'SF Mono', Monaco, monospace; font-size: 32px; font-weight: 700; color: #1F2937;">{{ticket_id}}</div>
                            <div style="color: #9CA3AF; font-size: 11px; text-transform: uppercase; margin-top: 6px;">Ticket-Nummer</div>
                        </td>
                    </tr>

                    <!-- Badges -->
                    <tr>
                        <td style="text-align: center; padding: 0 24px 24px;">
                            <span style="display: inline-block; background-color: #E0E7FF; color: #4338CA; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin: 3px;">{{status}}</span>
                            <span style="display: inline-block; background-color: #FEF3C7; color: #D97706; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin: 3px;">{{case_type}}</span>
                            <span style="display: inline-block; background-color: #FEE2E2; color: #DC2626; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin: 3px;">{{category}}</span>
                            <span style="display: inline-block; background-color: #dbeafe; color: #1e40af; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; margin: 3px;">{{source}}</span>
                        </td>
                    </tr>

                    <tr><td style="padding: 0 24px;"><div style="border-top: 1px solid #E5E7EB;"></div></td></tr>

                    <!-- Contact Info -->
                    <tr>
                        <td style="padding: 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <div style="color: #1D4ED8; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #BFDBFE;">Kontaktinformationen</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 100px;">Name</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 600; padding: 6px 0;">{{customer_name}}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0;">Telefon</td>
                                                <td style="padding: 6px 0;"><a href="tel:{{customer_phone}}" style="color: #1D4ED8; font-size: 14px; font-weight: 500; text-decoration: none;">{{customer_phone}}</a></td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0;">E-Mail</td>
                                                <td style="padding: 6px 0;"><a href="mailto:{{customer_email}}" style="color: #1D4ED8; font-size: 14px; font-weight: 500; text-decoration: none;">{{customer_email}}</a></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Call Details -->
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <div style="color: #6D28D9; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #DDD6FE;">Anruf-Details</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0; width: 140px;">Angerufene Nummer</td>
                                                <td style="padding: 6px 0;"><a href="tel:{{service_number}}" style="color: #6D28D9; font-size: 14px; font-weight: 500; text-decoration: none;">{{service_number_formatted}}</a></td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0;">Zugehöriges Unternehmen</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 500; padding: 6px 0;">{{receiver_display}}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding: 6px 0;">Gesprächsdauer</td>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 500; padding: 6px 0;">{{call_duration}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Issue Details -->
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFF7ED; border: 1px solid #FED7AA; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <div style="color: #C2410C; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #FED7AA;">Problembeschreibung</div>
                                        <div style="margin-bottom: 16px;">
                                            <div style="color: #78716C; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;">Betreff</div>
                                            <div style="color: #1F2937; font-size: 16px; font-weight: 600; line-height: 1.4;">{{subject}}</div>
                                        </div>
                                        <div>
                                            <div style="color: #78716C; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;">Beschreibung</div>
                                            <div style="background-color: #FFFBEB; border-radius: 6px; padding: 14px; color: #374151; font-size: 14px; line-height: 1.6; white-space: pre-wrap; border: 1px solid #FDE68A;">{{description}}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Action Button -->
                    <tr>
                        <td style="text-align: center; padding: 8px 24px 28px;">
                            <a href="{{admin_url}}" style="display: inline-block; background-color: #2563EB; color: #ffffff; padding: 14px 36px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; border: 1px solid #1D4ED8;">Ticket bearbeiten</a>
                            <p style="margin: 8px 0 0; font-size: 11px; color: #94A3B8;">Link gültig für 72 Stunden</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F8FAFC; padding: 18px 24px; border-top: 1px solid #E2E8F0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #94A3B8; font-size: 11px;">Gesendet: {{sent_date}}</td>
                                    <td style="text-align: right; color: #64748B; font-size: 11px; font-weight: 500;">{{company_name}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get the plaintext preset (based on notification-text.blade.php)
     */
    private function getPlaintextPreset(): string
    {
        return <<<'TEXT'
================================================================================
                         IT SUPPORT TICKET BENACHRICHTIGUNG
================================================================================

PRIORITÄT: {{priority}}

--------------------------------------------------------------------------------
TICKET-NUMMER:  {{ticket_id}}
--------------------------------------------------------------------------------

Status:         {{status}}
Typ:            {{case_type}}
Herkunft:       {{source}}
Kategorie:      {{category}}
Erstellt am:    {{created_date}}

================================================================================
BETREFF
================================================================================

{{subject}}

================================================================================
BESCHREIBUNG
================================================================================

{{description}}

================================================================================
KONTAKT / KUNDE
================================================================================

Name:           {{customer_name}}
Telefon:        {{customer_phone}}
E-Mail:         {{customer_email}}

================================================================================
ANRUF-DETAILS
================================================================================

Angerufene Nr.: {{service_number_formatted}} (vom Kunden gewählt)
Zugehöriges Unternehmen: {{receiver_display}}
Gesprächsdauer: {{call_duration}}
Datum:          {{created_date}}

================================================================================
SLA-FRISTEN
================================================================================

Erste Reaktion bis:  {{sla_response_due}}
Lösung bis:         {{sla_resolution_due}}

================================================================================
AKTION ERFORDERLICH
================================================================================

Ticket bearbeiten (Link 72h gültig):
{{admin_url}}

--------------------------------------------------------------------------------
Gesendet: {{sent_date}}
{{company_name}}

Diese E-Mail wurde automatisch generiert.
Bitte antworten Sie nicht direkt auf diese Nachricht.
================================================================================
TEXT;
    }
}
