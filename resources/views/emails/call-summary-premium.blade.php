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
            .action-buttons { flex-direction: column !important; }
            .action-button { width: 100% !important; margin-bottom: 10px !important; }
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
                    
                    <!-- Premium Header with Quick Actions -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 30px;">
                            <!-- Company Info -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center; padding-bottom: 20px;">
                                        <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700;">{{ $call->company->name ?? 'AskProAI' }}</h1>
                                        <p style="margin: 5px 0 0 0; color: #e0e7ff; font-size: 14px;">
                                            Anruf weitergeleitet an {{ $call->from_number }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Quick Action Buttons -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top: 20px;">
                                <tr>
                                    <td align="center">
                                        <!-- Button Container -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <!-- Details Button -->
                                                <td style="padding: 0 5px;">
                                                    <a href="https://api.askproai.de/business/calls/{{ $call->id }}/v2" 
                                                       style="display: inline-block; padding: 10px 18px; background-color: #ffffff; color: #1e40af; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px; text-align: center; white-space: nowrap;">
                                                        📞 Details
                                                    </a>
                                                </td>
                                                
                                                @if($call->recording_url || (isset($call->webhook_data['recording_url']) && $call->webhook_data['recording_url']))
                                                <!-- Audio Button -->
                                                <td style="padding: 0 5px;">
                                                    <a href="https://api.askproai.de/business/calls/{{ $call->id }}/v2#audio" 
                                                       style="display: inline-block; padding: 10px 18px; background-color: rgba(255, 255, 255, 0.2); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px; border: 2px solid rgba(255, 255, 255, 0.3); text-align: center; white-space: nowrap;">
                                                        🎧 Audio
                                                    </a>
                                                </td>
                                                @endif
                                                
                                                @if($includeOptions['attachCSV'] ?? false)
                                                <!-- CSV Button -->
                                                <td style="padding: 0 5px;">
                                                    <a href="https://api.askproai.de/business/download/csv/{{ $csvDownloadToken }}" 
                                                       style="display: inline-block; padding: 10px 18px; background-color: rgba(255, 255, 255, 0.2); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px; border: 2px solid rgba(255, 255, 255, 0.3); text-align: center; white-space: nowrap;">
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

                    <!-- Call Metadata Bar -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; border-bottom: 1px solid #e5e7eb;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td width="33%" style="text-align: center;">
                                        <p style="margin: 0; font-size: 12px; color: #64748b;">DATUM & ZEIT</p>
                                        <p style="margin: 5px 0 0 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                                            {{ $call->created_at->format('d.m.Y') }}<br>
                                            {{ $call->created_at->format('H:i') }} Uhr
                                        </p>
                                    </td>
                                    <td width="33%" style="text-align: center; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
                                        <p style="margin: 0; font-size: 12px; color: #64748b;">DAUER</p>
                                        <p style="margin: 5px 0 0 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                                            {{ gmdate('i:s', $call->duration_sec ?? 0) }} Min
                                        </p>
                                    </td>
                                    <td width="33%" style="text-align: center;">
                                        @if(isset($urgency) && $urgency)
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
                                            <p style="margin: 0; font-size: 12px; color: #64748b;">PRIORITÄT</p>
                                            <p style="margin: 5px 0 0 0; font-size: 14px; color: {{ $color }}; font-weight: 700;">
                                                {{ $label }}
                                            </p>
                                        @else
                                            <p style="margin: 0; font-size: 12px; color: #64748b;">STATUS</p>
                                            <p style="margin: 5px 0 0 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                                                Bearbeitet
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="content" style="padding: 40px 30px;">
                            
                            <!-- Custom Content at Top -->
                            @if($customContent && trim($customContent))
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="background-color: #eff6ff; border-radius: 8px; padding: 20px; border-left: 4px solid #3b82f6;">
                                        {!! $customContent !!}
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($customerInfo)
                            <!-- Customer Info Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Anruferinformationen
                                        </h3>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; padding: 20px;">
                                            <tr>
                                                <td>
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="50%" style="padding-bottom: 10px;">
                                                                <p style="margin: 0; color: #64748b; font-size: 12px; text-transform: uppercase;">Name</p>
                                                                <p style="margin: 3px 0 0 0; color: #1e293b; font-size: 15px; font-weight: 600;">{{ $customerInfo['name'] }}</p>
                                                            </td>
                                                            <td width="50%" style="padding-bottom: 10px;">
                                                                <p style="margin: 0; color: #64748b; font-size: 12px; text-transform: uppercase;">Telefon</p>
                                                                <p style="margin: 3px 0 0 0; color: #1e293b; font-size: 15px; font-weight: 600;">{{ $customerInfo['phone'] }}</p>
                                                            </td>
                                                        </tr>
                                                        @if($customerInfo['email'] || $customerInfo['company'])
                                                        <tr>
                                                            @if($customerInfo['email'])
                                                            <td width="50%" style="padding-top: 10px;">
                                                                <p style="margin: 0; color: #64748b; font-size: 12px; text-transform: uppercase;">E-Mail</p>
                                                                <p style="margin: 3px 0 0 0; color: #1e293b; font-size: 15px;">{{ $customerInfo['email'] }}</p>
                                                            </td>
                                                            @endif
                                                            @if($customerInfo['company'])
                                                            <td width="50%" style="padding-top: 10px;">
                                                                <p style="margin: 0; color: #64748b; font-size: 12px; text-transform: uppercase;">Firma</p>
                                                                <p style="margin: 3px 0 0 0; color: #1e293b; font-size: 15px;">{{ $customerInfo['company'] }}</p>
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
                            
                            @if($summary)
                            <!-- Summary Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Zusammenfassung
                                        </h3>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                                            <tr>
                                                <td>
                                                    <p style="margin: 0; color: #475569; font-size: 15px; line-height: 1.6;">{{ $summary }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            @if($appointmentInfo)
                            <!-- Appointment Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Terminanfrage
                                        </h3>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #fef3c7; border-radius: 8px; padding: 20px; border-left: 4px solid #f59e0b;">
                                            <tr>
                                                <td>
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="33%">
                                                                <p style="margin: 0; color: #92400e; font-size: 12px; text-transform: uppercase;">Datum</p>
                                                                <p style="margin: 3px 0 0 0; color: #451a03; font-size: 15px; font-weight: 600;">{{ $appointmentInfo['date'] ?? 'Offen' }}</p>
                                                            </td>
                                                            <td width="33%">
                                                                <p style="margin: 0; color: #92400e; font-size: 12px; text-transform: uppercase;">Uhrzeit</p>
                                                                <p style="margin: 3px 0 0 0; color: #451a03; font-size: 15px; font-weight: 600;">{{ $appointmentInfo['time'] ?? 'Offen' }}</p>
                                                            </td>
                                                            <td width="34%">
                                                                <p style="margin: 0; color: #92400e; font-size: 12px; text-transform: uppercase;">Status</p>
                                                                <p style="margin: 3px 0 0 0; color: #451a03; font-size: 15px; font-weight: 600;">
                                                                    {{ $appointmentInfo['made'] ? '✅ Gebucht' : '⏳ Offen' }}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                        @if($appointmentInfo['service'])
                                                        <tr>
                                                            <td colspan="3" style="padding-top: 15px;">
                                                                <p style="margin: 0; color: #92400e; font-size: 12px; text-transform: uppercase;">Dienstleistung</p>
                                                                <p style="margin: 3px 0 0 0; color: #451a03; font-size: 15px;">{{ $appointmentInfo['service'] }}</p>
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
                            <!-- Transcript Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 25px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Gesprächsverlauf
                                        </h3>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; padding: 20px;">
                                            <tr>
                                                <td>
                                                    @if($call->transcript)
                                                        @foreach(preg_split('/\r\n|\r|\n/', $call->transcript) as $line)
                                                            @if(trim($line))
                                                                @php
                                                                    $isAgent = stripos($line, 'agent:') !== false || stripos($line, 'ai:') !== false;
                                                                @endphp
                                                                <div style="margin-bottom: 12px; padding: 12px 16px; background-color: {{ $isAgent ? '#ffffff' : '#e0e7ff' }}; border-radius: 8px; {{ $isAgent ? 'margin-left: 20px; border: 1px solid #e2e8f0;' : 'margin-right: 20px; background-color: #eff6ff;' }}">
                                                                    <p style="margin: 0; color: {{ $isAgent ? '#64748b' : '#1e40af' }}; font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">
                                                                        {{ $isAgent ? 'AGENT' : 'KUNDE' }}
                                                                    </p>
                                                                    <p style="margin: 0; color: #1e293b; font-size: 14px; line-height: 1.5;">
                                                                        {{ preg_replace('/^(Agent:|User:|AI:)\s*/i', '', $line) }}
                                                                    </p>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <p style="margin: 0; color: #6b7280; font-size: 14px; text-align: center;">Kein Transkript verfügbar</p>
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
                        <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">
                                Diese E-Mail wurde automatisch von AskProAI generiert.
                            </p>
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">
                                Bei Fragen wenden Sie sich bitte an 
                                <a href="mailto:{{ $call->company->email ?? 'support@askproai.de' }}" style="color: #3b82f6; text-decoration: none;">{{ $call->company->email ?? 'support@askproai.de' }}</a>
                            </p>
                            <p style="margin: 20px 0 0 0; color: #9ca3af; font-size: 12px;">
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