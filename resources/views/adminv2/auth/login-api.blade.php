<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin v2 Login (API)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5a67d8;
        }
        button:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .loader {
            display: none;
            text-align: center;
            color: #667eea;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin v2 Login</h1>
        <div id="error" class="error"></div>
        <div id="success" class="success"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="fabian@askproai.de" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="" required>
            </div>
            <button type="submit" id="submitBtn">Login</button>
        </form>
        
        <div class="loader" id="loader">
            <p>Logging in...</p>
        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const errorDiv = document.getElementById('error');
        const successDiv = document.getElementById('success');
        const loader = document.getElementById('loader');
        const submitBtn = document.getElementById('submitBtn');
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Reset messages
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            loader.style.display = 'block';
            submitBtn.disabled = true;
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            try {
                // Call API login endpoint (using direct PHP temporarily)
                const response = await fetch('/admin-v2-api-test.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    successDiv.textContent = 'Login successful! Redirecting...';
                    successDiv.style.display = 'block';
                    
                    // Store token if needed
                    if (data.token) {
                        localStorage.setItem('adminv2_token', data.token);
                    }
                    
                    // Redirect to dashboard
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '/admin-v2/dashboard';
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Login failed');
                }
            } catch (error) {
                errorDiv.textContent = error.message || 'Invalid credentials or server error';
                errorDiv.style.display = 'block';
                loader.style.display = 'none';
                submitBtn.disabled = false;
            }
        });
        
        // Check if already authenticated
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('/admin-v2/api/check', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.authenticated) {
                    successDiv.textContent = 'Already logged in! Redirecting...';
                    successDiv.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = '/admin-v2/dashboard';
                    }, 1000);
                }
            } catch (error) {
                // Not authenticated, show login form
            }
        });
    </script>
</body>
</html>