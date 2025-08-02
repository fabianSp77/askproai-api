<?php
// Direct business login - bypassing all Laravel routing
require_once __DIR__ . '/../vendor/autoload.php';

// Start the app
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake request
$request = Illuminate\Http\Request::create('/business/login', 'GET');
$response = $kernel->handle($request);

// Clear any cookies that might cause loops
header("Set-Cookie: portal_session=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/");
header("Set-Cookie: askproai_portal_session=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/");
header("Set-Cookie: laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/");

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Portal Login</title>
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Business Portal Login
                </h2>
            </div>
            <form class="mt-8 space-y-6" action="/business/login" method="POST">
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">E-Mail</label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="E-Mail Adresse">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Passwort</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Passwort">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Anmelden
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>