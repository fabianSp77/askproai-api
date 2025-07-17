<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'AskProAI') }} - Admin Login</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
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
        
        .demo-credentials {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .demo-credentials h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #0066ff;
        }
        
        .demo-credentials code {
            background: rgba(0,0,0,0.05);
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>AskProAI</h1>
            <p>Admin Portal</p>
        </div>
        
        <div class="demo-credentials">
            <h3>Demo-Zugangsdaten</h3>
            <p>Email: <code>admin@askproai.de</code></p>
            <p>Passwort: <code>admin123</code></p>
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
        async function handleLogin(event) {
            event.preventDefault();
            
            const messageEl = document.getElementById('message');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            
            // Clear previous messages
            messageEl.innerHTML = '';
            messageEl.className = '';
            
            // Disable button and show loading
            loginBtn.disabled = true;
            btnText.innerHTML = '<span class="loading"></span>Anmeldung läuft...';
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            try {
                const response = await fetch('/api/admin/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (response.ok && data.token) {
                    // Save token
                    localStorage.setItem('admin_token', data.token);
                    
                    // Show success message
                    messageEl.innerHTML = 'Anmeldung erfolgreich! Sie werden weitergeleitet...';
                    messageEl.className = 'success';
                    
                    // Redirect to admin dashboard
                    setTimeout(() => {
                        window.location.href = '/admin-react';
                    }, 1000);
                } else {
                    // Show error message
                    const errorMsg = data.message || 'Anmeldung fehlgeschlagen. Bitte überprüfen Sie Ihre Zugangsdaten.';
                    messageEl.innerHTML = errorMsg;
                    messageEl.className = 'error';
                    
                    // Re-enable button
                    loginBtn.disabled = false;
                    btnText.textContent = 'Anmelden';
                }
            } catch (error) {
                console.error('Login error:', error);
                messageEl.innerHTML = 'Netzwerkfehler. Bitte versuchen Sie es später erneut.';
                messageEl.className = 'error';
                
                // Re-enable button
                loginBtn.disabled = false;
                btnText.textContent = 'Anmelden';
            }
        }
        
        // Check if already logged in
        if (localStorage.getItem('admin_token')) {
            // Optionally redirect if already logged in
            // window.location.href = '/admin-react';
        }
    </script>
</body>
</html>