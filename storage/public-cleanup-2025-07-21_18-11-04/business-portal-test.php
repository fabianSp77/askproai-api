<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

$action = $_GET['action'] ?? 'home';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Business Portal Test Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Business Portal Test Suite</h1>

        <!-- Navigation -->
        <div class="bg-white rounded-lg shadow mb-8 p-4">
            <div class="flex space-x-4">
                <a href="?action=home" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Home</a>
                <a href="?action=test-login" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Test Login</a>
                <a href="?action=force-login" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Force Login</a>
                <a href="?action=check-auth" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">Check Auth</a>
                <a href="?action=logout" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow p-6">
            <?php
            switch ($action) {
                case 'test-login':
                    // Test login form
                    ?>
                    <h2 class="text-2xl font-semibold mb-4">Test Portal Login</h2>
                    
                    <?php if (isset($_POST['email'])): ?>
                        <?php
                        $credentials = [
                            'email' => $_POST['email'],
                            'password' => $_POST['password']
                        ];
                        
                        $attempt = Auth::guard('portal')->attempt($credentials, true);
                        ?>
                        
                        <div class="mb-4 p-4 <?php echo $attempt ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded">
                            <?php if ($attempt): ?>
                                ✅ Login successful! User: <?php echo Auth::guard('portal')->user()->email; ?>
                            <?php else: ?>
                                ❌ Login failed. Please check credentials.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="?action=test-login" class="space-y-4">
                        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="demo@askproai.de" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 border">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" value="demo123" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 border">
                        </div>
                        
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Test Login
                        </button>
                    </form>
                    <?php
                    break;
                    
                case 'force-login':
                    // Force login as demo user
                    $user = PortalUser::where('email', 'demo@askproai.de')->first();
                    if ($user) {
                        Auth::guard('portal')->login($user, true);
                        app()->instance('current_company_id', $user->company_id);
                        ?>
                        <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
                            ✅ Forced login as: <?php echo $user->email; ?>
                        </div>
                        <p>Session ID: <?php echo session()->getId(); ?></p>
                        <p>Auth Check: <?php echo Auth::guard('portal')->check() ? 'YES' : 'NO'; ?></p>
                        <?php
                    } else {
                        ?>
                        <div class="bg-red-100 text-red-800 p-4 rounded">
                            ❌ Demo user not found!
                        </div>
                        <?php
                    }
                    break;
                    
                case 'check-auth':
                    // Check authentication status
                    ?>
                    <h2 class="text-2xl font-semibold mb-4">Authentication Status</h2>
                    
                    <div class="space-y-2">
                        <p><strong>Portal Guard:</strong> <?php echo Auth::guard('portal')->check() ? '✅ Authenticated' : '❌ Not Authenticated'; ?></p>
                        <?php if (Auth::guard('portal')->check()): ?>
                            <p>User: <?php echo Auth::guard('portal')->user()->email; ?></p>
                            <p>User ID: <?php echo Auth::guard('portal')->user()->id; ?></p>
                            <p>Company ID: <?php echo Auth::guard('portal')->user()->company_id; ?></p>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <p><strong>Web Guard:</strong> <?php echo Auth::guard('web')->check() ? '✅ Authenticated' : '❌ Not Authenticated'; ?></p>
                        <?php if (Auth::guard('web')->check()): ?>
                            <p>User: <?php echo Auth::guard('web')->user()->email; ?></p>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <p><strong>Session ID:</strong> <?php echo session()->getId(); ?></p>
                        <p><strong>CSRF Token:</strong> <?php echo substr(csrf_token(), 0, 20); ?>...</p>
                        
                        <hr class="my-4">
                        
                        <h3 class="font-semibold">Session Data:</h3>
                        <pre class="bg-gray-100 p-2 rounded text-sm overflow-x-auto"><?php print_r(session()->all()); ?></pre>
                    </div>
                    <?php
                    break;
                    
                case 'logout':
                    // Logout
                    Auth::guard('portal')->logout();
                    Auth::guard('web')->logout();
                    session()->flush();
                    ?>
                    <div class="bg-yellow-100 text-yellow-800 p-4 rounded">
                        ✅ Logged out from all guards and session flushed.
                    </div>
                    <?php
                    break;
                    
                default:
                    // Home
                    ?>
                    <h2 class="text-2xl font-semibold mb-4">Business Portal Test Suite</h2>
                    
                    <div class="space-y-4">
                        <div class="bg-blue-50 p-4 rounded">
                            <h3 class="font-semibold mb-2">Available Tests:</h3>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Test Login:</strong> Test the portal login with form submission</li>
                                <li><strong>Force Login:</strong> Force login as demo@askproai.de user</li>
                                <li><strong>Check Auth:</strong> Check current authentication status</li>
                                <li><strong>Logout:</strong> Logout from all guards</li>
                            </ul>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded">
                            <h3 class="font-semibold mb-2">Quick Links:</h3>
                            <ul class="space-y-1">
                                <li><a href="/business/login" class="text-blue-600 hover:underline">Business Portal Login</a></li>
                                <li><a href="/business" class="text-blue-600 hover:underline">Business Portal Dashboard</a></li>
                                <li><a href="/admin" class="text-blue-600 hover:underline">Admin Panel</a></li>
                                <li><a href="/admin-working.php" class="text-blue-600 hover:underline">Working Admin Dashboard</a></li>
                            </ul>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded">
                            <h3 class="font-semibold mb-2">Test Credentials:</h3>
                            <p>Email: demo@askproai.de</p>
                            <p>Password: demo123</p>
                        </div>
                    </div>
                    <?php
            }
            ?>
        </div>
        
        <!-- Debug Info -->
        <div class="mt-8 bg-gray-50 p-4 rounded text-sm">
            <h3 class="font-semibold mb-2">Debug Info:</h3>
            <p>Environment: <?php echo app()->environment(); ?></p>
            <p>Debug Mode: <?php echo config('app.debug') ? 'ON' : 'OFF'; ?></p>
            <p>URL: <?php echo config('app.url'); ?></p>
        </div>
    </div>
</body>
</html>