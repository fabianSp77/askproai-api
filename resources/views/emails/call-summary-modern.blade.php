<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $call->company->name ?? 'AskProAI' }} - Anrufzusammenfassung</title>
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
            .container { padding: 0 !important; width: 100% !important; }
            .content { padding: 20px !important; }
            .button { width: 100% !important; max-width: 300px !important; }
        }

        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            .darkmode-bg { background-color: #111111 !important; }
            .darkmode-text { color: #ffffff !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    
    <!-- Wrapper -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                
                <!-- Email Container -->
                <table class="container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">{{ $call->company->name ?? 'AskProAI' }}</h1>
                            <p style="margin: 10px 0 0 0; color: #e0e7ff; font-size: 16px;">Anrufzusammenfassung</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content" style="padding: 40px 30px;">
                            
                            <!-- Custom Content at Top -->
                            @if($customContent && trim($customContent))
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px 0; border-bottom: 1px solid #e5e7eb;">
                                        {!! $customContent !!}
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($customerInfo)
                            <!-- Customer Info Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="background-color: #f0f9ff; border-radius: 8px; padding: 24px; border-left: 4px solid #3b82f6;">
                                        <h3 style="margin: 0 0 16px 0; color: #1e40af; font-size: 18px; font-weight: 600;">
                                            <span style="font-size: 20px; margin-right: 8px;">📞</span>
                                            Anruferinformationen
                                        </h3>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 4px 0;">
                                                    <strong style="color: #64748b; font-size: 14px;">Name:</strong>
                                                    <span style="color: #1e293b; font-size: 14px; margin-left: 8px;">{{ $customerInfo['name'] }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0;">
                                                    <strong style="color: #64748b; font-size: 14px;">Telefon:</strong>
                                                    <span style="color: #1e293b; font-size: 14px; margin-left: 8px;">{{ $customerInfo['phone'] }}</span>
                                                </td>
                                            </tr>
                                            @if($customerInfo['email'])
                                            <tr>
                                                <td style="padding: 4px 0;">
                                                    <strong style="color: #64748b; font-size: 14px;">E-Mail:</strong>
                                                    <span style="color: #1e293b; font-size: 14px; margin-left: 8px;">{{ $customerInfo['email'] }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                            @if($customerInfo['company'])
                                            <tr>
                                                <td style="padding: 4px 0;">
                                                    <strong style="color: #64748b; font-size: 14px;">Firma:</strong>
                                                    <span style="color: #1e293b; font-size: 14px; margin-left: 8px;">{{ $customerInfo['company'] }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(isset($urgency) && $urgency)
                                            <tr>
                                                <td style="padding: 8px 0 0 0;">
                                                    <strong style="color: #64748b; font-size: 14px;">Dringlichkeit:</strong>
                                                    @php
                                                        $urgencyColors = [
                                                            'urgent' => '#dc2626',
                                                            'high' => '#f97316',
                                                            'normal' => '#10b981',
                                                            'low' => '#6b7280'
                                                        ];
                                                        $urgencyLabels = [
                                                            'urgent' => 'Dringend',
                                                            'high' => 'Hoch',
                                                            'normal' => 'Normal',
                                                            'low' => 'Niedrig'
                                                        ];
                                                        $urgencyKey = strtolower($urgency);
                                                        $color = $urgencyColors[$urgencyKey] ?? '#6b7280';
                                                        $label = $urgencyLabels[$urgencyKey] ?? $urgency;
                                                    @endphp
                                                    <span style="background-color: {{ $color }}; color: #ffffff; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; margin-left: 8px;">{{ $label }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($summary)
                            <!-- Summary Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="background-color: #f8fafc; border-radius: 8px; padding: 24px;">
                                        <h3 style="margin: 0 0 16px 0; color: #1e293b; font-size: 18px; font-weight: 600;">
                                            <span style="font-size: 20px; margin-right: 8px;">📋</span>
                                            Zusammenfassung
                                        </h3>
                                        <p style="margin: 0; color: #475569; font-size: 15px; line-height: 1.6;">{{ $summary }}</p>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($appointmentInfo)
                            <!-- Appointment Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="background-color: #fef3c7; border-radius: 8px; padding: 24px; border-left: 4px solid #f59e0b;">
                                        <h3 style="margin: 0 0 16px 0; color: #92400e; font-size: 18px; font-weight: 600;">
                                            <span style="font-size: 20px; margin-right: 8px;">📅</span>
                                            Terminanfrage
                                        </h3>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 4px 0;">
                                                    <strong style="color: #92400e; font-size: 14px;">Datum:</strong>
                                                    <span style="color: #451a03; font-size: 14px; margin-left: 8px;">{{ $appointmentInfo['date'] ?? 'Nicht angegeben' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0;">
                                                    <strong style="color: #92400e; font-size: 14px;">Uhrzeit:</strong>
                                                    <span style="color: #451a03; font-size: 14px; margin-left: 8px;">{{ $appointmentInfo['time'] ?? 'Nicht angegeben' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0;">
                                                    <strong style="color: #92400e; font-size: 14px;">Dienstleistung:</strong>
                                                    <span style="color: #451a03; font-size: 14px; margin-left: 8px;">{{ $appointmentInfo['service'] ?? 'Nicht angegeben' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0 0 0;">
                                                    @if($appointmentInfo['made'])
                                                        <span style="background-color: #10b981; color: #ffffff; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;">✅ Termin vereinbart</span>
                                                    @else
                                                        <span style="background-color: #f59e0b; color: #ffffff; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;">⏳ Noch offen</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            
                            @if($includeOptions['transcript'] ?? false)
                            <!-- Transcript Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="background-color: #f8fafc; border-radius: 8px; padding: 24px;">
                                        <h3 style="margin: 0 0 16px 0; color: #1e293b; font-size: 18px; font-weight: 600;">
                                            <span style="font-size: 20px; margin-right: 8px;">💬</span>
                                            Transkript
                                        </h3>
                                        <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px;">
                                            @if($call->transcript)
                                                @foreach(preg_split('/\r\n|\r|\n/', $call->transcript) as $line)
                                                    @if(trim($line))
                                                        @php
                                                            $isAgent = stripos($line, 'agent:') !== false || stripos($line, 'ai:') !== false;
                                                        @endphp
                                                        <div style="margin-bottom: 12px; padding: 12px; background-color: {{ $isAgent ? '#eff6ff' : '#f3f4f6' }}; border-radius: 6px; {{ $isAgent ? 'margin-left: 40px;' : 'margin-right: 40px;' }}">
                                                            <p style="margin: 0; color: #374151; font-size: 14px; line-height: 1.5;">{{ $line }}</p>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @else
                                                <p style="margin: 0; color: #6b7280; font-size: 14px; text-align: center;">Kein Transkript verfügbar</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            <!-- Audio Link -->
                            @if($call->recording_url || (isset($call->webhook_data['recording_url']) && $call->webhook_data['recording_url']))
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="background-color: #f3f4f6; border-radius: 8px; padding: 20px; text-align: center;">
                                        <p style="margin: 0 0 10px 0; color: #374151; font-size: 14px;">
                                            <span style="font-size: 20px; margin-right: 8px;">🎧</span>
                                            Aufzeichnung verfügbar
                                        </p>
                                        <a href="https://api.askproai.de/business/calls/{{ $call->id }}/v2#audio" style="display: inline-block; padding: 10px 20px; background-color: #6b7280; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 14px;">
                                            Aufzeichnung im Portal anhören
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            
                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top: 40px;">
                                <tr>
                                    <td align="center">
                                        <a href="https://api.askproai.de/business/calls/{{ $call->id }}/v2" style="display: inline-block; padding: 14px 32px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                            Anruf im Portal anzeigen
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">
                                Diese E-Mail wurde automatisch von AskProAI generiert.
                            </p>
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">
                                Bei Fragen wenden Sie sich bitte an 
                                <a href="mailto:fabian@askproai.de" style="color: #3b82f6; text-decoration: none;">fabian@askproai.de</a>
                            </p>
                            <p style="margin: 20px 0 0 0; color: #9ca3af; font-size: 12px;">
                                <a href="https://askproai.de" style="color: #3b82f6; text-decoration: none;">askproai.de</a> • 
                                <a href="https://askproai.de/datenschutz" style="color: #3b82f6; text-decoration: none;">Datenschutz</a> • 
                                <a href="https://askproai.de/impressum" style="color: #3b82f6; text-decoration: none;">Impressum</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>