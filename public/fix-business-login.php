<?php
// Emergency fix for business login redirect loop

// Clear all session data
session_start();
session_destroy();

// Clear all cookies
$cookies = ['portal_session', 'askproai_portal_session', 'laravel_session', 'PHPSESSID', 'XSRF-TOKEN'];
foreach ($cookies as $cookie) {
    setcookie($cookie, '', time() - 3600, '/');
    setcookie($cookie, '', time() - 3600, '/business');
    setcookie($cookie, '', time() - 3600, '/business/');
}

// Generate new CSRF token
$token = bin2hex(random_bytes(32));

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Portal Login - Fixed</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Business Portal Anmeldung
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Bitte melden Sie sich mit Ihren Zugangsdaten an
                </p>
            </div>
            
            <form class="mt-8 space-y-6" action="/business/login" method="POST" onsubmit="return handleLogin(event)">
                <input type="hidden" name="_token" value="<?php echo $token; ?>">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">E-Mail Adresse</label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="E-Mail Adresse"
                               value="">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Passwort</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="Passwort">
                    </div>
                </div>

                <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline" id="error-text"></span>
                </div>

                <div>
                    <button type="submit" id="submit-btn" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Anmelden
                    </button>
                </div>
            </form>

            <div class="text-center text-sm text-gray-600">
                <p>Test-Zugangsdaten:</p>
                <p class="font-mono">demo@portal.de / Test2024!</p>
            </div>
        </div>
    </div>

    <script>
    function handleLogin(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const submitBtn = document.getElementById('submit-btn');
        const errorDiv = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Anmeldung läuft...';
        errorDiv.classList.add('hidden');
        
        // Create form data
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('_token', '<?php echo $token; ?>');
        
        // Submit via AJAX
        fetch('/business/login', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.redirected) {
                // Follow redirect
                window.location.href = response.url;
            } else {
                return response.json();
            }
        })
        .then(data => {
            if (data && data.success) {
                window.location.href = '/business/dashboard';
            } else if (data && data.error) {
                errorText.textContent = data.error;
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Anmelden';
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            errorText.textContent = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Anmelden';
        });
        
        return false;
    }
    </script>
</body>
</html>