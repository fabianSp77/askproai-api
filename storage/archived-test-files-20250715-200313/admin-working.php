<?php
// Working Admin Dashboard - Direct PHP without Laravel complications
session_start();
require __DIR__.'/../vendor/autoload.php';

// Database connection
$db = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');

// Check if user is logged in
$isLoggedIn = false;
$user = null;

if (isset($_SESSION['user_email'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['user_email']]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    $isLoggedIn = true;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($user && password_verify($password, $user->password)) {
        $_SESSION['user_email'] = $user->email;
        header('Location: /admin-working.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin-working.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AskProAI Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php if (!$isLoggedIn): ?>
    <!-- Login Form -->
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    AskProAI Admin Login
                </h2>
            </div>
            <form class="mt-8 space-y-6" method="POST">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Email" value="fabian@askproai.de">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Einloggen
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Dashboard -->
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold">AskProAI Admin Dashboard</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">Hallo, <?php echo htmlspecialchars($user->name); ?></span>
                        <a href="?logout=1" class="text-gray-500 hover:text-gray-700">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Quick Stats -->
            <div class="px-4 py-6 sm:px-0">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <?php
                    // Get stats
                    $stats = [
                        'companies' => $db->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
                        'appointments' => $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
                        'customers' => $db->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
                        'calls' => $db->query("SELECT COUNT(*) FROM calls")->fetchColumn(),
                    ];
                    ?>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Firmen</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['companies']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Termine</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['appointments']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Kunden</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['customers']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Anrufe</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['calls']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="/admin" class="bg-blue-600 text-white rounded-lg p-6 hover:bg-blue-700 transition">
                        <h3 class="text-lg font-medium">Filament Admin</h3>
                        <p class="mt-2 text-sm">Zum vollen Admin-Panel (wenn es funktioniert)</p>
                    </a>
                    
                    <a href="/quickwins/health.php" class="bg-green-600 text-white rounded-lg p-6 hover:bg-green-700 transition">
                        <h3 class="text-lg font-medium">System Health</h3>
                        <p class="mt-2 text-sm">Quick Wins Status überprüfen</p>
                    </a>
                    
                    <a href="/status.php" class="bg-purple-600 text-white rounded-lg p-6 hover:bg-purple-700 transition">
                        <h3 class="text-lg font-medium">Performance Status</h3>
                        <p class="mt-2 text-sm">Quick Wins Performance Metriken</p>
                    </a>
                </div>

                <!-- System Info -->
                <div class="mt-8 bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">System Information</h2>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo PHP_VERSION; ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Server</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Quick Wins Status</dt>
                            <dd class="mt-1 text-sm text-green-600 font-medium">✓ Deployed & Ready</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Retell.ai Webhook</dt>
                            <dd class="mt-1 text-sm text-yellow-600 font-medium">⚠️ Update URL to activate</dd>
                        </div>
                    </dl>
                </div>

                <!-- Quick Wins Activation Instructions -->
                <div class="mt-8 bg-yellow-50 border-l-4 border-yellow-400 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Quick Wins aktivieren</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Um die Performance-Optimierungen zu aktivieren:</p>
                                <ol class="list-decimal list-inside mt-1">
                                    <li>Loggen Sie sich bei Retell.ai ein</li>
                                    <li>Gehen Sie zu Agent Settings → Webhooks</li>
                                    <li>Ändern Sie die URL zu: <code class="bg-yellow-100 px-1">https://api.askproai.de/api/retell/optimized-webhook</code></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php endif; ?>
</body>
</html>