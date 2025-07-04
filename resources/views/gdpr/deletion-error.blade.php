<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - {{ app()->getLocale() === 'de' ? 'Fehler' : 'Error' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 600px;
            margin: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        
        .icon-error {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background-color: #f8d7da;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .icon-error svg {
            width: 40px;
            height: 40px;
            stroke: #721c24;
        }
        
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .message {
            color: #555;
            margin-bottom: 30px;
            font-size: 18px;
        }
        
        .error-details {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .error-details h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .error-code {
            font-family: monospace;
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            color: #495057;
        }
        
        .possible-reasons {
            background-color: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        
        .possible-reasons h4 {
            color: #34495e;
            margin-bottom: 10px;
        }
        
        .possible-reasons ul {
            margin-left: 20px;
            color: #666;
        }
        
        .possible-reasons li {
            margin-bottom: 8px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
            color: #7f8c8d;
        }
        
        .footer a {
            color: #3498db;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: #2980b9;
        }
        
        .button-secondary {
            background-color: #6c757d;
            margin-left: 10px;
        }
        
        .button-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        
        @if(app()->getLocale() === 'de')
            <h1>Fehler bei der Löschbestätigung</h1>
            
            <p class="message">
                Leider konnte Ihre Löschbestätigung nicht verarbeitet werden.
            </p>
            
            @if(isset($error))
                <div class="error-details">
                    <h3>Fehlerdetails:</h3>
                    <div class="error-code">
                        {{ $error }}
                    </div>
                </div>
            @endif
            
            <div class="possible-reasons">
                <h4>Mögliche Gründe:</h4>
                <ul>
                    <li>Der Bestätigungslink ist abgelaufen (gültig für 3 Tage)</li>
                    <li>Der Link wurde bereits verwendet</li>
                    <li>Die Löschanfrage wurde storniert</li>
                    <li>Der Link ist ungültig oder beschädigt</li>
                </ul>
            </div>
            
            <p style="margin: 30px 0;">
                Falls Sie Ihre Daten immer noch löschen möchten, können Sie eine neue Anfrage stellen.
            </p>
            
            <div style="margin-top: 30px;">
                <a href="/privacy-tools" class="button">Neue Anfrage stellen</a>
                <a href="/" class="button button-secondary">Zur Startseite</a>
            </div>
            
            <div class="footer">
                <p>Bei Fragen oder Problemen kontaktieren Sie bitte unseren Support.</p>
            </div>
        @else
            <h1>Deletion Confirmation Error</h1>
            
            <p class="message">
                Unfortunately, your deletion confirmation could not be processed.
            </p>
            
            @if(isset($error))
                <div class="error-details">
                    <h3>Error Details:</h3>
                    <div class="error-code">
                        {{ $error }}
                    </div>
                </div>
            @endif
            
            <div class="possible-reasons">
                <h4>Possible Reasons:</h4>
                <ul>
                    <li>The confirmation link has expired (valid for 3 days)</li>
                    <li>The link has already been used</li>
                    <li>The deletion request was cancelled</li>
                    <li>The link is invalid or corrupted</li>
                </ul>
            </div>
            
            <p style="margin: 30px 0;">
                If you still want to delete your data, you can submit a new request.
            </p>
            
            <div style="margin-top: 30px;">
                <a href="/privacy-tools" class="button">Submit New Request</a>
                <a href="/" class="button button-secondary">Back to Homepage</a>
            </div>
            
            <div class="footer">
                <p>If you have questions or issues, please contact our support.</p>
            </div>
        @endif
    </div>
</body>
</html>