<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'AskProAI') }} - Admin Login</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css'])
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Admin Portal
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    React-basiertes Admin Portal (NEU!)
                </p>
            </div>
            
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form id="loginForm" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            E-Mail Adresse
                        </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Passwort
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-900">
                                Angemeldet bleiben
                            </label>
                        </div>
                    </div>

                    <div>
                        <button type="submit" id="submitBtn"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Anmelden
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Oder</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="/admin/login" 
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Zum alten Admin Portal (Filament)
                        </a>
                    </div>
                </div>

                <div id="errorMessage" class="mt-4 hidden">
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Fehler bei der Anmeldung
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p id="errorText"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        <strong>Info:</strong> Dies ist das neue React-basierte Admin Portal. 
                        Es löst die Session-Probleme des alten Systems und bietet eine moderne, 
                        schnelle Benutzeroberfläche.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const errorDiv = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird angemeldet...';
            errorDiv.classList.add('hidden');
            
            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                remember: document.getElementById('remember').checked
            };
            
            try {
                const response = await fetch('/api/admin/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Store token
                    localStorage.setItem('admin_token', data.token);
                    
                    // Redirect to React admin dashboard
                    window.location.href = '/admin-react-app';
                } else {
                    // Show error
                    errorText.textContent = data.message || 'Anmeldung fehlgeschlagen';
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Login error:', error);
                errorText.textContent = 'Netzwerkfehler. Bitte versuchen Sie es später erneut.';
                errorDiv.classList.remove('hidden');
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Anmelden';
            }
        });
    </script>
</body>
</html>