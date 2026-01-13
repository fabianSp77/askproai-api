<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sitzung abgelaufen - AskProAI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        * { margin: 0; }

        :root {
            --color-bg: #f9fafb;
            --color-card: #ffffff;
            --color-text: #111827;
            --color-text-muted: #6b7280;
            --color-amber: #f59e0b;
            --color-amber-hover: #d97706;
            --color-blue-bg: #dbeafe;
            --color-blue-text: #1e40af;
        }

        .dark {
            --color-bg: #111827;
            --color-card: #1f2937;
            --color-text: #f9fafb;
            --color-text-muted: #9ca3af;
            --color-blue-bg: #1e3a5f;
            --color-blue-text: #93c5fd;
        }

        html { height: 100%; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            min-height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 1rem;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 28rem;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            background: var(--color-card);
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .icon-wrapper {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.5rem;
            padding: 0.75rem;
            border-radius: 50%;
            background: #fef3c7;
        }

        .dark .icon-wrapper {
            background: #78350f;
        }

        .icon-wrapper svg {
            width: 100%;
            height: 100%;
            color: #d97706;
        }

        .dark .icon-wrapper svg {
            color: #fbbf24;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .message {
            color: var(--color-text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .info-box {
            background: var(--color-blue-bg);
            color: var(--color-blue-text);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: none;
            font-size: 0.875rem;
            text-align: left;
        }

        .info-box.show {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .info-box svg {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            background: var(--color-amber);
            color: white;
            transition: background-color 0.15s ease;
        }

        .btn:hover {
            background: var(--color-amber-hover);
        }

        .error-code {
            margin-top: 1rem;
            font-size: 0.75rem;
            color: var(--color-text-muted);
        }
    </style>
    <script>
        // Dark Mode Detection
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            {{-- Clock Icon --}}
            <div class="icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1>Sitzung abgelaufen</h1>

            <p class="message">
                Ihre Sitzung ist aus Sicherheitsgründen abgelaufen.
                Bitte melden Sie sich erneut an, um fortzufahren.
            </p>

            {{-- Info about saved data --}}
            <div class="info-box" id="saved-data-notice">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <span>Ihre ungespeicherten Formulardaten wurden zwischengespeichert und können nach der Anmeldung wiederhergestellt werden.</span>
            </div>

            <a href="/admin/login" class="btn">
                Zur Anmeldung
            </a>

            <p class="error-code">
                Fehlercode: 419 | Page Expired
            </p>
        </div>
    </div>

    <script>
        // Check for saved form data
        (function() {
            try {
                var savedData = localStorage.getItem('askpro_unsaved_forms');
                if (savedData) {
                    var parsed = JSON.parse(savedData);
                    // Show notice if data was saved in the last hour
                    if (Date.now() - parsed.timestamp < 3600000) {
                        document.getElementById('saved-data-notice').classList.add('show');
                    }
                }
            } catch (e) {
                // Ignore errors
            }
        })();
    </script>
</body>
</html>
