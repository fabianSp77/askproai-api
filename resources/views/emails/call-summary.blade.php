<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anrufzusammenfassung - {{ $call->customer?->name ?? 'Neuer Anruf' }}</title>
    <style>
        /* Reset and Base Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f5f5;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Container */
        .email-wrapper {
            width: 100%;
            background-color: #f5f5f5;
            padding: 40px 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .logo {
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .header .subtitle {
            color: #e0e7ff;
            font-size: 16px;
            margin-top: 8px;
        }
        
        /* Urgency Banner */
        .urgency-banner {
            padding: 15px 30px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
        }
        
        .urgency-urgent {
            background-color: #dc2626;
            color: white;
        }
        
        .urgency-high {
            background-color: #f59e0b;
            color: white;
        }
        
        /* Content */
        .content {
            padding: 40px 30px;
        }
        
        /* Call Header Info */
        .call-header {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        
        .call-header-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .call-info-item {
            display: flex;
            align-items: start;
        }
        
        .call-info-icon {
            width: 40px;
            height: 40px;
            background-color: #e0e7ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .call-info-content h3 {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .call-info-content p {
            margin: 4px 0 0;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }
        
        /* Customer Card */
        .customer-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #fcd34d;
        }
        
        .customer-card h2 {
            margin: 0 0 12px;
            font-size: 18px;
            color: #92400e;
            display: flex;
            align-items: center;
        }
        
        .customer-details {
            display: grid;
            gap: 8px;
        }
        
        .customer-detail {
            display: flex;
            align-items: center;
            color: #92400e;
        }
        
        .customer-detail-icon {
            margin-right: 8px;
            opacity: 0.7;
        }
        
        /* Summary Section */
        .summary-card {
            background-color: #ffffff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .summary-card h2 {
            margin: 0 0 16px;
            font-size: 20px;
            color: #111827;
            display: flex;
            align-items: center;
        }
        
        .summary-content {
            color: #4b5563;
            font-size: 16px;
            line-height: 1.8;
        }
        
        /* Action Items */
        .action-items {
            margin-bottom: 30px;
        }
        
        .action-header {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 16px;
            display: flex;
            align-items: center;
        }
        
        .action-item {
            background-color: #ffffff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: start;
            transition: all 0.2s;
        }
        
        .action-item.priority-urgent {
            border-color: #dc2626;
            background-color: #fef2f2;
        }
        
        .action-item.priority-high {
            border-color: #f59e0b;
            background-color: #fffbeb;
        }
        
        .action-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
            font-size: 20px;
        }
        
        .action-icon.urgent {
            background-color: #fecaca;
        }
        
        .action-icon.high {
            background-color: #fed7aa;
        }
        
        .action-content h3 {
            margin: 0 0 4px;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }
        
        .action-content p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }
        
        /* CTA Buttons */
        .cta-section {
            text-align: center;
            margin: 40px 0;
        }
        
        .cta-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #1e40af;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.2s;
        }
        
        .cta-button:hover {
            background-color: #1e3a8a;
        }
        
        .cta-secondary {
            display: inline-block;
            margin-top: 12px;
            color: #6b7280;
            text-decoration: underline;
            font-size: 14px;
        }
        
        /* Footer */
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-logo {
            margin-bottom: 16px;
        }
        
        .footer-links {
            margin-bottom: 20px;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            margin: 0 12px;
            font-size: 14px;
        }
        
        .footer-text {
            color: #9ca3af;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .footer-contact {
            margin-top: 16px;
            color: #6b7280;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .email-wrapper {
                padding: 20px 0;
            }
            
            .email-container {
                border-radius: 0;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .call-header-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-button {
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                <h1>{{ $company->name }}</h1>
                <div class="subtitle">Anrufzusammenfassung vom {{ $call->created_at->format('d.m.Y') }}</div>
            </div>
            
            <!-- Urgency Banner -->
            @if($urgencyLevel === 'urgent' || $urgencyLevel === 'dringend')
            <div class="urgency-banner urgency-urgent">
                ⚡ DRINGEND - Sofortige Bearbeitung erforderlich
            </div>
            @elseif($urgencyLevel === 'high' || $urgencyLevel === 'hoch')
            <div class="urgency-banner urgency-high">
                ⚠️ HOHE PRIORITÄT - Zeitnahe Bearbeitung empfohlen
            </div>
            @endif
            
            <!-- Content -->
            <div class="content">
                <!-- Custom Message -->
                @if($customMessage)
                <div style="background-color: #dbeafe; border-left: 4px solid #2563eb; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
                    <p style="margin: 0; color: #1e40af;">{{ $customMessage }}</p>
                </div>
                @endif
                
                <!-- Call Header Info -->
                <div class="call-header">
                    <div class="call-header-grid">
                        <div class="call-info-item">
                            <div class="call-info-icon">📅</div>
                            <div class="call-info-content">
                                <h3>Datum & Uhrzeit</h3>
                                <p>{{ $call->created_at->format('d.m.Y, H:i') }} Uhr</p>
                            </div>
                        </div>
                        <div class="call-info-item">
                            <div class="call-info-icon">⏱️</div>
                            <div class="call-info-content">
                                <h3>Gesprächsdauer</h3>
                                <p>{{ $callDuration }}</p>
                            </div>
                        </div>
                        @if($call->branch)
                        <div class="call-info-item">
                            <div class="call-info-icon">🏢</div>
                            <div class="call-info-content">
                                <h3>Filiale</h3>
                                <p>{{ $call->branch->name }}</p>
                            </div>
                        </div>
                        @endif
                        <div class="call-info-item">
                            <div class="call-info-icon">📞</div>
                            <div class="call-info-content">
                                <h3>Anruf-ID</h3>
                                <p>#{{ $call->id }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information -->
                @if($call->customer || $call->from_number)
                <div class="customer-card">
                    <h2>👤 Kundeninformationen</h2>
                    <div class="customer-details">
                        @if($call->customer?->name)
                        <div class="customer-detail">
                            <span class="customer-detail-icon">👤</span>
                            <strong>{{ $call->customer->name }}</strong>
                        </div>
                        @endif
                        
                        <div class="customer-detail">
                            <span class="customer-detail-icon">📱</span>
                            {{ $call->from_number ?? $call->phone_number ?? 'Unbekannt' }}
                        </div>
                        
                        @if($call->customer?->email)
                        <div class="customer-detail">
                            <span class="customer-detail-icon">✉️</span>
                            {{ $call->customer->email }}
                        </div>
                        @endif
                        
                        @if($call->customer?->company_name)
                        <div class="customer-detail">
                            <span class="customer-detail-icon">🏢</span>
                            {{ $call->customer->company_name }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                
                <!-- Summary -->
                <div class="summary-card">
                    <h2>📋 Gesprächszusammenfassung</h2>
                    <div class="summary-content">
                        {!! nl2br(e($call->summary ?? $call->call_summary ?? 'Keine Zusammenfassung verfügbar.')) !!}
                    </div>
                    
                    @if($hasAppointment)
                    <div style="margin-top: 16px; padding: 12px; background-color: #d1fae5; border-radius: 6px; color: #065f46; font-weight: 600;">
                        ✅ Termin wurde erfolgreich gebucht
                    </div>
                    @endif
                </div>
                
                <!-- Extracted Information -->
                @if($call->custom_analysis_data && count(array_filter($call->custom_analysis_data)) > 0)
                <div class="summary-card">
                    <h2>📊 Weitere erfasste Informationen</h2>
                    <table style="width: 100%; border-collapse: collapse;">
                        @foreach($call->custom_analysis_data as $key => $value)
                        @if($value && !in_array($key, ['urgency_level']))
                        <tr>
                            <td style="padding: 12px 16px 12px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-weight: 500; vertical-align: top; width: 40%;">
                                {{ str_replace('_', ' ', ucfirst($key)) }}
                            </td>
                            <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: #111827;">
                                {{ is_array($value) ? implode(', ', $value) : $value }}
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </table>
                </div>
                @endif
                
                <!-- Action Items -->
                @if(count($actionItems) > 0)
                <div class="action-items">
                    <h2 class="action-header">🎯 Erforderliche Maßnahmen</h2>
                    @foreach($actionItems as $item)
                    <div class="action-item priority-{{ $item['priority'] }}">
                        <div class="action-icon {{ $item['priority'] }}">
                            @if($item['type'] === 'appointment_needed')
                                📅
                            @elseif($item['type'] === 'callback_needed')
                                📞
                            @elseif($item['type'] === 'urgent_followup')
                                ⚡
                            @else
                                ✏️
                            @endif
                        </div>
                        <div class="action-content">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                <!-- CTA Section -->
                <div class="cta-section">
                    <a href="https://askproai.de/business/calls/{{ $call->id }}/v2" class="cta-button">
                        Anruf im Business Portal ansehen
                    </a>
                    <br>
                    <a href="https://askproai.de/business/customers" class="cta-secondary">
                        Zur Kundenübersicht
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <div class="footer-logo">
                    <strong>AskProAI</strong>
                </div>
                
                <div class="footer-links">
                    <a href="https://askproai.de">Website</a>
                    <a href="https://askproai.de/business">Business Portal</a>
                    <a href="https://askproai.de/hilfe">Hilfe</a>
                </div>
                
                <div class="footer-text">
                    Diese E-Mail wurde automatisch generiert von {{ $company->name }}.<br>
                    Powered by AskProAI - Ihre KI-gestützte Telefonassistenz
                </div>
                
                <div class="footer-contact">
                    Bei Fragen kontaktieren Sie uns: <a href="mailto:fabian@askproai.de" style="color: #1e40af;">fabian@askproai.de</a>
                </div>
                
                @if($recipientType === 'external')
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 12px;">
                    Diese E-Mail enthält vertrauliche Informationen. Wenn Sie nicht der beabsichtigte Empfänger sind, 
                    löschen Sie bitte diese E-Mail und benachrichtigen Sie den Absender.
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>