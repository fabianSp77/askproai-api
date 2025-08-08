<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $alert->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, {{ $severity_color }}, {{ $severity_color }}dd);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .severity {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .content {
            padding: 32px 24px;
        }
        .alert-details {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
            border-left: 4px solid {{ $severity_color }};
        }
        .alert-details h3 {
            margin: 0 0 12px 0;
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
        }
        .alert-details p {
            margin: 0;
            color: #4b5563;
            font-size: 14px;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }
        .metric {
            background-color: #f9fafb;
            padding: 16px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .metric .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .metric .value {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }
        .metric.critical .value {
            color: #ef4444;
        }
        .metric.warning .value {
            color: #f59e0b;
        }
        .action-items {
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .action-items h3 {
            margin: 0 0 16px 0;
            color: #92400e;
            font-size: 16px;
            font-weight: 600;
        }
        .action-items ul {
            margin: 0;
            padding-left: 20px;
            color: #78350f;
        }
        .action-items li {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .buttons {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            display: inline-block;
            background-color: {{ $severity_color }};
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            margin: 0 8px;
        }
        .button:hover {
            opacity: 0.9;
        }
        .button.secondary {
            background-color: #6b7280;
        }
        .footer {
            background-color: #f9fafb;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
        }
        .footer a {
            color: {{ $severity_color }};
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 24px 16px;
            }
            .metrics {
                grid-template-columns: 1fr;
            }
            .buttons {
                flex-direction: column;
            }
            .button {
                margin: 8px 0;
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="severity">{{ $severity_icon }}</div>
            <h1>{{ $alert->title }}</h1>
            <p>{{ $company->name }}</p>
        </div>

        <div class="content">
            <div class="alert-details">
                <h3>Alert Details</h3>
                <p><strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $alert->alert_type)) }}</p>
                <p><strong>Severity:</strong> {{ ucfirst($alert->severity) }}</p>
                <p><strong>Time:</strong> {{ $alert->created_at->format('F j, Y \a\t g:i A T') }}</p>
                @if($alert->data)
                <p><strong>Alert ID:</strong> #{{ $alert->id }}</p>
                @endif
            </div>

            <p>{{ $alert->message }}</p>

            @if($alert->alert_type === 'low_balance' || $alert->alert_type === 'zero_balance')
                <div class="metrics">
                    @if(isset($alert->data['balance']))
                    <div class="metric {{ $alert->data['balance'] <= 0 ? 'critical' : ($alert->data['balance'] < 10 ? 'warning' : '') }}">
                        <div class="label">Current Balance</div>
                        <div class="value">€{{ number_format($alert->data['balance'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['threshold']))
                    <div class="metric">
                        <div class="label">Low Balance Threshold</div>
                        <div class="value">€{{ number_format($alert->data['threshold'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['recommended_topup']))
                    <div class="metric">
                        <div class="label">Recommended Top-up</div>
                        <div class="value">€{{ number_format($alert->data['recommended_topup'], 2) }}</div>
                    </div>
                    @endif
                </div>
            @endif

            @if($alert->alert_type === 'budget_exceeded')
                <div class="metrics">
                    @if(isset($alert->data['monthly_spend']))
                    <div class="metric {{ $alert->data['percentage'] > 100 ? 'critical' : 'warning' }}">
                        <div class="label">Monthly Spend</div>
                        <div class="value">€{{ number_format($alert->data['monthly_spend'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['monthly_budget']))
                    <div class="metric">
                        <div class="label">Monthly Budget</div>
                        <div class="value">€{{ number_format($alert->data['monthly_budget'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['percentage']))
                    <div class="metric {{ $alert->data['percentage'] > 100 ? 'critical' : 'warning' }}">
                        <div class="label">Budget Used</div>
                        <div class="value">{{ number_format($alert->data['percentage'], 1) }}%</div>
                    </div>
                    @endif
                </div>
            @endif

            @if($alert->alert_type === 'usage_spike')
                <div class="metrics">
                    @if(isset($alert->data['current_hourly_cost']))
                    <div class="metric critical">
                        <div class="label">Current Hour Cost</div>
                        <div class="value">€{{ number_format($alert->data['current_hourly_cost'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['average_hourly_cost']))
                    <div class="metric">
                        <div class="label">Average Hour Cost</div>
                        <div class="value">€{{ number_format($alert->data['average_hourly_cost'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['percentage_increase']))
                    <div class="metric critical">
                        <div class="label">Increase</div>
                        <div class="value">+{{ $alert->data['percentage_increase'] }}%</div>
                    </div>
                    @endif
                </div>
            @endif

            @if($alert->alert_type === 'cost_anomaly')
                <div class="metrics">
                    @if(isset($alert->data['current_daily_cost']))
                    <div class="metric critical">
                        <div class="label">Today's Cost</div>
                        <div class="value">€{{ number_format($alert->data['current_daily_cost'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['average_daily_cost']))
                    <div class="metric">
                        <div class="label">Average Daily Cost</div>
                        <div class="value">€{{ number_format($alert->data['average_daily_cost'], 2) }}</div>
                    </div>
                    @endif
                    
                    @if(isset($alert->data['anomaly_multiplier']))
                    <div class="metric critical">
                        <div class="label">Multiplier</div>
                        <div class="value">{{ $alert->data['anomaly_multiplier'] }}x</div>
                    </div>
                    @endif
                </div>
            @endif

            <div class="action-items">
                <h3>📋 Recommended Actions</h3>
                <ul>
                    @foreach($action_items as $action)
                    <li>{{ $action }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="buttons">
                <a href="{{ $dashboard_url }}" class="button">
                    📊 View Dashboard
                </a>
                @if($alert->alert_type === 'low_balance' || $alert->alert_type === 'zero_balance')
                <a href="{{ config('app.url') }}/admin/prepaid-balances/{{ $company->id }}/topup" class="button">
                    💳 Add Funds
                </a>
                @endif
                <a href="{{ config('app.url') }}/admin/billing-alert-configs" class="button secondary">
                    ⚙️ Alert Settings
                </a>
            </div>

            <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 24px 0;">
                <p style="margin: 0; color: #991b1b; font-size: 14px;">
                    <strong>⚡ Need immediate assistance?</strong><br>
                    Contact our support team at <a href="mailto:{{ $support_contact }}" style="color: #dc2626;">{{ $support_contact }}</a>
                    or call +49 (0) 123 456 789 for urgent billing issues.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>
                This alert was automatically generated by AskProAI Cost Tracking System.<br>
                <a href="{{ config('app.url') }}/admin/billing-alert-configs">Manage your alert preferences</a> |
                <a href="mailto:{{ $support_contact }}">Contact Support</a>
            </p>
            <p style="margin-top: 16px;">
                AskProAI GmbH | {{ config('app.url') }} | {{ $support_contact }}
            </p>
        </div>
    </div>
</body>
</html>