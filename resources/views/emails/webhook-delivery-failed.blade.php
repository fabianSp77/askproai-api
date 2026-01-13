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
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <!-- Preheader Text -->
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        {{ $count }} Webhook-Fehler erkannt ({{ $period }})
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
                                        !!! WEBHOOK-FEHLER ERKANNT
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
                                                    <div style="color: #A16207; font-size: 10px; margin-top: 2px;">HTTP 200 + Fehler im Body</div>
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

                    {{-- Help Box --}}
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #1D4ED8; font-size: 12px; font-weight: 600; margin-bottom: 8px;">
                                            [i] EMPFOHLENE MASSNAHMEN
                                        </div>
                                        <div style="color: #1E40AF; font-size: 13px; line-height: 1.5;">
                                            Pruefen Sie die fehlgeschlagenen Webhooks und beheben Sie eventuelle Konfigurationsprobleme.
                                            Semantische Fehler deuten oft auf Aenderungen in der API-Struktur oder ungueltige Credentials hin.
                                        </div>
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
