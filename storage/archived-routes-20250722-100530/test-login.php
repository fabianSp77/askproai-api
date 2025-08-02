<?php

use Illuminate\Support\Facades\Route;

// Test login route
Route::get('/business/login-simple', function() {
    return view('portal.auth.react-login-simple');
})->name('business.login-simple');

// Fallback HTML login
Route::get('/business/login-fallback', function() {
    return '
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="' . csrf_token() . '">
    <title>Business Portal Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 24px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 32px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #1890ff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        button:hover {
            background: #40a9ff;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .error {
            padding: 12px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
            margin-bottom: 16px;
        }
        .info {
            margin-top: 32px;
            padding: 16px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
        }
        .result {
            margin-top: 16px;
            padding: 12px;
            border-radius: 4px;
        }
        .result.success {
            background: #d4edda;
            color: #155724;
        }
        .result.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Business Portal</h2>
        <p class="subtitle">Melden Sie sich an, um auf Ihr Unternehmenskonto zuzugreifen</p>
        
        <div id="error-msg" class="error" style="display: none;"></div>
        
        <form id="login-form">
            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" value="demo@askproai.de" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" value="demo123" required>
            </div>
            
            <button type="submit" id="submit-btn">Anmelden</button>
        </form>
        
        <div id="result" class="result" style="display: none;"></div>
        
        <div class="info">
            <strong>Test-Anmeldedaten:</strong><br>
            Email: demo@askproai.de<br>
            Passwort: demo123
        </div>
    </div>
    
    <script>
        const form = document.getElementById("login-form");
        const errorMsg = document.getElementById("error-msg");
        const result = document.getElementById("result");
        const submitBtn = document.getElementById("submit-btn");
        
        // Get CSRF token
        const csrfToken = document.querySelector(\'meta[name="csrf-token"]\').getAttribute("content");
        
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            
            errorMsg.style.display = "none";
            result.style.display = "none";
            submitBtn.disabled = true;
            submitBtn.textContent = "Anmeldung läuft...";
            
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            
            try {
                const response = await fetch("/api/auth/portal/login", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        device_name: "web"
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.token) {
                    // Store token
                    localStorage.setItem("auth_token", data.token);
                    localStorage.setItem("portal_user", JSON.stringify(data.user));
                    
                    result.className = "result success";
                    result.textContent = "Login erfolgreich! Weiterleitung...";
                    result.style.display = "block";
                    
                    // Redirect
                    setTimeout(() => {
                        window.location.href = "/business";
                    }, 1000);
                } else {
                    const errorMessage = data.errors?.email?.[0] || data.message || "Login fehlgeschlagen";
                    errorMsg.textContent = errorMessage;
                    errorMsg.style.display = "block";
                }
            } catch (error) {
                console.error("Login error:", error);
                errorMsg.textContent = "Netzwerkfehler: " + error.message;
                errorMsg.style.display = "block";
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = "Anmelden";
            }
        });
    </script>
</body>
</html>
    ';
})->name('business.login-fallback');