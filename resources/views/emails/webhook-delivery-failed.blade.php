{{--
    Webhook Delivery Failed Email Template

    A professional, scannable email notification for webhook failures.
    Features:
    - Mobile-first responsive design
    - Dark mode support via @media (prefers-color-scheme: dark)
    - Clear visual hierarchy with error type color-coding
    - Accessible color contrast ratios (WCAG AA compliant)
--}}
<!DOCTYPE html>
<html lang="de" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Webhook-Fehler erkannt</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Base styles */
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #f4f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a2e !important;
            }
            .email-wrapper {
                background-color: #1a1a2e !important;
            }
            .email-content {
                background-color: #16213e !important;
            }
            .text-primary {
                color: #e8e8e8 !important;
            }
            .text-secondary {
                color: #a0aec0 !important;
            }
            .text-muted {
                color: #718096 !important;
            }
            .bg-header {
                background-color: #0f3460 !important;
            }
            .bg-card {
                background-color: #1a1a2e !important;
                border-color: #2d3748 !important;
            }
            .border-light {
                border-color: #2d3748 !important;
            }
            .bg-summary {
                background-color: #1a1a2e !important;
            }
            .error-card {
                background-color: #1a1a2e !important;
                border-color: #2d3748 !important;
            }
            /* Error type badges dark mode */
            .badge-semantic {
                background-color: #744210 !important;
                color: #fbd38d !important;
            }
            .badge-http {
                background-color: #742a2a !important;
                color: #feb2b2 !important;
            }
            .badge-exception {
                background-color: #2d3748 !important;
                color: #a0aec0 !important;
            }
        }

        /* Responsive styles */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .mobile-padding {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
            .mobile-stack {
                display: block !important;
                width: 100% !important;
            }
            .mobile-center {
                text-align: center !important;
            }
            .mobile-hide {
                display: none !important;
            }
            .summary-item {
                display: block !important;
                margin-bottom: 12px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7;">
    <!-- Wrapper -->
    <table role="presentation" class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7;">
        <tr>
            <td align="center" style="padding: 24px 16px;">

                <!-- Main Container -->
                <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td class="bg-header" style="background-color: #dc2626; border-radius: 12px 12px 0 0; padding: 32px 24px; text-align: center;">
                            <!-- Alert Icon -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 16px;">
                                        <div style="width: 56px; height: 56px; background-color: rgba(255,255,255,0.2); border-radius: 50%; display: inline-block; line-height: 56px;">
                                            <span style="font-size: 28px; color: #ffffff;">!</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #ffffff; line-height: 1.3;">
                                            Webhook-Fehler erkannt
                                        </h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 8px;">
                                        <p style="margin: 0; font-size: 16px; color: rgba(255,255,255,0.9);">
                                            {{ $count }} fehlgeschlagene Zustellung{{ $count > 1 ? 'en' : '' }} ({{ $period }})
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content Area -->
                    <tr>
                        <td class="email-content" style="background-color: #ffffff; padding: 32px 24px;">

                            <!-- Summary Cards -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td>
                                        <h2 class="text-primary" style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1a202c; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Zusammenfassung
                                        </h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="bg-summary" style="background-color: #f7fafc; border-radius: 8px; padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                @if($semanticCount > 0)
                                                <td class="summary-item" style="vertical-align: top; padding-right: 16px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="padding-bottom: 4px;">
                                                                <span class="badge-semantic" style="display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                                    Semantisch
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <span class="text-primary" style="font-size: 28px; font-weight: 700; color: #1a202c;">{{ $semanticCount }}</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <span class="text-muted" style="font-size: 12px; color: #718096;">HTTP 200, Fehler im Body</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                @endif

                                                @if($httpErrorCount > 0)
                                                <td class="summary-item" style="vertical-align: top; padding-right: 16px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="padding-bottom: 4px;">
                                                                <span class="badge-http" style="display: inline-block; background-color: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                                    HTTP
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <span class="text-primary" style="font-size: 28px; font-weight: 700; color: #1a202c;">{{ $httpErrorCount }}</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <span class="text-muted" style="font-size: 12px; color: #718096;">Status 4xx/5xx</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                @endif

                                                @if($exceptionCount > 0)
                                                <td class="summary-item" style="vertical-align: top;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="padding-bottom: 4px;">
                                                                <span class="badge-exception" style="display: inline-block; background-color: #e2e8f0; color: #4a5568; font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                                    Exception
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <span class="text-primary" style="font-size: 28px; font-weight: 700; color: #1a202c;">{{ $exceptionCount }}</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <span class="text-muted" style="font-size: 12px; color: #718096;">Verbindung/Timeout</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Error Details -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <h2 class="text-primary" style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1a202c; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Fehlerdetails (letzte {{ min(5, $count) }})
                                        </h2>
                                    </td>
                                </tr>

                                @foreach($logs as $log)
                                <tr>
                                    <td style="padding-bottom: 16px;">
                                        @php
                                            $statusType = match(true) {
                                                $log->hasSemanticError() => 'semantic',
                                                $log->status_code >= 400 => 'http',
                                                default => 'exception',
                                            };
                                            $badgeStyle = match($statusType) {
                                                'semantic' => 'background-color: #fef3c7; color: #92400e;',
                                                'http' => 'background-color: #fee2e2; color: #991b1b;',
                                                'exception' => 'background-color: #e2e8f0; color: #4a5568;',
                                            };
                                            $badgeText = match($statusType) {
                                                'semantic' => 'Semantisch',
                                                'http' => 'HTTP ' . $log->status_code,
                                                'exception' => 'Exception',
                                            };
                                            $borderColor = match($statusType) {
                                                'semantic' => '#f59e0b',
                                                'http' => '#ef4444',
                                                'exception' => '#6b7280',
                                            };
                                        @endphp
                                        <table role="presentation" class="error-card" width="100%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid {{ $borderColor }}; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <!-- Error Header -->
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                                        <tr>
                                                            <td style="vertical-align: middle;">
                                                                <span class="badge-{{ $statusType }}" style="display: inline-block; {{ $badgeStyle }} font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                                    {{ $badgeText }}
                                                                </span>
                                                            </td>
                                                            <td style="text-align: right; vertical-align: middle;">
                                                                <span class="text-muted" style="font-size: 12px; color: #718096;">
                                                                    {{ $log->created_at->format('d.m.Y H:i:s') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <!-- Endpoint -->
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                                        <tr>
                                                            <td>
                                                                <span class="text-muted" style="font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px;">Endpoint</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <code class="text-primary" style="font-family: 'SF Mono', Monaco, Consolas, monospace; font-size: 13px; color: #1a202c; word-break: break-all;">
                                                                    {{ Str::limit($log->endpoint, 80) }}
                                                                </code>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    @if($log->error_class)
                                                    <!-- Error Type -->
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                                        <tr>
                                                            <td>
                                                                <span class="text-muted" style="font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px;">Fehlertyp</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <code style="font-family: 'SF Mono', Monaco, Consolas, monospace; font-size: 12px; color: #dc2626;">
                                                                    {{ Str::limit($log->error_class, 60) }}
                                                                </code>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    @endif

                                                    @if($log->error_message)
                                                    <!-- Error Message -->
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td>
                                                                <span class="text-muted" style="font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px;">Meldung</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <p class="text-secondary" style="margin: 4px 0 0 0; font-size: 13px; color: #4a5568; line-height: 1.5;">
                                                                    {{ Str::limit($log->error_message, 150) }}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach

                                @if($count > 5)
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <p class="text-muted" style="margin: 0; font-size: 14px; color: #718096; text-align: center; font-style: italic;">
                                            ... und {{ $count - 5 }} weitere Fehler
                                        </p>
                                    </td>
                                </tr>
                                @endif
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 32px;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background-color: #2563eb; border-radius: 8px;">
                                                    <a href="{{ $adminUrl }}" target="_blank" style="display: inline-block; padding: 14px 32px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">
                                                        Alle Fehler im Admin-Panel anzeigen
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Help Text -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 24px;">
                                <tr>
                                    <td class="bg-card" style="background-color: #f7fafc; border-radius: 8px; padding: 16px; border: 1px solid #e2e8f0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width: 24px; vertical-align: top; padding-right: 12px;">
                                                    <span style="font-size: 18px;">&#128161;</span>
                                                </td>
                                                <td>
                                                    <p class="text-secondary" style="margin: 0; font-size: 13px; color: #4a5568; line-height: 1.6;">
                                                        <strong style="color: #1a202c;">Empfohlene Massnahmen:</strong><br>
                                                        Pruefen Sie die fehlgeschlagenen Webhooks und beheben Sie eventuelle Konfigurationsprobleme.
                                                        Semantische Fehler deuten oft auf Aenderungen in der API-Struktur hin.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1a202c; border-radius: 0 0 12px 12px; padding: 24px; text-align: center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #ffffff;">
                                            AskPro AI - Service Gateway
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="margin: 0; font-size: 12px; color: #a0aec0;">
                                            Automatische Benachrichtigung - Bitte nicht antworten
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
                <!-- /Main Container -->

            </td>
        </tr>
    </table>
    <!-- /Wrapper -->
</body>
</html>
