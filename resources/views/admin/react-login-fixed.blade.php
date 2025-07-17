<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'AskProAI') }} - Admin Login</title>
    
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 28px;
            color: #1a1a1a;
        }
        
        .logo p {
            color: #666;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #0066ff;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #0066ff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover:not(:disabled) {
            background: #0052cc;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .error {
            background: #fee;
            color: #c00;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background: #efe;
            color: #060;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .info {
            background: #e3f2fd;
            color: #1976d2;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>AskProAI</h1>
            <p>Admin Portal</p>
        </div>
        
        <div class="info">
            <strong>💡 CSRF-Fix aktiviert</strong><br>
            Diese Login-Seite umgeht Session-Konflikte.
        </div>
        
        <div id="message"></div>
        
        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="admin@askproai.de"
                    required 
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="password">Passwort</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    value="admin123"
                    required
                >
            </div>
            
            <button type="submit" class="btn" id="loginBtn">
                <span id="btnText">Anmelden</span>
            </button>
        </form>
    </div>
    
    <script>
        // WICHTIG: Wir verwenden KEINE Cookies oder Sessions!
        async function handleLogin(event) {
            event.preventDefault();
            
            const messageEl = document.getElementById('message');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            
            messageEl.innerHTML = '';
            messageEl.className = '';
            
            loginBtn.disabled = true;
            btnText.innerHTML = '<span class="loading"></span>Anmeldung läuft...';
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            try {
                // WICHTIG: Kein credentials: 'include' und kein CSRF Token!
                const response = await fetch('/api/admin/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        // KEIN X-CSRF-TOKEN Header!
                    },
                    // KEIN credentials: 'include'!
                    body: JSON.stringify({ email, password })
                });
                
                let data;
                const text = await response.text();
                
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Response:', text);
                    throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
                }
                
                if (response.ok && data.token) {
                    localStorage.setItem('admin_token', data.token);
                    
                    messageEl.innerHTML = 'Anmeldung erfolgreich! Sie werden weitergeleitet...';
                    messageEl.className = 'success';
                    
                    setTimeout(() => {
                        window.location.href = '/admin-react';
                    }, 1000);
                } else {
                    const errorMsg = data.message || data.error || 'Anmeldung fehlgeschlagen';
                    messageEl.innerHTML = errorMsg;
                    messageEl.className = 'error';
                    
                    loginBtn.disabled = false;
                    btnText.textContent = 'Anmelden';
                }
            } catch (error) {
                console.error('Login error:', error);
                messageEl.innerHTML = 'Fehler: ' + error.message;
                messageEl.className = 'error';
                
                loginBtn.disabled = false;
                btnText.textContent = 'Anmelden';
            }
        }
        
        // Debug-Info
        console.log('== CSRF Debug Info ==');
        console.log('Cookies:', document.cookie);
        console.log('Current URL:', window.location.href);
        console.log('Token exists:', !!localStorage.getItem('admin_token'));
    </script>
</body>
</html>