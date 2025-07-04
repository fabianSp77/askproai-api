<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'de' ? 'Terminänderung' : 'Appointment Rescheduled' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #F59E0B;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .appointment-box {
            background-color: #FEF3C7;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #F59E0B;
        }
        .old-appointment-box {
            background-color: #F3F4F6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #9CA3AF;
        }
        .appointment-detail {
            margin: 10px 0;
            display: flex;
            align-items: flex-start;
        }
        .appointment-detail-label {
            font-weight: 600;
            min-width: 120px;
            color: #666;
        }
        .appointment-detail-value {
            flex: 1;
            color: #333;
        }
        .strikethrough {
            text-decoration: line-through;
            color: #9CA3AF;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s;
        }
        .button-primary {
            background-color: {{ $company->metadata['brand_color'] ?? '#3B82F6' }};
            color: white;
        }
        .button-primary:hover {
            background-color: {{ $company->metadata['brand_color_dark'] ?? '#2563EB' }};
        }
        .button-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        .button-secondary:hover {
            background-color: #d1d5db;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .footer a {
            color: {{ $company->metadata['brand_color'] ?? '#3B82F6' }};
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .appointment-detail {
                flex-direction: column;
            }
            .appointment-detail-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($company->logo)
        <div class="header" style="background-image: url('cid:logo.png'); background-size: contain; background-repeat: no-repeat; background-position: center;">
            <h1 style="opacity: 0;">{{ $company->name }}</h1>
        </div>
        @else
        <div class="header">
            <h1>{{ $company->name }}</h1>
        </div>
        @endif
        
        <div class="content">
            @if($locale === 'de')
                <h2>Hallo {{ $customer->first_name }},</h2>
                
                <p>Ihr Termin wurde erfolgreich verschoben. Bitte beachten Sie die neuen Termindetails:</p>
                
                <div class="appointment-box">
                    <h3 style="margin-top: 0; color: #333;">🗓️ Neuer Termin</h3>
                    
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Datum:</span>
                        <span class="appointment-detail-value">
                            <strong>{{ $appointment->starts_at->locale($locale)->isoFormat('dddd, D. MMMM YYYY') }}</strong>
                        </span>
                    </div>
                    
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Uhrzeit:</span>
                        <span class="appointment-detail-value">
                            <strong>{{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }} Uhr</strong>
                        </span>
                    </div>
                    
                    @if($service)
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Leistung:</span>
                        <span class="appointment-detail-value">{{ $service->name }}</span>
                    </div>
                    @endif
                    
                    @if($staff)
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Mitarbeiter:</span>
                        <span class="appointment-detail-value">{{ $staff->first_name }} {{ $staff->last_name }}</span>
                    </div>
                    @endif
                    
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Standort:</span>
                        <span class="appointment-detail-value">
                            {{ $branch->name }}<br>
                            {{ $branch->address }}
                        </span>
                    </div>
                </div>
                
                <div class="old-appointment-box">
                    <p style="margin: 0;"><strong>Alter Termin (wurde verschoben):</strong><br>
                    <span class="strikethrough">{{ $oldStartTime->locale($locale)->isoFormat('dddd, D. MMMM YYYY') }} um {{ $oldStartTime->format('H:i') }} Uhr</span></p>
                </div>
                
                @if($rescheduleReason)
                <div style="background-color: #E0E7FF; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <strong>Grund der Verschiebung:</strong> {{ $rescheduleReason }}
                </div>
                @endif
                
                <div style="text-align: center; margin: 40px 0;">
                    <a href="{{ $addToCalendarUrl }}" class="button button-primary">
                        Neuen Termin im Kalender speichern
                    </a>
                    <a href="{{ $cancelUrl }}" class="button button-secondary">
                        Termin stornieren
                    </a>
                </div>
                
                <p><strong>Bitte aktualisieren Sie Ihren Kalender mit dem neuen Termin.</strong> Die angehängte ICS-Datei können Sie direkt in Ihren Kalender importieren.</p>
                
                <p>Falls Sie den neuen Termin nicht wahrnehmen können, bitten wir um rechtzeitige Absage.</p>
                
                <p>Mit freundlichen Grüßen,<br>
                Ihr {{ $company->name }} Team</p>
            @else
                <h2>Hello {{ $customer->first_name }},</h2>
                
                <p>Your appointment has been successfully rescheduled. Please note the new appointment details:</p>
                
                <div class="appointment-box">
                    <h3 style="margin-top: 0; color: #333;">🗓️ New Appointment</h3>
                    
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Date:</span>
                        <span class="appointment-detail-value">
                            <strong>{{ $appointment->starts_at->locale($locale)->isoFormat('dddd, MMMM D, YYYY') }}</strong>
                        </span>
                    </div>
                    
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Time:</span>
                        <span class="appointment-detail-value">
                            <strong>{{ $appointment->starts_at->format('g:i A') }} - {{ $appointment->ends_at->format('g:i A') }}</strong>
                        </span>
                    </div>
                    
                    @if($service)
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Service:</span>
                        <span class="appointment-detail-value">{{ $service->name }}</span>
                    </div>
                    @endif
                    
                    @if($staff)
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Staff:</span>
                        <span class="appointment-detail-value">{{ $staff->first_name }} {{ $staff->last_name }}</span>
                    </div>
                    @endif
                    
                    <div class="appointment-detail">
                        <span class="appointment-detail-label">Location:</span>
                        <span class="appointment-detail-value">
                            {{ $branch->name }}<br>
                            {{ $branch->address }}
                        </span>
                    </div>
                </div>
                
                <div class="old-appointment-box">
                    <p style="margin: 0;"><strong>Previous appointment (has been moved):</strong><br>
                    <span class="strikethrough">{{ $oldStartTime->locale($locale)->isoFormat('dddd, MMMM D, YYYY') }} at {{ $oldStartTime->format('g:i A') }}</span></p>
                </div>
                
                @if($rescheduleReason)
                <div style="background-color: #E0E7FF; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <strong>Reason for rescheduling:</strong> {{ $rescheduleReason }}
                </div>
                @endif
                
                <div style="text-align: center; margin: 40px 0;">
                    <a href="{{ $addToCalendarUrl }}" class="button button-primary">
                        Save New Time to Calendar
                    </a>
                    <a href="{{ $cancelUrl }}" class="button button-secondary">
                        Cancel Appointment
                    </a>
                </div>
                
                <p><strong>Please update your calendar with the new appointment time.</strong> You can import the attached ICS file directly into your calendar.</p>
                
                <p>If you cannot make the new appointment time, please let us know as soon as possible.</p>
                
                <p>Best regards,<br>
                Your {{ $company->name }} Team</p>
            @endif
        </div>
        
        <div class="footer">
            <p style="margin: 0;">
                @if($locale === 'de')
                    Diese Nachricht wurde automatisch erstellt.<br>
                    <a href="{{ url('/datenschutz') }}">Datenschutz</a> | 
                    <a href="{{ url('/impressum') }}">Impressum</a>
                @else
                    This message was automatically generated.<br>
                    <a href="{{ url('/privacy') }}">Privacy Policy</a> | 
                    <a href="{{ url('/imprint') }}">Imprint</a>
                @endif
            </p>
            
            @if($company->address)
            <p style="margin: 15px 0 0 0; font-size: 12px;">
                {{ $company->name }} · {{ $company->address }}
            </p>
            @endif
        </div>
    </div>
    
    <div style="text-align: center; padding: 20px; font-size: 12px; color: #999;">
        <img src="https://api.askproai.de/images/powered-by-askproai.png" alt="Powered by AskProAI" style="height: 20px; opacity: 0.6;">
    </div>
</body>
</html>