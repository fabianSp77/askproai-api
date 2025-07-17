<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Anrufzusammenfassung</title>
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
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; }

        /* Remove default styling */
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* Mobile styles */
        @media screen and (max-width: 600px) {
            .mobile-hide { display: none !important; }
            .mobile-center { text-align: center !important; }
            .mobile-full-width { width: 100% !important; max-width: 100% !important; }
            .mobile-padding { padding: 20px !important; }
            .mobile-button { width: 100% !important; padding: 15px !important; }
            .mobile-stack { display: block !important; width: 100% !important; }
            .mobile-text-center { text-align: center !important; }
            .mobile-margin-bottom { margin-bottom: 20px !important; }
            h1 { font-size: 24px !important; }
            h2 { font-size: 20px !important; }
            .button-container { padding: 0 !important; }
            .action-button { display: block !important; width: 100% !important; margin-bottom: 10px !important; }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .dark-mode-bg { background-color: #1a1a1a !important; }
            .dark-mode-text { color: #ffffff !important; }
            .dark-mode-border { border-color: #333333 !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Email Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="mobile-full-width" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    
                    <!-- Service Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center;">
                                        <h3 style="color: #ffffff; margin: 0; font-size: 14px; font-weight: 400; opacity: 0.9;">Ein Service von</h3>
                                        <h2 style="color: #ffffff; margin: 5px 0 0 0; font-size: 24px; font-weight: 700;">AskProAI</h2>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Header with Quick Actions -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; border-bottom: 1px solid #e9ecef;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 15px 0; font-size: 16px; color: #495057; font-weight: 600;">IM PORTAL VERFÜGBAR</h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" class="button-container">
                                            <tr>
                                                <!-- Desktop: Side by side, Mobile: Stacked -->
                                                <td class="mobile-stack mobile-margin-bottom" style="padding-right: 10px;">
                                                    <a href="{{ $detailsUrl }}" style="display: inline-block; padding: 12px 24px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center;" class="action-button mobile-button">📋 Details</a>
                                                </td>
                                                <td class="mobile-stack mobile-margin-bottom" style="padding-right: 10px;">
                                                    <a href="{{ $audioUrl }}" style="display: inline-block; padding: 12px 24px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center;" class="action-button mobile-button">🎵 Audio</a>
                                                </td>
                                                <td class="mobile-stack">
                                                    <a href="{{ $csvUrl }}" style="display: inline-block; padding: 12px 24px; background-color: #6c757d; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; text-align: center;" class="action-button mobile-button">📊 CSV</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Urgency Alert (if urgent) -->
                    @if(strtolower($call->urgency_level ?? 'normal') === 'urgent')
                    <tr>
                        <td style="background-color: #fee; padding: 15px 20px; border-left: 4px solid #dc3545;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="font-size: 24px; padding-right: 10px;">🚨</td>
                                    <td style="width: 100%;">
                                        <strong style="color: #dc3545; font-size: 16px;">DRINGEND</strong>
                                        <p style="margin: 5px 0 0 0; color: #721c24; font-size: 14px;">Dieser Anruf erfordert Ihre sofortige Aufmerksamkeit!</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Custom Message (if provided) -->
                    @if(!empty($customHtml))
                    <tr>
                        <td class="mobile-padding" style="padding: 20px 30px; background-color: #e8f4f8; border-bottom: 1px solid #d1e7f0;">
                            {!! $customHtml !!}
                        </td>
                    </tr>
                    @endif

                    <!-- Call Metadata -->
                    <tr>
                        <td class="mobile-padding" style="padding: 25px 30px;">
                            <h2 style="margin: 0 0 20px 0; font-size: 18px; color: #495057; font-weight: 600;">ANRUF-DETAILS</h2>
                            
                            <!-- Responsive grid for metadata -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td class="mobile-stack mobile-margin-bottom" style="width: 50%; padding-right: 15px; vertical-align: top;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <strong style="color: #6c757d; font-size: 13px;">Anrufer:</strong><br>
                                                    <span style="color: #212529; font-size: 15px;">{{ $call->extracted_name ?: $call->from_number }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <strong style="color: #6c757d; font-size: 13px;">Datum & Zeit:</strong><br>
                                                    <span style="color: #212529; font-size: 15px;">{{ \Carbon\Carbon::parse($call->created_at)->format('d.m.Y H:i') }} Uhr</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="mobile-stack" style="width: 50%; padding-left: 15px; vertical-align: top;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <strong style="color: #6c757d; font-size: 13px;">Dauer:</strong><br>
                                                    <span style="color: #212529; font-size: 15px;">{{ gmdate('i:s', $call->duration_sec ?? 0) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <strong style="color: #6c757d; font-size: 13px;">Weitergeleitet von:</strong><br>
                                                    <span style="color: #212529; font-size: 15px;">{{ $company->name ?? 'Unbekannt' }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td class="mobile-padding" style="padding: 0 30px 25px 30px;">
                            <h2 style="margin: 0 0 20px 0; font-size: 18px; color: #495057; font-weight: 600;">Gesprächsinformationen</h2>
                            
                            @if($customerRequest)
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                                <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d; font-weight: 600;">Kundenanfrage:</h3>
                                <p style="margin: 0; color: #212529; font-size: 15px; line-height: 1.6;">{{ $customerRequest }}</p>
                            </div>
                            @endif

                            @if($summary)
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                                <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d; font-weight: 600;">Zusammenfassung:</h3>
                                <p style="margin: 0; color: #212529; font-size: 15px; line-height: 1.6;">{{ $summary }}</p>
                            </div>
                            @endif

                            @if($transcript)
                            <div style="margin-top: 20px;">
                                <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d; font-weight: 600;">Transkript:</h3>
                                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">
                                    <pre style="margin: 0; color: #212529; font-size: 14px; line-height: 1.6; white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">{{ $transcript }}</pre>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0 0 10px 0; color: #6c757d; font-size: 13px;">
                                Diese E-Mail wurde automatisch generiert von AskProAI<br>
                                <a href="https://askproai.de" style="color: #007bff; text-decoration: none;">www.askproai.de</a>
                            </p>
                            <p style="margin: 0; color: #6c757d; font-size: 12px;">
                                © {{ date('Y') }} AskProAI. Alle Rechte vorbehalten.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>