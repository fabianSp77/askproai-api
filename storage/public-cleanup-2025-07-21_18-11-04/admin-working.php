<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a simple request
$request = Illuminate\Http\Request::create('/');
$response = $kernel->handle($request);

// Check if we have a logged in user
$user = null;
if (auth('web')->check()) {
    $user = auth('web')->user();
} else {
    // Try to log in as admin
    $adminUser = \App\Models\User::where('email', 'admin@askproai.de')->first();
    if ($adminUser) {
        auth('web')->login($adminUser);
        $user = $adminUser;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Working</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-semibold">Admin Portal</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="#" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="#calls" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Calls
                            </a>
                            <a href="#appointments" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Appointments
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <?php if ($user): ?>
                            <span class="text-sm text-gray-500 mr-4"><?php echo htmlspecialchars($user->email); ?></span>
                            <a href="/logout" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                        <?php else: ?>
                            <a href="/admin/login" class="text-sm text-gray-500 hover:text-gray-700">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <?php
                    // Get stats
                    $callCount = \App\Models\Call::count();
                    $appointmentCount = \App\Models\Appointment::count();
                    $customerCount = \App\Models\Customer::count();
                    ?>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Calls</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($callCount); ?></dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Appointments</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($appointmentCount); ?></dd>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($customerCount); ?></dd>
                        </div>
                    </div>
                </div>

                <!-- Recent Calls -->
                <div class="mt-8">
                    <h2 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Calls</h2>
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $recentCalls = \App\Models\Call::latest()->limit(10)->get();
                                foreach ($recentCalls as $call):
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($call->id); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($call->from_number ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($call->to_number ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $call->duration_sec ? $call->duration_sec . 's' : 'N/A'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <?php echo htmlspecialchars($call->status ?? 'completed'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $call->created_at ? $call->created_at->format('Y-m-d H:i') : 'N/A'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Debug Info -->
                <div class="mt-8 bg-gray-50 p-4 rounded">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Debug Information</h3>
                    <dl class="text-sm text-gray-600">
                        <dt class="inline font-medium">Auth Status:</dt>
                        <dd class="inline"><?php echo auth('web')->check() ? 'Authenticated' : 'Not Authenticated'; ?></dd><br>
                        
                        <dt class="inline font-medium">User Email:</dt>
                        <dd class="inline"><?php echo $user ? htmlspecialchars($user->email) : 'N/A'; ?></dd><br>
                        
                        <dt class="inline font-medium">User ID:</dt>
                        <dd class="inline"><?php echo $user ? $user->id : 'N/A'; ?></dd><br>
                        
                        <dt class="inline font-medium">Company ID:</dt>
                        <dd class="inline"><?php echo $user ? $user->company_id : 'N/A'; ?></dd><br>
                        
                        <dt class="inline font-medium">Session ID:</dt>
                        <dd class="inline"><?php echo session()->getId(); ?></dd>
                    </dl>
                </div>

                <!-- Links -->
                <div class="mt-8">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Other Portals</h3>
                    <div class="space-x-4">
                        <a href="/admin" class="text-blue-600 hover:underline">Try Filament Admin</a>
                        <a href="/business" class="text-blue-600 hover:underline">Business Portal</a>
                        <a href="/minimal-dashboard.php?uid=41" class="text-blue-600 hover:underline">Minimal Dashboard</a>
                        <a href="/admin-login-bypass.php" class="text-blue-600 hover:underline">Admin Bypass Login</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>