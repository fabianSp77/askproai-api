<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Critical Alert - AskProAI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #dc2626;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .alert-info {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin-bottom: 20px;
        }
        .alert-info h2 {
            margin: 0 0 10px 0;
            color: #991b1b;
            font-size: 18px;
        }
        .details {
            background-color: #f9fafb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details td {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .details td:first-child {
            font-weight: 600;
            color: #6b7280;
            width: 30%;
        }
        .context {
            background-color: #f3f4f6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .action-button {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .severity-critical {
            color: #dc2626;
            font-weight: bold;
        }
        .severity-high {
            color: #f59e0b;
            font-weight: bold;
        }
        .severity-medium {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Critical System Alert</h1>
        </div>
        
        <div class="content">
            <div class="alert-info">
                <h2>{{ $message }}</h2>
                <p>Service: <strong>{{ ucfirst($service) }}</strong> | Type: <strong>{{ str_replace('_', ' ', ucfirst($errorType)) }}</strong></p>
            </div>
            
            <div class="details">
                <table>
                    <tr>
                        <td>Alert ID:</td>
                        <td>{{ $alertId }}</td>
                    </tr>
                    <tr>
                        <td>Timestamp:</td>
                        <td>{{ $timestamp->format('Y-m-d H:i:s') }} UTC</td>
                    </tr>
                    <tr>
                        <td>Service:</td>
                        <td>{{ ucfirst($service) }}</td>
                    </tr>
                    <tr>
                        <td>Error Type:</td>
                        <td>{{ str_replace('_', ' ', ucfirst($errorType)) }}</td>
                    </tr>
                    <tr>
                        <td>Severity:</td>
                        <td class="severity-{{ $severity ?? 'medium' }}">
                            {{ strtoupper($severity ?? 'MEDIUM') }}
                        </td>
                    </tr>
                </table>
            </div>
            
            @if(!empty($context))
                <h3>Additional Context:</h3>
                <div class="context">
                    <pre>{{ json_encode($context, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ url('/admin/api-health-monitor') }}" class="action-button">
                    View System Dashboard
                </a>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background-color: #fef3c7; border-radius: 4px;">
                <p style="margin: 0; color: #92400e;">
                    <strong>⚠️ Action Required:</strong> Please investigate this alert immediately. 
                    If this is a critical production issue, follow the incident response procedure.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated alert from AskProAI System Monitoring</p>
            <p>Alert generated at {{ $timestamp->format('Y-m-d H:i:s') }} UTC</p>
            <p style="margin-top: 10px;">
                <a href="{{ url('/admin/system-alerts') }}" style="color: #3b82f6;">View All Alerts</a> |
                <a href="{{ url('/admin/api-health-monitor') }}" style="color: #3b82f6;">System Dashboard</a>
            </p>
        </div>
    </div>
</body>
</html>