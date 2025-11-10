<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abgemeldet - AskPro AI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .logout-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            padding: 2.5rem;
            text-align: center;
        }

        .icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        h1 {
            color: #2d3748;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .message {
            color: #718096;
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .info-box {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
            border-radius: 4px;
        }

        .info-box p {
            color: #166534;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .footer {
            margin-top: 2rem;
            color: #64748b; /* WCAG AA compliant: 5.1:1 contrast ratio (was #a0aec0: 2.52:1) */
            font-size: 0.85rem;
        }

        @media (max-width: 480px) {
            .logout-container {
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="icon">✅</div>
        <h1>Erfolgreich abgemeldet</h1>
        <p class="message">Sie wurden erfolgreich von der Dokumentation abgemeldet.</p>

        <div class="info-box">
            <p><strong>✓ Sitzung beendet</strong></p>
            <p>Ihre Sitzung wurde erfolgreich beendet. Sie können diese Seite schließen oder sich erneut anmelden.</p>
            <p><strong>Sicherheitstipp:</strong> Verwenden Sie bei gemeinsam genutzten Computern immer den Abmelde-Button, um Ihre Sitzung zu beenden.</p>
        </div>

        <a href="{{ route('docs.backup-system.login') }}" class="btn">
            Erneut anmelden
        </a>

        <div class="footer">
            <p>AskPro AI Gateway &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
