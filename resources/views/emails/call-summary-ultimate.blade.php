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
            .metadata-cell { display: block !important; width: 100% !important; text-align: left !important; padding: 10px 0 !important; }
            .metadata-separator { display: none !important; }
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
            <td align="center" style="padding: 0;">
                
                <!-- AskProAI Service Banner -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #0f172a;">
                    <tr>
                        <td align="center" style="padding: 12px 0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="https://askproai.de" style="text-decoration: none; display: inline-block;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td style="vertical-align: middle;">
                                                        <span style="color: #94a3b8; font-size: 13px; font-weight: 400;">
                                                            ✨ Ein Service von
                                                        </span>
                                                        <span style="color: #3b82f6; font-size: 14px; font-weight: 600; margin-left: 4px;">
                                                            AskProAI.de
                                                        </span>
                                                        <span style="color: #64748b; font-size: 13px; margin-left: 4px;">
                                                            - KI-gestützte Anrufverwaltung
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
            </td>
        </tr>
        <tr>
            <td align="center" style="padding: 30px 0 40px 0;">
                
                <!-- Email Container -->
                <table class="container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    
                    <!-- Premium Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 25px 30px;">
                            <!-- Company Info -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center;">
                                        <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">{{ $call->company->name ?? 'AskProAI' }}</h1>
                                        <p style="margin: 5px 0 0 0; color: #e0e7ff; font-size: 13px;">
                                            KI-Anruf weitergeleitet an {{ $call->from_number }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Portal Quick Links Section -->
                    <tr>
                        <td style="background-color: #1e293b; padding: 15px 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 10px 0; color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; text-align: center;">
                                            IM PORTAL VERFÜGBAR
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <!-- Button Container -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <!-- Details Button -->
                                                <td style="padding: 0 5px;">
                                                    <a href="https://api.askproai.de/business/calls/{{ $call->id }}/v2" 
                                                       style="display: inline-block; padding: 8px 16px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 12px; text-align: center; white-space: nowrap;">
                                                        📞 Details
                                                    </a>
                                                </td>
                                                
                                                @if($call->recording_url || (isset($call->webhook_data['recording_url']) && $call->webhook_data['recording_url']))
                                                <!-- Audio Button -->
                                                <td style="padding: 0 5px;">
                                                    <a href="https://api.askproai.de/business/calls/{{ $call->id }}/v2#audio" 
                                                       style="display: inline-block; padding: 8px 16px; background-color: #475569; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 12px; text-align: center; white-space: nowrap;">
                                                        🎧 Audio
                                                    </a>
                                                </td>
                                                @endif
                                                
                                                @if($includeOptions['attachCSV'] ?? false)
                                                <!-- CSV Button -->
                                                <td style="padding: 0 5px;">
                                                    <a href="https://api.askproai.de/business/download/csv/{{ $csvDownloadToken }}" 
                                                       style="display: inline-block; padding: 8px 16px; background-color: #475569; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: 600; font-size: 12px; text-align: center; white-space: nowrap;">
                                                        📊 CSV
                                                    </a>
                                                </td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Call Metrics Section -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; border-bottom: 1px solid #e5e7eb;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 10px;">
                                        <p style="margin: 0; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                                            ANRUF-DETAILS
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <!-- Date & Time -->
                                                <td class="metadata-cell" style="text-align: left; padding-right: 20px;">
                                                    <p style="margin: 0; font-size: 11px; color: #94a3b8;">Datum</p>
                                                    <p style="margin: 2px 0 0 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                                                        {{ $call->created_at->format('d.m.Y') }}
                                                    </p>
                                                </td>
                                                
                                                <td class="metadata-separator" style="width: 1px; background-color: #e2e8f0;">&nbsp;</td>
                                                
                                                <!-- Time -->
                                                <td class="metadata-cell" style="text-align: center; padding: 0 20px;">
                                                    <p style="margin: 0; font-size: 11px; color: #94a3b8;">Uhrzeit</p>
                                                    <p style="margin: 2px 0 0 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                                                        {{ $call->created_at->format('H:i') }} Uhr
                                                    </p>
                                                </td>
                                                
                                                <td class="metadata-separator" style="width: 1px; background-color: #e2e8f0;">&nbsp;</td>
                                                
                                                <!-- Duration -->
                                                <td class="metadata-cell" style="text-align: center; padding: 0 20px;">
                                                    <p style="margin: 0; font-size: 11px; color: #94a3b8;">Dauer</p>
                                                    <p style="margin: 2px 0 0 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                                                        {{ gmdate('i:s', $call->duration_sec ?? 0) }}
                                                    </p>
                                                </td>
                                                
                                                @if(isset($urgency) && $urgency)
                                                <td class="metadata-separator" style="width: 1px; background-color: #e2e8f0;">&nbsp;</td>
                                                
                                                <!-- Priority -->
                                                <td class="metadata-cell" style="text-align: right; padding-left: 20px;">
                                                    @php
                                                        $urgencyColors = [
                                                            'urgent' => '#dc2626',
                                                            'high' => '#f97316',
                                                            'normal' => '#10b981',
                                                            'low' => '#6b7280'
                                                        ];
                                                        $urgencyLabels = [
                                                            'urgent' => 'DRINGEND',
                                                            'high' => 'HOCH',
                                                            'normal' => 'NORMAL',
                                                            'low' => 'NIEDRIG'
                                                        ];
                                                        $urgencyKey = strtolower($urgency);
                                                        $color = $urgencyColors[$urgencyKey] ?? '#6b7280';
                                                        $label = $urgencyLabels[$urgencyKey] ?? strtoupper($urgency);
                                                    @endphp
                                                    <p style="margin: 0; font-size: 11px; color: #94a3b8;">Priorität</p>
                                                    <p style="margin: 2px 0 0 0; font-size: 14px; color: {{ $color }}; font-weight: 700;">
                                                        {{ $label }}
                                                    </p>
                                                </td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td class="content" style="padding: 30px;">
                            
                            <!-- Section Title -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0; color: #1e293b; font-size: 18px; font-weight: 700;">
                                            Gesprächsinformationen
                                        </h2>
                                        <div style="width: 40px; height: 3px; background-color: #3b82f6; margin-top: 10px;"></div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Custom Content at Top -->
                            @if($customContent && trim($customContent))
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td style="background-color: #eff6ff; border-radius: 8px; padding: 18px; border-left: 3px solid #3b82f6;">
                                        {!! $customContent !!}
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($translatedCustomerRequest)
                            <!-- Customer Request -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 12px 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                                            KUNDENANLIEGEN
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #eff6ff; border-radius: 6px; border-left: 3px solid #3b82f6;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <p style="margin: 0; color: #1e293b; font-size: 15px; line-height: 1.6; font-weight: 500;">
                                                        {{ $translatedCustomerRequest }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($customerInfo)
                            <!-- Customer Info -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 12px 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                                            KONTAKTDATEN
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="50%" style="padding: 0 10px 8px 0;">
                                                                <p style="margin: 0; color: #94a3b8; font-size: 11px;">Name</p>
                                                                <p style="margin: 2px 0 0 0; color: #1e293b; font-size: 14px; font-weight: 600;">{{ $customerInfo['name'] }}</p>
                                                            </td>
                                                            <td width="50%" style="padding: 0 0 8px 10px;">
                                                                <p style="margin: 0; color: #94a3b8; font-size: 11px;">Telefon</p>
                                                                <p style="margin: 2px 0 0 0; color: #1e293b; font-size: 14px; font-weight: 600;">{{ $customerInfo['phone'] }}</p>
                                                            </td>
                                                        </tr>
                                                        @if($customerInfo['email'] || $customerInfo['company'])
                                                        <tr>
                                                            @if($customerInfo['email'])
                                                            <td width="50%" style="padding: 8px 10px 0 0; border-top: 1px solid #f1f5f9;">
                                                                <p style="margin: 0; color: #94a3b8; font-size: 11px;">E-Mail</p>
                                                                <p style="margin: 2px 0 0 0; color: #1e293b; font-size: 14px;">{{ $customerInfo['email'] }}</p>
                                                            </td>
                                                            @endif
                                                            @if($customerInfo['company'])
                                                            <td width="50%" style="padding: 8px 0 0 10px; border-top: 1px solid #f1f5f9;">
                                                                <p style="margin: 0; color: #94a3b8; font-size: 11px;">Firma</p>
                                                                <p style="margin: 2px 0 0 0; color: #1e293b; font-size: 14px;">{{ $customerInfo['company'] }}</p>
                                                            </td>
                                                            @endif
                                                        </tr>
                                                        @endif
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($translatedSummary ?? $summary)
                            <!-- Summary -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 12px 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                                            ZUSAMMENFASSUNG
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <p style="margin: 0; color: #334155; font-size: 14px; line-height: 1.6;">
                                                        {{ $translatedSummary ?? $summary }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($appointmentInfo)
                            <!-- Appointment Info -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 12px 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                                            TERMINANFRAGE
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #fef3c7; border-radius: 6px; border-left: 3px solid #f59e0b;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="33%">
                                                                <p style="margin: 0; color: #92400e; font-size: 11px;">Datum</p>
                                                                <p style="margin: 2px 0 0 0; color: #451a03; font-size: 14px; font-weight: 600;">{{ $appointmentInfo['date'] ?? 'Offen' }}</p>
                                                            </td>
                                                            <td width="33%">
                                                                <p style="margin: 0; color: #92400e; font-size: 11px;">Uhrzeit</p>
                                                                <p style="margin: 2px 0 0 0; color: #451a03; font-size: 14px; font-weight: 600;">{{ $appointmentInfo['time'] ?? 'Offen' }}</p>
                                                            </td>
                                                            <td width="34%">
                                                                <p style="margin: 0; color: #92400e; font-size: 11px;">Status</p>
                                                                <p style="margin: 2px 0 0 0; color: #451a03; font-size: 14px; font-weight: 600;">
                                                                    {{ $appointmentInfo['made'] ? '✅ Gebucht' : '⏳ Offen' }}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                        @if($appointmentInfo['service'])
                                                        <tr>
                                                            <td colspan="3" style="padding-top: 12px; border-top: 1px solid #fed7aa;">
                                                                <p style="margin: 0; color: #92400e; font-size: 11px;">Dienstleistung</p>
                                                                <p style="margin: 2px 0 0 0; color: #451a03; font-size: 14px;">{{ $appointmentInfo['service'] }}</p>
                                                            </td>
                                                        </tr>
                                                        @endif
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($includeOptions['transcript'] ?? false)
                            <!-- Transcript -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 12px 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                                            GESPRÄCHSVERLAUF
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    @if($call->transcript)
                                                        @foreach(preg_split('/\r\n|\r|\n/', $call->transcript) as $line)
                                                            @if(trim($line))
                                                                @php
                                                                    $isAgent = stripos($line, 'agent:') !== false || stripos($line, 'ai:') !== false;
                                                                    $cleanLine = preg_replace('/^(Agent:|User:|AI:)\s*/i', '', $line);
                                                                @endphp
                                                                <div style="margin-bottom: 10px; padding: 10px 14px; background-color: {{ $isAgent ? '#ffffff' : '#e0e7ff' }}; border-radius: 6px; {{ $isAgent ? 'margin-left: 20px; border: 1px solid #e2e8f0;' : 'margin-right: 20px;' }}">
                                                                    <p style="margin: 0 0 3px 0; color: {{ $isAgent ? '#64748b' : '#3b82f6' }}; font-size: 10px; text-transform: uppercase; font-weight: 600;">
                                                                        {{ $isAgent ? 'AGENT' : 'KUNDE' }}
                                                                    </p>
                                                                    <p style="margin: 0; color: #1e293b; font-size: 13px; line-height: 1.5;">
                                                                        {{ $cleanLine }}
                                                                    </p>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <p style="margin: 0; color: #6b7280; font-size: 13px; text-align: center;">Kein Transkript verfügbar</p>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 25px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 8px 0; color: #6b7280; font-size: 13px;">
                                Diese E-Mail wurde automatisch von AskProAI generiert.
                            </p>
                            <p style="margin: 0 0 8px 0; color: #6b7280; font-size: 13px;">
                                Bei Fragen wenden Sie sich bitte an 
                                <a href="mailto:{{ $call->company->email ?? 'support@askproai.de' }}" style="color: #3b82f6; text-decoration: none;">{{ $call->company->email ?? 'support@askproai.de' }}</a>
                            </p>
                            <p style="margin: 15px 0 0 0; color: #9ca3af; font-size: 11px;">
                                {{ $call->company->name ?? 'AskProAI' }} • 
                                <a href="https://askproai.de" style="color: #3b82f6; text-decoration: none;">askproai.de</a> • 
                                <a href="https://askproai.de/datenschutz" style="color: #3b82f6; text-decoration: none;">Datenschutz</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>