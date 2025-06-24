<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminbestätigung</title>
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
            background-color: {{ $company->metadata['brand_color'] ?? '#3B82F6' }};
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
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid {{ $company->metadata['brand_color'] ?? '#3B82F6' }};
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
            <h2>Hallo {{ $customer->first_name }},</h2>
            
            <p>vielen Dank für Ihre Terminbuchung. Wir freuen uns auf Ihren Besuch!</p>
            
            <div class="appointment-box">
                <h3 style="margin-top: 0; color: #333;">Ihre Termindetails</h3>
                
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
                
                @if($service->price)
                <div class="appointment-detail">
                    <span class="appointment-detail-label">Preis:</span>
                    <span class="appointment-detail-value">{{ number_format($service->price, 2, ',', '.') }} €</span>
                </div>
                @endif
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
                
                @if($branch->phone)
                <div class="appointment-detail">
                    <span class="appointment-detail-label">Telefon:</span>
                    <span class="appointment-detail-value">
                        <a href="tel:{{ $branch->phone }}">{{ $branch->phone }}</a>
                    </span>
                </div>
                @endif
            </div>
            
            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $addToCalendarUrl }}" class="button button-primary">
                    In Kalender speichern
                </a>
                <a href="{{ $rescheduleUrl }}" class="button button-secondary">
                    Termin verschieben
                </a>
            </div>
            
            <div class="divider"></div>
            
            <h3>Wichtige Hinweise</h3>
            <ul style="color: #666; padding-left: 20px;">
                <li>Bitte erscheinen Sie pünktlich zu Ihrem Termin</li>
                <li>Bei Verspätung kann sich die Behandlungszeit verkürzen</li>
                <li>Falls Sie den Termin nicht wahrnehmen können, bitten wir um rechtzeitige Absage</li>
                @if($branch->metadata['parking_info'] ?? false)
                <li>{{ $branch->metadata['parking_info'] }}</li>
                @endif
            </ul>
            
            @if($appointment->notes)
            <div style="background-color: #FEF3C7; border-radius: 6px; padding: 15px; margin: 20px 0;">
                <strong>Hinweis:</strong> {{ $appointment->notes }}
            </div>
            @endif
            
            <p style="margin-top: 30px;">
                Falls Sie Fragen haben, kontaktieren Sie uns gerne unter 
                <a href="tel:{{ $branch->phone ?? $company->phone }}">{{ $branch->phone ?? $company->phone }}</a> 
                oder antworten Sie einfach auf diese E-Mail.
            </p>
            
            <p>Mit freundlichen Grüßen,<br>
            Ihr {{ $company->name }} Team</p>
        </div>
        
        <div class="footer">
            <p style="margin: 0;">
                Diese Terminbestätigung wurde automatisch erstellt.<br>
                <a href="{{ $cancelUrl }}">Termin stornieren</a> | 
                <a href="{{ url('/datenschutz') }}">Datenschutz</a> | 
                <a href="{{ url('/impressum') }}">Impressum</a>
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