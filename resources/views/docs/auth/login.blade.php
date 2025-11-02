<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentation Login - AskPro AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
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

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 420px;
            width: 100%;
            padding: 2.5rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            font-size: 3.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        h1 {
            text-align: center;
            color: #2d3748;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .subtitle {
            text-align: center;
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            color: #4a5568;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1.25rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            background: white;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input.error {
            border-color: #ef4444;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-label {
            color: #4a5568;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .footer {
            margin-top: 2rem;
            text-align: center;
            color: #a0aec0;
            font-size: 0.85rem;
        }

        .info-box {
            background: #f7fafc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #4a5568;
            line-height: 1.6;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .logo-icon {
                font-size: 3rem;
            }
        }

        /* Loading spinner */
        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .btn.loading .spinner {
            display: inline-block;
        }

        .btn.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">📚</div>
        </div>

        <h1>Dokumentation</h1>
        <p class="subtitle">Backup System & Technische Dokumentation</p>

        @if(session('success'))
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <span class="mdi mdi-alert-circle"></span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('intended'))
            <div class="alert alert-info">
                <span class="mdi mdi-information"></span>
                <span>Bitte melden Sie sich an, um fortzufahren.</span>
            </div>
        @endif

        @if($errors->has('credentials'))
            <div class="alert alert-error">
                <span class="mdi mdi-alert-circle"></span>
                <span>{{ $errors->first('credentials') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('docs.backup-system.login.submit') }}" id="loginForm">
            @csrf

            <div class="form-group">
                <label for="username">Benutzername</label>
                <div class="input-wrapper">
                    <span class="input-icon mdi mdi-account"></span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        class="@error('username') error @enderror"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>
                @error('username')
                    <div class="error-message">
                        <span class="mdi mdi-alert-circle"></span>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Passwort</label>
                <div class="input-wrapper">
                    <span class="input-icon mdi mdi-lock"></span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="@error('password') error @enderror"
                        required
                        autocomplete="current-password"
                    >
                </div>
                @error('password')
                    <div class="error-message">
                        <span class="mdi mdi-alert-circle"></span>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="checkbox-wrapper">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <label for="remember" class="checkbox-label">Angemeldet bleiben (30 Tage)</label>
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <span class="spinner"></span>
                <span class="btn-text">
                    <span class="mdi mdi-login"></span>
                    <span>Anmelden</span>
                </span>
            </button>
        </form>

        <div class="info-box" style="margin-top: 1.5rem;">
            <strong>💡 Hinweis:</strong> Für den Zugriff benötigen Sie gültige Zugangsdaten. Bei Fragen wenden Sie sich an das DevOps-Team.
        </div>

        <div class="footer">
            <p>AskPro AI Gateway © {{ date('Y') }}</p>
        </div>
    </div>

    <script>
        // Add loading state to form
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Focus username field on page load
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
