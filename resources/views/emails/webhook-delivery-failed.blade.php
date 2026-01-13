<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
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
    <style type="text/css">
        /* Reset styles */
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* Mobile responsive */
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .stack-column { display: block !important; width: 100% !important; }
            .mobile-padding { padding-left: 16px !important; padding-right: 16px !important; }
            .summary-card { margin-bottom: 12px !important; }
            .mobile-full { width: 100% !important; display: block !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <!-- Preheader Text -->
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        {{ $count }} Webhook-Fehler erkannt ({{ $period }})@if($exhaustedRetries > 0) - {{ $exhaustedRetries }} erfordern Aktion @endif
    </div>

    <!-- Email Container -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">

                    {{-- Alert Header Bar --}}
                    <tr>
                        <td style="background-color: #FEF2F2; border-left: 5px solid #EF4444; padding: 16px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #DC2626; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        WEBHOOK-FEHLER ERKANNT
                                    </td>
                                    <td style="text-align: right; color: #6B7280; font-size: 12px;">
                                        {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero Count --}}
                    <tr>
                        <td style="text-align: center; padding: 32px 24px 16px;">
                            <div style="font-size: 48px; font-weight: 700; color: #DC2626;">
                                {{ $count }}
                            </div>
                            <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                fehlgeschlagene Zustellung{{ $count > 1 ? 'en' : '' }} ({{ $period }})
                            </div>
                        </td>
                    </tr>

                    {{-- Summary Cards --}}
                    <tr>
                        <td style="padding: 16px 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    @if($semanticCount > 0)
                                    {{-- Semantic Error Card (Amber) --}}
                                    <td class="stack-column summary-card" style="vertical-align: top; padding-right: 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px; text-align: center;">
                                                    <div style="color: #D97706; font-size: 24px; font-weight: 700;">{{ $semanticCount }}</div>
                                                    <div style="color: #92400E; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-top: 4px;">Semantisch</div>
                                                    <div style="color: #A16207; font-size: 10px; margin-top: 2px;">HTTP 200 + Fehler</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    @endif

                                    @if($httpErrorCount > 0)
                                    {{-- HTTP Error Card (Red) --}}
                                    <td class="stack-column summary-card" style="vertical-align: top; padding-right: 8px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px; text-align: center;">
                                                    <div style="color: #DC2626; font-size: 24px; font-weight: 700;">{{ $httpErrorCount }}</div>
                                                    <div style="color: #991B1B; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-top: 4px;">HTTP-Fehler</div>
                                                    <div style="color: #B91C1C; font-size: 10px; margin-top: 2px;">Status 4xx/5xx</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    @endif

                                    @if($exceptionCount > 0)
                                    {{-- Exception Card (Gray) --}}
                                    <td class="stack-column summary-card" style="vertical-align: top;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px; text-align: center;">
                                                    <div style="color: #4B5563; font-size: 24px; font-weight: 700;">{{ $exceptionCount }}</div>
                                                    <div style="color: #374151; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-top: 4px;">Exception</div>
                                                    <div style="color: #6B7280; font-size: 10px; margin-top: 2px;">Verbindung/Timeout</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Action Required Banner (NEW) --}}
                    @if($exhaustedRetries > 0)
                    <tr>
                        <td style="padding: 0 24px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FEF3C7; border: 1px solid #FDE68A; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #92400E; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                            AKTION ERFORDERLICH
                                        </div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #78350F; font-size: 13px; padding: 4px 0;">
                                                    <span style="color: #DC2626; font-weight: 600;">{{ $exhaustedRetries }}</span> Fehler: Alle Retries erschoepft (manuelle Aktion noetig)
                                                </td>
                                            </tr>
                                            @if($retriesPending > 0)
                                            <tr>
                                                <td style="color: #78350F; font-size: 13px; padding: 4px 0;">
                                                    <span style="color: #059669; font-weight: 600;">{{ $retriesPending }}</span> Fehler: Auto-Retry aktiv
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- Affected Customers Section (NEW) --}}
                    @if($byCompany->isNotEmpty())
                    <tr>
                        <td style="padding: 0 24px 16px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                BETROFFENE KUNDEN
                            </div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px;">
                                @foreach($byCompany as $company)
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #1F2937; font-size: 14px; font-weight: 600;">
                                                    {{ $company['name'] }}
                                                </td>
                                                <td style="text-align: right;">
                                                    <span style="background-color: #FEE2E2; color: #991B1B; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 12px;">
                                                        {{ $company['count'] }} {{ $company['count'] == 1 ? 'Fehler' : 'Fehler' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- Divider --}}
                    <tr>
                        <td style="padding: 0 24px;">
                            <div style="border-top: 1px solid #E5E7EB;"></div>
                        </td>
                    </tr>

                    {{-- Error Details Header --}}
                    <tr>
                        <td style="padding: 24px 24px 8px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                FEHLERDETAILS (LETZTE {{ min(5, $count) }})
                            </div>
                        </td>
                    </tr>

                    {{-- Error List --}}
                    @foreach($logs as $log)
                    @php
                        $statusType = match(true) {
                            $log->hasSemanticError() => 'semantic',
                            ($log->status_code ?? 0) >= 400 => 'http',
                            default => 'exception',
                        };
                        $borderColor = match($statusType) {
                            'semantic' => '#F59E0B',
                            'http' => '#EF4444',
                            'exception' => '#6B7280',
                        };
                        $badgeBg = match($statusType) {
                            'semantic' => '#FFFBEB',
                            'http' => '#FEF2F2',
                            'exception' => '#F9FAFB',
                        };
                        $badgeText = match($statusType) {
                            'semantic' => '#D97706',
                            'http' => '#DC2626',
                            'exception' => '#4B5563',
                        };
                        $badgeLabel = match($statusType) {
                            'semantic' => 'Semantisch',
                            'http' => 'HTTP ' . $log->status_code,
                            'exception' => 'Exception',
                        };

                        // Format duration
                        $duration = $log->duration_ms ?? 0;
                        $durationFormatted = $duration >= 1000
                            ? number_format($duration / 1000, 2) . 's'
                            : $duration . 'ms';

                        // Response preview
                        $responsePreview = null;
                        if ($log->response_body_redacted) {
                            $responseJson = json_encode($log->response_body_redacted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $responsePreview = Str::limit($responseJson, 150);
                        }

                        // Direct link to this log
                        $logDetailUrl = url("/admin/service-gateway-exchange-logs/{$log->id}");
                    @endphp
                    <tr>
                        <td style="padding: 8px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F9FAFB; border-left: 4px solid {{ $borderColor }}; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        {{-- Header Row --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td>
                                                    <span style="display: inline-block; background-color: {{ $badgeBg }}; color: {{ $badgeText }}; font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid {{ $borderColor }};">
                                                        {{ $badgeLabel }}
                                                    </span>
                                                </td>
                                                <td style="text-align: right; color: #9CA3AF; font-size: 11px;">
                                                    {{ $log->created_at->timezone('Europe/Berlin')->format('d.m.Y H:i:s') }}
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Company & Config (NEW) --}}
                                        @if($log->company || $log->outputConfiguration)
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 12px;">
                                            @if($log->company)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 11px; padding-bottom: 4px;">
                                                    <span style="color: #9CA3AF;">Kunde:</span>
                                                    <span style="color: #1F2937; font-weight: 600;">{{ $log->company->name }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                            @if($log->outputConfiguration)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 11px; padding-bottom: 4px;">
                                                    <span style="color: #9CA3AF;">Config:</span>
                                                    <span style="color: #374151;">{{ $log->outputConfiguration->name }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                        @endif

                                        {{-- Endpoint --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #6B7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 4px;">Endpoint</td>
                                            </tr>
                                            <tr>
                                                <td style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 12px; color: #1F2937; word-break: break-all;">
                                                    {{ Str::limit($log->endpoint, 70) }}
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Duration & Retry Status (NEW) --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 8px;">
                                            <tr>
                                                <td style="color: #6B7280; font-size: 11px;">
                                                    <span style="color: #9CA3AF;">Dauer:</span>
                                                    <span style="color: {{ $duration > 5000 ? '#DC2626' : '#374151' }}; font-weight: 500;">{{ $durationFormatted }}</span>
                                                    <span style="color: #D1D5DB; margin: 0 6px;">|</span>
                                                    <span style="color: #9CA3AF;">Versuch:</span>
                                                    <span style="color: {{ $log->attempt_no >= $log->max_attempts ? '#DC2626' : '#374151' }}; font-weight: 500;">{{ $log->attempt_no }}/{{ $log->max_attempts }}</span>
                                                    @if($log->attempt_no >= $log->max_attempts)
                                                    <span style="color: #DC2626; font-size: 10px; font-weight: 600; margin-left: 4px;">(FINAL)</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>

                                        @if($log->error_class || $log->error_message)
                                        {{-- Error Info --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 12px; background-color: #ffffff; border-radius: 6px; border: 1px solid #E5E7EB;">
                                            <tr>
                                                <td style="padding: 12px;">
                                                    @if($log->error_class)
                                                    <div style="color: #6B7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Fehlertyp</div>
                                                    <div style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 11px; color: #DC2626; margin-top: 2px;">{{ Str::limit($log->error_class, 50) }}</div>
                                                    @endif
                                                    @if($log->error_message)
                                                    <div style="color: #6B7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: {{ $log->error_class ? '10px' : '0' }};">Meldung</div>
                                                    <div style="font-size: 12px; color: #374151; margin-top: 2px; line-height: 1.4;">{{ Str::limit($log->error_message, 120) }}</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                        {{-- Response Preview (NEW) --}}
                                        @if($responsePreview)
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 12px;">
                                            <tr>
                                                <td style="color: #6B7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 4px;">Response</td>
                                            </tr>
                                            <tr>
                                                <td style="background-color: #1F2937; border-radius: 6px; padding: 10px 12px;">
                                                    <div style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 11px; color: #E5E7EB; word-break: break-all; line-height: 1.4;">{{ $responsePreview }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                        {{-- Direct Link Button (NEW) --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 12px;">
                                            <tr>
                                                <td>
                                                    <a href="{{ $logDetailUrl }}" style="display: inline-block; background-color: #ffffff; color: #374151; font-size: 12px; font-weight: 500; padding: 8px 16px; border-radius: 6px; text-decoration: none; border: 1px solid #D1D5DB;">
                                                        Log #{{ $log->id }} anzeigen
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach

                    @if($count > 5)
                    <tr>
                        <td style="padding: 8px 24px 16px; text-align: center;">
                            <span style="color: #9CA3AF; font-size: 13px; font-style: italic;">... und {{ $count - 5 }} weitere Fehler</span>
                        </td>
                    </tr>
                    @endif

                    {{-- Claude Debug Prompt Section --}}
                    <tr>
                        <td style="padding: 24px 24px 8px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                CLAUDE DEBUG PROMPTS
                            </div>
                            <div style="color: #9CA3AF; font-size: 11px; margin-top: 4px;">
                                Kopiere einen Block und fuege ihn in Claude Code ein fuer automatische Fehleranalyse
                            </div>
                        </td>
                    </tr>
                    @foreach($logs as $log)
                    @php
                        $errorTypeLabel = $errorTypeLabels[$loop->index] ?? 'Unbekannt';
                        $requestBodyJson = $log->request_body_redacted
                            ? json_encode($log->request_body_redacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            : 'null';
                        $responseBodyJson = $log->response_body_redacted
                            ? json_encode($log->response_body_redacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            : 'null';

                        // Truncate if too long
                        $maxLen = 2000;
                        if (strlen($requestBodyJson) > $maxLen) {
                            $requestBodyJson = substr($requestBodyJson, 0, $maxLen) . "\n... [TRUNCATED - siehe Admin Panel fuer vollstaendige Daten]";
                        }
                        if (strlen($responseBodyJson) > $maxLen) {
                            $responseBodyJson = substr($responseBodyJson, 0, $maxLen) . "\n... [TRUNCATED - siehe Admin Panel fuer vollstaendige Daten]";
                        }
                    @endphp
                    <tr>
                        <td style="padding: 8px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #1F2937; border-radius: 8px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #374151;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="color: #F9FAFB; font-size: 11px; font-weight: 600;">
                                                    CLAUDE DEBUG PROMPT - Log #{{ $log->id }}
                                                </td>
                                                <td style="text-align: right;">
                                                    <span style="color: #9CA3AF; font-size: 10px;">Kopieren &amp; in Claude Code einfuegen</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px;">
                                        <pre style="margin: 0; font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 10px; color: #E5E7EB; white-space: pre-wrap; word-break: break-word; line-height: 1.5; background: transparent;">Analysiere diesen Webhook-Fehler:

**Fehlertyp:** {{ $errorTypeLabel }}
**Endpoint:** `{{ $log->endpoint }}`
**HTTP:** {{ $log->http_method }} -> {{ $log->status_code ?? 'N/A' }}
**Error Class:** {{ $log->error_class ?? 'Kein Error Class' }}
**Nachricht:** {{ $log->error_message ?? 'Keine Nachricht' }}
**Dauer:** {{ $log->duration_ms ?? 0 }}ms | Versuch: {{ $log->attempt_no }}/{{ $log->max_attempts }}
**Kunde:** {{ $log->company?->name ?? 'System' }}
**Config:** {{ $log->outputConfiguration?->name ?? 'N/A' }}

**Response Body:**
```json
{{ $responseBodyJson }}
```

**Request Body:**
```json
{{ $requestBodyJson }}
```

Bitte analysiere:
1. Was ist die Ursache des Fehlers?
2. Wie kann ich das beheben?
3. Welche Dateien/Configs muss ich pruefen?

Admin-Link: {{ url("/admin/service-gateway-exchange-logs/{$log->id}") }}</pre>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach

                    {{-- Action Button --}}
                    <tr>
                        <td style="text-align: center; padding: 24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 8px; background-color: #2563EB;">
                                        <a href="{{ $adminUrl }}" style="display: inline-block; background-color: #2563EB; color: #ffffff; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; border: 1px solid #2563EB;">
                                            Alle Fehler im Admin-Panel anzeigen
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Help Box with Error-specific Guidance --}}
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #1D4ED8; font-size: 12px; font-weight: 600; margin-bottom: 12px;">
                                            EMPFOHLENE MASSNAHMEN
                                        </div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            @if($semanticCount > 0)
                                            <tr>
                                                <td style="color: #1E40AF; font-size: 13px; line-height: 1.5; padding-bottom: 8px;">
                                                    <span style="color: #D97706; font-weight: 600;">Semantische Fehler:</span> API-Credentials pruefen, HMAC-Secret abgleichen, API-Struktur-Aenderungen beim Anbieter nachfragen.
                                                </td>
                                            </tr>
                                            @endif
                                            @if($httpErrorCount > 0)
                                            <tr>
                                                <td style="color: #1E40AF; font-size: 13px; line-height: 1.5; padding-bottom: 8px;">
                                                    <span style="color: #DC2626; font-weight: 600;">HTTP-Fehler:</span> Endpoint-URL pruefen, Authentifizierung validieren, Rate-Limits beim Anbieter pruefen.
                                                </td>
                                            </tr>
                                            @endif
                                            @if($exceptionCount > 0)
                                            <tr>
                                                <td style="color: #1E40AF; font-size: 13px; line-height: 1.5; padding-bottom: 8px;">
                                                    <span style="color: #4B5563; font-weight: 600;">Exceptions:</span> Netzwerk-Konnektivitaet pruefen, Firewall-Regeln, SSL-Zertifikate validieren.
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 16px 24px; border-top: 1px solid #E5E7EB;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #9CA3AF; font-size: 11px;">
                                        AskPro AI - Service Gateway
                                    </td>
                                    <td style="text-align: right; color: #9CA3AF; font-size: 11px;">
                                        Automatische Benachrichtigung
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                {{-- Footer Text --}}
                <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
                    <tr>
                        <td style="text-align: center; color: #9CA3AF; font-size: 11px;">
                            Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese Nachricht.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
